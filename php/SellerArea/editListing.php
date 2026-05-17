
<?php
session_start();
if (!isset($_SESSION['userID']) || $_SESSION['role'] !== 'seller') {
    header('Location: ../AuthSystem/login.php'); exit;
}
require_once '../BackendLogic/dbConn.php';

$sellerID  = (int)$_SESSION['userID'];
$listingID = (int)($_GET['id'] ?? 0);
if (!$listingID) { header('Location: sellerDashboard.php'); exit; }

// Verify ownership
$stmt = $conn->prepare("SELECT * FROM tblListings WHERE listingID=? AND sellerID=?");
$stmt->bind_param("ii", $listingID, $sellerID);
$stmt->execute();
$listing = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$listing) { header('Location: sellerDashboard.php?err=not_found'); exit; }

$error = $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'save';

    if ($action === 'delete') {
        // Delete image file
        if (!empty($listing['imagePath'])) {
            $fp = __DIR__ . '/../../' . $listing['imagePath'];
            if (file_exists($fp)) @unlink($fp);
        }
        $d = $conn->prepare("DELETE FROM tblListings WHERE listingID=? AND sellerID=?");
        $d->bind_param("ii", $listingID, $sellerID);
        $d->execute(); $d->close();
        $conn->close();
        header('Location: sellerDashboard.php?msg=deleted'); exit;
    }

    // Save / update
    $title       = htmlspecialchars(trim($_POST['title']       ?? ''));
    $description = htmlspecialchars(trim($_POST['description'] ?? ''));
    $category    = htmlspecialchars(trim($_POST['category']    ?? ''));
    $condition   = htmlspecialchars(trim($_POST['condition_']  ?? ''));
    $price       = floatval($_POST['price'] ?? 0);
    $delivery    = implode(', ', $_POST['delivery'] ?? []);
    $imagePath   = $listing['imagePath'];

    if (!$title || !$category || !$condition || $price <= 0) {
        $error = "Please fill in all required fields and set a price above R0.";
    } else {
        // Handle new image upload
        if (!empty($_FILES['image']['name'])) {
            $ext     = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg','jpeg','png','webp'];
            if (!in_array($ext, $allowed)) {
                $error = "Image must be JPG, PNG or WEBP.";
            } elseif ($_FILES['image']['size'] > 5*1024*1024) {
                $error = "Image must be under 5MB.";
            } else {
                // Delete old
                if (!empty($listing['imagePath'])) {
                    $fp = __DIR__ . '/../../' . $listing['imagePath'];
                    if (file_exists($fp)) @unlink($fp);
                }
                $uploadDir = '../uploads/listings/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                $filename  = uniqid('listing_', true) . '.' . $ext;
                $imagePath = 'php/uploads/listings/' . $filename;
                move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $filename);
            }
        }

        if (!$error) {
            // Re-submit for admin approval if the listing was rejected
            $newStatus = $listing['status'] === 'rejected' ? 'pending' : $listing['status'];
            $upd = $conn->prepare(
                "UPDATE tblListings SET title=?,description=?,category=?,condition_=?,price=?,delivery=?,imagePath=?,status=? WHERE listingID=? AND sellerID=?"
            );
            $upd->bind_param("ssssdsssii", $title,$description,$category,$condition,$price,$delivery,$imagePath,$newStatus,$listingID,$sellerID);
            if ($upd->execute()) {
                $listing = array_merge($listing, compact('title','description','category','condition','price','delivery','imagePath'));
                $listing['status'] = $newStatus;
                $success = "Listing updated!" . ($newStatus === 'pending' ? ' Re-submitted for admin review.' : '');
            } else {
                $error = "Failed to update: " . $conn->error;
            }
            $upd->close();
        }
    }
}
$conn->close();
$initials = strtoupper(substr($_SESSION['firstName'],0,1).substr($_SESSION['lastName'],0,1));
$categories = ['Clothing','Accessories','Footwear','Outerwear','Jewelry','Vintage'];
$conditions = ['New with Tags','Like New','Good','Fair'];
$deliveryOpts = ['Paxi','PUDO','Aramex','Collection'];
$currentDelivery = array_map('trim', explode(',', $listing['delivery'] ?? ''));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit Listing – Past Times</title>
<link rel="stylesheet" href="../../css/styles.css">
<link rel="stylesheet" href="../../css/responsive.css">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
.alert-success{background:#e6faf0;border:1px solid #b2dbd7;color:#1a5c35;border-radius:8px;padding:14px 18px;margin-bottom:20px;font-size:14px;}
.alert-error{background:#fce8e8;border:1px solid #f5b7b7;color:#8b1a14;border-radius:8px;padding:14px 18px;margin-bottom:20px;font-size:14px;}
.delivery-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-top:8px;}
.delivery-option{display:flex;align-items:center;gap:10px;padding:12px 14px;border:1.5px solid var(--border);border-radius:var(--radius);cursor:pointer;font-size:14px;font-weight:500;}
.delivery-option input{accent-color:var(--primary);}
.price-wrap{position:relative;}
.price-prefix{position:absolute;left:14px;top:50%;transform:translateY(-50%);font-weight:700;color:var(--text-muted);}
.price-wrap input{padding-left:32px;}
.upload-zone{border:2px dashed var(--border);border-radius:var(--radius);padding:32px;text-align:center;cursor:pointer;background:#fafafa;transition:.2s;}
.upload-zone:hover{border-color:var(--primary);background:#fff8f7;}
.status-info{border-radius:8px;padding:12px 16px;font-size:13px;margin-bottom:20px;}
.danger-zone{border:1.5px solid #f5b7b7;border-radius:var(--radius);padding:20px;margin-top:28px;}
</style>
</head>
<body>
<nav class="navbar">
  <div class="navbar-brand" onclick="location.href='sellerDashboard.php'">
    <div class="logo-icon">P</div>
    <span style="font-family:var(--font-display);font-size:16px;font-weight:700;">Past Times</span>
  </div>
  <div class="navbar-nav">
    <a class="nav-link" href="sellerDashboard.php">My Dashboard</a>
    <a class="nav-link" href="create-listing.php">+ New Listing</a>
  </div>
  <div class="navbar-actions">
    <div class="avatar-btn" onclick="location.href='../../html/settings.php'"><?= $initials ?></div>
    <a href="../AuthSystem/logout.php" style="font-size:13px;font-weight:600;color:var(--primary);text-decoration:none;margin-left:12px;">Log Out</a>
  </div>
</nav>

<div class="listing-layout">
  <h1 class="listing-title">Edit Listing</h1>
  <p class="listing-subtitle">Update your item details. Changes to rejected listings will re-submit for admin review.</p>

  <?php
  $statusColors = ['pending'=>['#fff3e0','#7a4f00'],'approved'=>['#e6faf0','#1a5c35'],'rejected'=>['#fce8e8','#8b1a14']];
  [$sbg,$sc] = $statusColors[$listing['status']] ?? $statusColors['pending'];
  ?>
  <div class="status-info" style="background:<?= $sbg ?>;color:<?= $sc ?>;border:1px solid <?= $sc ?>22;">
      Status: <strong><?= strtoupper($listing['status']) ?></strong>
      <?php if ($listing['status']==='rejected'): ?> — Editing will re-submit for approval.<?php endif; ?>
  </div>

  <?php if ($success): ?><div class="alert-success"><?= $success ?></div><?php endif; ?>
  <?php if ($error):   ?><div class="alert-error"><?= $error ?></div><?php endif; ?>

  <form method="POST" action="editListing.php?id=<?= $listingID ?>" enctype="multipart/form-data">

    <!-- Photo -->
    <div class="listing-section" style="margin-bottom:20px;">
      <div class="listing-section-title">Photo</div>
      <?php if (!empty($listing['imagePath'])): ?>
        <img src="../../<?= htmlspecialchars($listing['imagePath']) ?>" style="width:140px;height:140px;object-fit:cover;border-radius:var(--radius);margin-bottom:12px;" alt="">
        <div style="font-size:12px;color:var(--text-muted);margin-bottom:8px;">Upload a new photo to replace the current one.</div>
      <?php endif; ?>
      <label class="upload-zone" for="image">
        <div style="font-size:28px;margin-bottom:6px;">📷</div>
        <div style="font-weight:600;font-size:14px;">Click to <?= empty($listing['imagePath'])?'upload':'change' ?> photo</div>
        <div style="font-size:12px;color:var(--text-muted);margin-top:4px;">JPG, PNG or WEBP — max 5MB</div>
        <input type="file" id="image" name="image" accept=".jpg,.jpeg,.png,.webp" style="display:none;" onchange="previewImage(this)">
      </label>
      <img id="preview" src="" alt="" style="display:none;max-height:180px;margin-top:12px;border-radius:var(--radius);object-fit:cover;">
    </div>

    <!-- Details -->
    <div class="listing-section">
      <div class="listing-section-title">Item Details</div>
      <div class="form-group">
        <label class="form-label">TITLE *</label>
        <input class="form-input" type="text" name="title" value="<?= htmlspecialchars($listing['title']) ?>" required maxlength="150">
      </div>
      <div class="form-group">
        <label class="form-label">DESCRIPTION</label>
        <textarea class="form-input" name="description" rows="4"><?= htmlspecialchars($listing['description'] ?? '') ?></textarea>
      </div>
      <div class="input-row">
        <div class="form-group">
          <label class="form-label">CATEGORY *</label>
          <select class="form-input" name="category" required>
            <option value="">Select Category</option>
            <?php foreach ($categories as $c): ?>
            <option <?= $listing['category']===$c?'selected':'' ?>><?= $c ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">CONDITION *</label>
          <select class="form-input" name="condition_" required>
            <option value="">Select Condition</option>
            <?php foreach ($conditions as $c): ?>
            <option <?= $listing['condition_']===$c?'selected':'' ?>><?= $c ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
      <div class="listing-section">
        <div class="listing-section-title">Price *</div>
        <div class="price-wrap">
          <span class="price-prefix">R</span>
          <input class="form-input" type="number" name="price" min="1" step="0.01" value="<?= $listing['price'] ?>" required>
        </div>
      </div>
      <div class="listing-section">
        <div class="listing-section-title">Delivery Options</div>
        <div class="delivery-grid">
          <?php foreach ($deliveryOpts as $d): ?>
          <label class="delivery-option">
            <input type="checkbox" name="delivery[]" value="<?= $d ?>" <?= in_array($d,$currentDelivery)?'checked':'' ?>>
            <?= $d ?>
          </label>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <div style="margin-top:24px;display:flex;gap:12px;">
      <button class="btn btn-primary" type="submit" name="action" value="save">💾 Save Changes</button>
      <a href="sellerDashboard.php" class="btn btn-secondary">Cancel</a>
    </div>
  </form>

  <!-- DANGER ZONE -->
  <div class="danger-zone">
    <div style="font-weight:700;font-size:15px;color:#8b1a14;margin-bottom:6px;">⚠ Delete Listing</div>
    <div style="font-size:13px;color:var(--text-muted);margin-bottom:14px;">This will permanently remove the listing and its image. This cannot be undone.</div>
    <form method="POST" action="editListing.php?id=<?= $listingID ?>"
          onsubmit="return confirm('Delete this listing permanently?')">
      <button type="submit" name="action" value="delete"
              class="btn" style="background:#fce8e8;color:#8b1a14;border:1px solid #f5b7b7;">
        🗑 Delete Listing
      </button>
    </form>
  </div>
</div>

<script src="../../javascript/script.js"></script>
<script>
function previewImage(input) {
  const preview = document.getElementById('preview');
  if (input.files && input.files[0]) {
    const reader = new FileReader();
    reader.onload = e => { preview.src = e.target.result; preview.style.display = 'block'; };
    reader.readAsDataURL(input.files[0]);
  }
}
</script>
</body>
</html>
