<?php
session_start();
if (!isset($_SESSION['adminID']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../admin_login.php'); exit;
}
require_once '../BackendLogic/dbConn.php';
 
$adminID  = (int)$_SESSION['adminID'];
$initials = strtoupper(substr($_SESSION['firstName'],0,1).substr($_SESSION['lastName'],0,1));
 
// Send message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send'])) {
    $to   = (int)$_POST['receiverID'];
    $body = trim($_POST['body'] ?? '');
    if ($to && $body) {
        $s = $conn->prepare("INSERT INTO tblMessages (senderID,receiverID,body) VALUES (?,?,?)");
        $s->bind_param("iis", $adminID, $to, $body);
        $s->execute(); $s->close();
    }
    header("Location: messages.php?to=$to"); exit;
}
 
// Mark messages as read
$withID = isset($_GET['to']) ? (int)$_GET['to'] : 0;
if ($withID) {
    $conn->query("UPDATE tblMessages SET isRead=1 WHERE receiverID=$adminID AND senderID=$withID AND isRead=0");
}
 
// Inbox: last message per conversation
$convos = $conn->query("
    SELECT u.userID, u.firstName, u.lastName, u.username, u.role,
           m.body AS lastMsg, m.createdAt AS lastAt,
           (SELECT COUNT(*) FROM tblMessages WHERE receiverID=$adminID AND senderID=u.userID AND isRead=0) AS unread
    FROM tblUser u
    INNER JOIN tblMessages m ON (
        (m.senderID=u.userID AND m.receiverID=$adminID) OR
        (m.senderID=$adminID AND m.receiverID=u.userID)
    )
    WHERE u.userID != $adminID
    GROUP BY u.userID
    ORDER BY lastAt DESC
");
 
// All non-admin users for new conversation
$allUsers = $conn->query("SELECT userID, firstName, lastName, username, role FROM tblUser WHERE userID != $adminID AND role != 'admin' ORDER BY firstName");
 
// Conversation thread
$thread = [];
$withUser = null;
if ($withID) {
    $s = $conn->prepare("
        SELECT m.*, u.firstName, u.lastName, u.username
        FROM tblMessages m
        JOIN tblUser u ON m.senderID = u.userID
        WHERE (m.senderID=? AND m.receiverID=?) OR (m.senderID=? AND m.receiverID=?)
        ORDER BY m.createdAt ASC
    ");
    $s->bind_param("iiii", $adminID, $withID, $withID, $adminID);
    $s->execute();
    $thread = $s->get_result()->fetch_all(MYSQLI_ASSOC);
    $s->close();
    $ws = $conn->prepare("SELECT * FROM tblUser WHERE userID=?");
    $ws->bind_param("i", $withID); $ws->execute();
    $withUser = $ws->get_result()->fetch_assoc(); $ws->close();
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Messages – Admin</title>
<link rel="stylesheet" href="../../css/styles.css">
<link rel="stylesheet" href="../../css/responsive.css">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
body { background:var(--cream); font-family:var(--font-body); }
.messages-shell { display:grid; grid-template-columns:300px 1fr; height:calc(100vh - 64px); }
.inbox-panel { background:white; border-right:1px solid var(--border); display:flex; flex-direction:column; overflow:hidden; }
.inbox-header { padding:16px 20px; border-bottom:1px solid var(--border); display:flex; justify-content:space-between; align-items:center; }
.inbox-title { font-family:var(--font-display); font-size:18px; font-weight:700; }
.convo-list { overflow-y:auto; flex:1; }
.convo-item { display:flex; gap:12px; padding:14px 20px; cursor:pointer; border-bottom:1px solid var(--border-light); transition:.15s; text-decoration:none; color:inherit; }
.convo-item:hover, .convo-item.active { background:#fafafa; }
.convo-item.active { background:#fff3f3; border-left:3px solid var(--primary); }
.convo-avatar { width:40px; height:40px; border-radius:50%; background:var(--primary); color:white; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:14px; flex-shrink:0; }
.convo-name { font-weight:600; font-size:14px; }
.convo-preview { font-size:12px; color:var(--text-muted); margin-top:2px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:160px; }
.convo-time { font-size:11px; color:var(--text-muted); white-space:nowrap; }
.unread-badge { background:var(--primary); color:white; border-radius:999px; font-size:10px; font-weight:700; padding:1px 6px; min-width:18px; text-align:center; }
.role-tag { font-size:10px; font-weight:700; padding:1px 6px; border-radius:999px; text-transform:uppercase; }
.role-seller { background:#e6faf0; color:#1a5c35; }
.role-customer { background:#e8f0fe; color:#1a3c8b; }
 
.chat-panel { display:flex; flex-direction:column; background:var(--cream); }
.chat-header { background:white; border-bottom:1px solid var(--border); padding:14px 24px; display:flex; align-items:center; gap:14px; }
.chat-contact-name { font-weight:700; font-size:16px; }
.chat-role { font-size:12px; color:var(--text-muted); }
.chat-messages { flex:1; overflow-y:auto; padding:20px 24px; display:flex; flex-direction:column; gap:10px; }
.msg-row { display:flex; gap:8px; align-items:flex-end; }
.msg-row.sent { flex-direction:row-reverse; }
.msg-avatar { width:28px; height:28px; border-radius:50%; background:var(--dark); color:white; display:flex; align-items:center; justify-content:center; font-size:10px; font-weight:700; flex-shrink:0; }
.msg-bubble { padding:10px 14px; border-radius:18px; font-size:14px; max-width:60%; line-height:1.5; }
.msg-bubble.received { background:white; border:1px solid var(--border-light); border-bottom-left-radius:4px; }
.msg-bubble.sent { background:var(--primary); color:white; border-bottom-right-radius:4px; }
.msg-time { font-size:10px; color:var(--text-muted); margin-top:3px; }
.msg-row.sent .msg-time { text-align:right; }
.chat-input-area { background:white; border-top:1px solid var(--border); padding:14px 24px; display:flex; gap:10px; align-items:center; }
.chat-input { flex:1; border:1.5px solid var(--border); border-radius:999px; padding:10px 18px; font-size:14px; outline:none; }
.chat-input:focus { border-color:var(--primary); }
.send-btn { background:var(--primary); color:white; border:none; border-radius:50%; width:40px; height:40px; font-size:18px; cursor:pointer; flex-shrink:0; }
.empty-chat { flex:1; display:flex; align-items:center; justify-content:center; color:var(--text-muted); flex-direction:column; gap:12px; }
.new-convo-btn { background:var(--primary); color:white; border:none; border-radius:999px; padding:7px 16px; font-size:13px; font-weight:600; cursor:pointer; }
.chat-date { text-align:center; font-size:11px; color:var(--text-muted); font-weight:600; text-transform:uppercase; letter-spacing:.06em; margin:8px 0; }
</style>
</head>
<body>
 
<?php include 'adminNavbar.php'; ?>
 
<div class="messages-shell">
 
    <!-- INBOX -->
    <div class="inbox-panel">
        <div class="inbox-header">
            <div class="inbox-title">Inbox</div>
            <button class="new-convo-btn" onclick="document.getElementById('newConvoModal').style.display='flex'">+ New</button>
        </div>
        <div class="convo-list">
            <?php while ($c = $convos->fetch_assoc()): 
                $ini = strtoupper(substr($c['firstName'],0,1).substr($c['lastName'],0,1));
            ?>
            <a href="messages.php?to=<?= $c['userID'] ?>"
               class="convo-item <?= $withID===$c['userID']?'active':'' ?>">
                <div class="convo-avatar"><?= $ini ?></div>
                <div style="flex:1;min-width:0;">
                    <div style="display:flex;justify-content:space-between;align-items:center;gap:4px;">
                        <div class="convo-name"><?= htmlspecialchars($c['firstName'].' '.$c['lastName']) ?></div>
                        <div class="convo-time"><?= date('d M', strtotime($c['lastAt'])) ?></div>
                    </div>
                    <div style="display:flex;align-items:center;gap:6px;margin-top:2px;">
                        <span class="role-tag role-<?= $c['role'] ?>"><?= $c['role'] ?></span>
                        <?php if ($c['unread']>0): ?><span class="unread-badge"><?= $c['unread'] ?></span><?php endif; ?>
                    </div>
                    <div class="convo-preview"><?= htmlspecialchars($c['lastMsg']) ?></div>
                </div>
            </a>
            <?php endwhile; ?>
        </div>
    </div>
 
    <!-- CHAT -->
    <div class="chat-panel">
        <?php if ($withUser): ?>
            <div class="chat-header">
                <div class="convo-avatar" style="width:38px;height:38px;font-size:13px;">
                    <?= strtoupper(substr($withUser['firstName'],0,1).substr($withUser['lastName'],0,1)) ?>
                </div>
                <div>
                    <div class="chat-contact-name"><?= htmlspecialchars($withUser['firstName'].' '.$withUser['lastName']) ?></div>
                    <div class="chat-role">@<?= htmlspecialchars($withUser['username']) ?> · <span class="role-tag role-<?= $withUser['role'] ?>"><?= $withUser['role'] ?></span></div>
                </div>
            </div>
 
            <div class="chat-messages" id="chatMsgs">
                <?php
                $lastDate = '';
                foreach ($thread as $m):
                    $isMe = (int)$m['senderID'] === $adminID;
                    $msgDate = date('d M Y', strtotime($m['createdAt']));
                    if ($msgDate !== $lastDate): $lastDate = $msgDate; ?>
                        <div class="chat-date"><?= $msgDate ?></div>
                    <?php endif; ?>
                    <div class="msg-row <?= $isMe ? 'sent' : '' ?>">
                        <div class="msg-avatar"><?= $isMe ? $initials : strtoupper(substr($m['firstName'],0,1).substr($m['lastName'],0,1)) ?></div>
                        <div>
                            <div class="msg-bubble <?= $isMe ? 'sent' : 'received' ?>"><?= nl2br(htmlspecialchars($m['body'])) ?></div>
                            <div class="msg-time"><?= date('H:i', strtotime($m['createdAt'])) ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
 
            <form method="POST" action="messages.php?to=<?= $withID ?>">
                <input type="hidden" name="receiverID" value="<?= $withID ?>">
                <div class="chat-input-area">
                    <input class="chat-input" name="body" id="msgInput" placeholder="Type a message…" autocomplete="off" required>
                    <button type="submit" name="send" class="send-btn">➤</button>
                </div>
            </form>
 
        <?php else: ?>
            <div class="empty-chat">
                <div style="font-size:48px;">💬</div>
                <div style="font-weight:600;font-size:16px;">Select a conversation</div>
                <div style="font-size:13px;">Or start a new one with any user.</div>
                <button class="new-convo-btn" onclick="document.getElementById('newConvoModal').style.display='flex'">+ Start Conversation</button>
            </div>
        <?php endif; ?>
    </div>
</div>
 
<!-- NEW CONVERSATION MODAL -->
<div id="newConvoModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:200;align-items:center;justify-content:center;">
    <div style="background:white;border-radius:var(--radius-lg);padding:28px;width:400px;max-width:90vw;">
        <div style="font-family:var(--font-display);font-size:20px;font-weight:700;margin-bottom:16px;">New Message</div>
        <form method="POST" action="messages.php">
            <div class="form-group">
                <label class="form-label">SEND TO</label>
                <select class="form-input" name="receiverID" required>
                    <option value="">Select user…</option>
                    <?php $allUsers->data_seek(0); while ($u = $allUsers->fetch_assoc()): ?>
                    <option value="<?= $u['userID'] ?>"><?= htmlspecialchars($u['firstName'].' '.$u['lastName']) ?> (@<?= $u['username'] ?>) — <?= $u['role'] ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">MESSAGE</label>
                <textarea class="form-input" name="body" rows="4" placeholder="Type your message…" required></textarea>
            </div>
            <div style="display:flex;gap:10px;justify-content:flex-end;">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('newConvoModal').style.display='none'">Cancel</button>
                <button type="submit" name="send" class="btn btn-primary">Send</button>
            </div>
        </form>
    </div>
</div>
 
<script>
// Auto-scroll to bottom
const msgs = document.getElementById('chatMsgs');
if (msgs) msgs.scrollTop = msgs.scrollHeight;
// Enter to send
document.getElementById('msgInput')?.addEventListener('keypress', e => {
    if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); e.target.closest('form').submit(); }
});
</script>
</body>
</html>