<?php
session_start();
if (!isset($_SESSION['userID'])) {
    header('Location: ../php/AuthSystem/login.php'); exit;
}
require_once '../php/BackendLogic/dbConn.php';

$userID   = (int)$_SESSION['userID'];
$role     = $_SESSION['role'] ?? 'customer';
$initials = strtoupper(substr($_SESSION['firstName'],0,1).substr($_SESSION['lastName'],0,1));

// Send message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send'])) {
    $to   = (int)$_POST['receiverID'];
    $body = trim($_POST['body'] ?? '');

    // Customers can only message sellers (validate)
    if ($role === 'customer') {
        $check = $conn->query("SELECT role FROM tblUser WHERE userID=$to")->fetch_assoc();
        if (!$check || $check['role'] !== 'seller') {
            // Block: customers can't message admins/other customers directly
            header("Location: messages.php?err=only_sellers"); exit;
        }
    }

    if ($to && $body) {
        $s = $conn->prepare("INSERT INTO tblMessages (senderID,receiverID,body) VALUES (?,?,?)");
        $s->bind_param("iis",$userID,$to,$body); $s->execute(); $s->close();
    }
    header("Location: messages.php?to=$to"); exit;
}

$withID = isset($_GET['to']) ? (int)$_GET['to'] : 0;
if ($withID) {
    $conn->query("UPDATE tblMessages SET isRead=1 WHERE receiverID=$userID AND senderID=$withID AND isRead=0");
}

// Conversations list
$convos = $conn->query("
    SELECT u.userID, u.firstName, u.lastName, u.username, u.role,
           m.body AS lastMsg, m.createdAt AS lastAt,
           (SELECT COUNT(*) FROM tblMessages WHERE receiverID=$userID AND senderID=u.userID AND isRead=0) AS unread
    FROM tblUser u
    INNER JOIN tblMessages m ON (
        (m.senderID=u.userID AND m.receiverID=$userID) OR
        (m.senderID=$userID AND m.receiverID=u.userID)
    )
    WHERE u.userID != $userID
    GROUP BY u.userID ORDER BY lastAt DESC
");

// Who can customer contact? Sellers they've ordered from OR sellers of items in cart/favourites
$sellerContacts = [];
if ($role === 'customer') {
    // Get sellers from orders, favourites and cart (tblCart may not exist yet)
    $scSql = "
        SELECT DISTINCT u.userID, u.firstName, u.lastName, u.username
        FROM tblUser u
        WHERE u.role='seller' AND (
            u.userID IN (SELECT sellerID FROM tblOrders WHERE buyerID=$userID)
            OR u.userID IN (SELECT l.sellerID FROM tblFavourites f JOIN tblListings l ON f.listingID=l.listingID WHERE f.userID=$userID)
        )
        ORDER BY u.firstName
    ";
    $sc = $conn->query($scSql);
    if ($sc) while ($s = $sc->fetch_assoc()) $sellerContacts[] = $s;
    // Also add from cart if table exists
    $cartSellers = $conn->query("SELECT DISTINCT l.sellerID FROM tblCart c JOIN tblListings l ON c.listingID=l.listingID WHERE c.userID=$userID");
    if ($cartSellers) {
        $cartSellerIDs = array_column($cartSellers->fetch_all(MYSQLI_ASSOC),'sellerID');
        if (!empty($cartSellerIDs)) {
            $inList = implode(',', array_map('intval', $cartSellerIDs));
            $cs2 = $conn->query("SELECT userID,firstName,lastName,username FROM tblUser WHERE userID IN ($inList)");
            if ($cs2) while ($s=$cs2->fetch_assoc()) {
                if (!in_array($s['userID'], array_column($sellerContacts,'userID'))) $sellerContacts[] = $s;
            }
        }
    }
}

// Thread
$thread=[]; $withUser=null;
if ($withID) {
    $s=$conn->prepare("
        SELECT m.*,u.firstName,u.lastName FROM tblMessages m
        JOIN tblUser u ON m.senderID=u.userID
        WHERE (m.senderID=? AND m.receiverID=?) OR (m.senderID=? AND m.receiverID=?)
        ORDER BY m.createdAt ASC
    ");
    $s->bind_param("iiii",$userID,$withID,$withID,$userID);
    $s->execute(); $thread=$s->get_result()->fetch_all(MYSQLI_ASSOC); $s->close();
    $ws=$conn->prepare("SELECT * FROM tblUser WHERE userID=?");
    $ws->bind_param("i",$withID); $ws->execute();
    $withUser=$ws->get_result()->fetch_assoc(); $ws->close();
}
$conn->close();

// Cart count for navbar badge
$cartCount = 0; // We could fetch but keeping it simple
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Messages – Past Times</title>
<link rel="stylesheet" href="../css/styles.css">
<link rel="stylesheet" href="../css/responsive.css">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
body{background:var(--cream);font-family:var(--font-body);}
.messages-layout{display:grid;grid-template-columns:300px 1fr;height:calc(100vh - 64px);}
@media(max-width:680px){.messages-layout{grid-template-columns:1fr;} .chat-panel{display:<?= $withID?'flex':'none' ?>;}}
.inbox-panel{background:white;border-right:1px solid var(--border);display:flex;flex-direction:column;overflow:hidden;}
.inbox-header{padding:16px 20px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;}
.inbox-title{font-family:var(--font-display);font-size:18px;font-weight:700;}
.inbox-search{display:flex;align-items:center;gap:8px;padding:10px 20px;border-bottom:1px solid var(--border-light);}
.inbox-search input{flex:1;border:1.5px solid var(--border);border-radius:999px;padding:7px 14px;font-size:13px;outline:none;}
.inbox-search input:focus{border-color:var(--primary);}
.convo-list{overflow-y:auto;flex:1;}
.convo-item{display:flex;gap:12px;padding:14px 20px;cursor:pointer;border-bottom:1px solid var(--border-light);transition:.15s;text-decoration:none;color:inherit;}
.convo-item:hover,.convo-item.active{background:#fafafa;}
.convo-item.active{background:#fff3f3;border-left:3px solid var(--primary);}
.convo-avatar{width:40px;height:40px;border-radius:50%;background:var(--primary);color:white;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:14px;flex-shrink:0;}
.convo-name{font-weight:600;font-size:14px;}
.convo-preview{font-size:12px;color:var(--text-muted);margin-top:2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:160px;}
.convo-time{font-size:11px;color:var(--text-muted);white-space:nowrap;}
.unread-dot{width:10px;height:10px;border-radius:50%;background:var(--primary);flex-shrink:0;align-self:center;}
.unread-badge{background:var(--primary);color:white;border-radius:999px;font-size:10px;font-weight:700;padding:1px 6px;}
.role-tag{font-size:10px;font-weight:700;padding:1px 6px;border-radius:999px;text-transform:uppercase;}
.role-seller{background:#e6faf0;color:#1a5c35;}
.chat-panel{display:flex;flex-direction:column;background:var(--cream);}
.chat-header{background:white;border-bottom:1px solid var(--border);padding:14px 24px;display:flex;align-items:center;gap:14px;}
.chat-messages{flex:1;overflow-y:auto;padding:20px 24px;display:flex;flex-direction:column;gap:10px;}
.msg-row{display:flex;gap:8px;align-items:flex-end;}
.msg-row.sent{flex-direction:row-reverse;}
.msg-avatar{width:28px;height:28px;border-radius:50%;background:var(--dark);color:white;display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:700;flex-shrink:0;}
.msg-bubble{padding:10px 14px;border-radius:18px;font-size:14px;max-width:60%;line-height:1.5;}
.msg-bubble.received{background:white;border:1px solid var(--border-light);border-bottom-left-radius:4px;}
.msg-bubble.sent{background:var(--primary);color:white;border-bottom-right-radius:4px;}
.msg-time{font-size:10px;color:var(--text-muted);margin-top:3px;}
.msg-row.sent .msg-time{text-align:right;}
.chat-input-area{background:white;border-top:1px solid var(--border);padding:14px 24px;display:flex;gap:10px;align-items:center;}
.chat-input{flex:1;border:1.5px solid var(--border);border-radius:999px;padding:10px 18px;font-size:14px;outline:none;}
.chat-input:focus{border-color:var(--primary);}
.send-btn{background:var(--primary);color:white;border:none;border-radius:50%;width:40px;height:40px;font-size:18px;cursor:pointer;flex-shrink:0;}
.chat-safety-note{text-align:center;font-size:11px;color:var(--text-muted);padding:6px 0;background:white;border-top:1px solid var(--border-light);}
.escrow-banner{background:#e6faf0;border-bottom:1px solid #b2dbd7;padding:10px 24px;font-size:13px;color:#1a5c35;}
.empty-chat{flex:1;display:flex;align-items:center;justify-content:center;color:var(--text-muted);flex-direction:column;gap:12px;}
.chat-date{text-align:center;font-size:11px;color:var(--text-muted);font-weight:600;text-transform:uppercase;letter-spacing:.06em;margin:8px 0;}
.new-btn{background:var(--primary);color:white;border:none;border-radius:999px;padding:7px 16px;font-size:13px;font-weight:600;cursor:pointer;}
</style>
</head>
<body>

<nav class="navbar">
  <div class="navbar-brand" onclick="location.href='home.php'">
    <div class="logo-icon">P</div>
    <span style="font-family:var(--font-display);font-size:16px;font-weight:700;">Past Times</span>
  </div>
  <div class="navbar-nav">
    <a class="nav-link" href="home.php">Explore</a>
    <a class="nav-link active">Messages</a>
  </div>
  <div class="navbar-actions">
    <div class="icon-btn" onclick="location.href='cart.php'" title="Cart">🛒</div>
    <div class="icon-btn">🔔</div>
    <div class="avatar-btn" onclick="location.href='dashboard.php'"><?= $initials ?></div>
  </div>
</nav>

<div class="messages-layout">

  <!-- INBOX PANEL -->
  <div class="inbox-panel">
    <div class="inbox-header">
      <div class="inbox-title">Inbox</div>
      <?php if ($role==='customer' && !empty($sellerContacts)): ?>
      <button class="new-btn" onclick="document.getElementById('newModal').style.display='flex'">+ New</button>
      <?php endif; ?>
    </div>
    <div class="inbox-search">
      <span>🔍︎</span>
      <input id="convoSearch" placeholder="Search conversations…" oninput="filterConvos(this.value)">
    </div>
    <div class="convo-list" id="convoList">
      <?php while ($c = $convos->fetch_assoc()):
        $ini=strtoupper(substr($c['firstName'],0,1).substr($c['lastName'],0,1));
        // Only show sellers to customers
        if ($role==='customer' && $c['role']!=='seller') continue;
      ?>
      <a href="messages.php?to=<?= $c['userID'] ?>"
         class="convo-item <?= $withID===$c['userID']?'active':'' ?>"
         data-name="<?= htmlspecialchars(strtolower($c['firstName'].' '.$c['lastName'])) ?>">
        <div class="convo-avatar"><?= $ini ?></div>
        <div style="flex:1;min-width:0;">
          <div style="display:flex;justify-content:space-between;align-items:center;gap:4px;">
            <div class="convo-name"><?= htmlspecialchars($c['firstName'].' '.$c['lastName']) ?></div>
            <div class="convo-time"><?= date('d M', strtotime($c['lastAt'])) ?></div>
          </div>
          <div style="display:flex;align-items:center;gap:6px;margin-top:2px;">
            <span class="role-tag role-<?= $c['role'] ?>"><?= $c['role'] ?></span>
            <?php if($c['unread']>0): ?><span class="unread-badge"><?= $c['unread'] ?></span><?php endif; ?>
          </div>
          <div class="convo-preview"><?= htmlspecialchars($c['lastMsg']) ?></div>
        </div>
      </a>
      <?php endwhile; ?>
    </div>
  </div>

  <!-- CHAT PANEL -->
  <div class="chat-panel">
    <?php if ($withUser): ?>

      <div class="chat-header">
        <div class="convo-avatar" style="width:38px;height:38px;font-size:13px;">
          <?= strtoupper(substr($withUser['firstName'],0,1).substr($withUser['lastName'],0,1)) ?>
        </div>
        <div style="flex:1;">
          <div style="font-weight:700;font-size:16px;"><?= htmlspecialchars($withUser['firstName'].' '.$withUser['lastName']) ?></div>
          <div style="font-size:12px;color:var(--text-muted);">@<?= htmlspecialchars($withUser['username']) ?> · <span class="role-tag role-<?= $withUser['role'] ?>"><?= $withUser['role'] ?></span></div>
        </div>
      </div>

      <div class="escrow-banner">
        🛡 <strong>Stay Safe:</strong> Always complete purchases through Past Times. Never pay outside the app.
      </div>

      <div class="chat-messages" id="chatMsgs">
        <?php
        $lastDate='';
        foreach($thread as $m):
          $isMe = (int)$m['senderID']===$userID;
          $d=date('d M Y',strtotime($m['createdAt']));
          if($d!==$lastDate):$lastDate=$d;?>
            <div class="chat-date"><?= $d ?></div>
          <?php endif;?>
          <div class="msg-row <?= $isMe?'sent':'' ?>">
            <div class="msg-avatar"><?= $isMe?$initials:strtoupper(substr($m['firstName'],0,1).substr($m['lastName'],0,1)) ?></div>
            <div>
              <div class="msg-bubble <?= $isMe?'sent':'received' ?>"><?= nl2br(htmlspecialchars($m['body'])) ?></div>
              <div class="msg-time"><?= date('H:i',strtotime($m['createdAt'])) ?></div>
            </div>
          </div>
        <?php endforeach;?>
      </div>

      <form method="POST" action="messages.php?to=<?= $withID ?>">
        <input type="hidden" name="receiverID" value="<?= $withID ?>">
        <div class="chat-input-area">
          <input class="chat-input" name="body" id="chat-input-field"
                 placeholder="Type a message…" autocomplete="off" required>
          <button type="submit" name="send" class="send-btn">➤</button>
        </div>
      </form>
      <div class="chat-safety-note">Never share your phone number or email. Keep conversations here.</div>

    <?php else: ?>
      <div class="empty-chat">
        <div style="font-size:48px;">💬</div>
        <div style="font-weight:600;font-size:16px;">Your messages</div>
        <div style="font-size:13px;text-align:center;max-width:280px;">
          <?= $role==='customer' ? 'Select a conversation or message a seller about an item.' : 'Select a conversation from the inbox.' ?>
        </div>
        <?php if ($role==='customer' && !empty($sellerContacts)): ?>
        <button class="new-btn" onclick="document.getElementById('newModal').style.display='flex'">+ Message a Seller</button>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </div>
</div>

<!-- NEW MESSAGE MODAL (customers only, to sellers) -->
<?php if ($role==='customer' && !empty($sellerContacts)): ?>
<div id="newModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:200;align-items:center;justify-content:center;">
  <div style="background:white;border-radius:var(--radius-lg);padding:28px;width:400px;max-width:90vw;">
    <div style="font-family:var(--font-display);font-size:20px;font-weight:700;margin-bottom:16px;">Message a Seller</div>
    <form method="POST" action="messages.php">
      <div class="form-group">
        <label class="form-label">SELLER</label>
        <select class="form-input" name="receiverID" required>
          <option value="">Select seller…</option>
          <?php foreach ($sellerContacts as $s): ?>
          <option value="<?= $s['userID'] ?>"><?= htmlspecialchars($s['firstName'].' '.$s['lastName']) ?> (@<?= $s['username'] ?>)</option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">MESSAGE</label>
        <textarea class="form-input" name="body" rows="4" placeholder="Ask about an item, delivery, or anything…" required></textarea>
      </div>
      <div style="display:flex;gap:10px;justify-content:flex-end;">
        <button type="button" class="btn btn-secondary" onclick="document.getElementById('newModal').style.display='none'">Cancel</button>
        <button type="submit" name="send" class="btn btn-primary">Send</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<script src="../javascript/script.js"></script>
<script>
const msgs = document.getElementById('chatMsgs');
if (msgs) msgs.scrollTop = msgs.scrollHeight;

document.getElementById('chat-input-field')?.addEventListener('keypress', e => {
  if (e.key==='Enter' && !e.shiftKey) { e.preventDefault(); e.target.closest('form').submit(); }
});

function filterConvos(query) {
  const q = query.toLowerCase();
  document.querySelectorAll('#convoList .convo-item').forEach(el => {
    el.style.display = (el.dataset.name || '').includes(q) ? '' : 'none';
  });
}
</script>
</body>
</html>