<?php
session_start();
if (!isset($_SESSION['userID'])) {
    header('Location: ../php/AuthSystem/login.php');
    exit;
}
require_once '../BackendLogic/dbConn.php'; 

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title       = htmlspecialchars(trim($_POST['title']       ?? ''));
    $description = htmlspecialchars(trim($_POST['description'] ?? ''));
    $category    = htmlspecialchars(trim($_POST['category']    ?? ''));
    $condition   = htmlspecialchars(trim($_POST['condition_']  ?? ''));
    $price       = floatval($_POST['price'] ?? 0);
    $delivery    = implode(', ', $_POST['delivery'] ?? []);
    $sellerID    = (int)$_SESSION['userID'];
    $imagePath   = null;

    // Basic validation
    if (!$title || !$category || !$condition || $price <= 0) {
        $error = "Please fill in all required fields and set a price above R0.";
    } else {
        // Handle image upload
        if (!empty($_FILES['image']['name'])) {
            $uploadDir = '../uploads/listings/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            $ext      = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $allowed  = ['jpg','jpeg','png','webp'];
            if (!in_array($ext, $allowed)) {
                $error = "Image must be JPG, PNG or WEBP.";
            } elseif ($_FILES['image']['size'] > 5 * 1024 * 1024) {
                $error = "Image must be under 5MB.";
            } else {
                $filename  = uniqid('listing_', true) . '.' . $ext;
                $imagePath = 'php/uploads/listings/' . $filename;
                                move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $filename);
            }
        }

        if (!$error) {
            $stmt = $conn->prepare(
                "INSERT INTO tblListings (sellerID, title, description, category, condition_, price, delivery, imagePath, status, createdAt)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())"
            );
            $stmt->bind_param("issssdss", $sellerID, $title, $description, $category, $condition, $price, $delivery, $imagePath);

            if ($stmt->execute()) {
                $success = "Listing submitted! It will appear on the shop once approved by an admin.";
            } else {
                $error = "Failed to save listing: " . $conn->error;
            }
            $stmt->close();
        }
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Create Listing – Past Times</title>
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
    .pending-info{background:#fff3e0;border:1px solid #ffe0b2;border-radius:8px;padding:12px 16px;font-size:13px;color:#7a4f00;margin-top:16px;}
  </style>
</head>
<body>
<nav class="navbar">

  <div class="navbar-brand" onclick="location.href='sellerDashboard.php' // ✓ same dir">
        <div class="logo-icon">P</div>
        <span style="font-family:var(--font-display);font-size:16px;font-weight:700;">Past Times</span>
    </div>
  <div class="navbar-nav">
    <a class="nav-link" href="sellerDashboard.php">My Dashboard</a>
    <a class="nav-link active" href="create-listing.php">Sell</a>
  
  </div>
  <div class="navbar-actions">
    <div class="icon-btn">🔔</div>
    <div class="avatar-btn" onclick="location.href='sellerDashboard.php'">
      <?= strtoupper(substr($_SESSION['firstName'],0,1).substr($_SESSION['lastName'],0,1)) ?>
    </div>
  </div>
</nav>

<div class="listing-layout">
  <h1 class="listing-title">Create a Listing</h1>
  <p class="listing-subtitle">Turn your pre-loved treasures into someone else's new favourite find.</p>

  <?php if ($success): ?>
    <div class="alert-success"><?= $success ?></div>
    <div class="pending-info">⏳ Your listing is under admin review. You can track its status in your <a href="sellerDashboard.php" style="color:var(--primary);font-weight:600;">dashboard</a>.</div>
  <?php else: ?>

  <?php if ($error): ?>
    <div class="alert-error"><?= $error ?></div>
  <?php endif; ?>

  <form method="POST" action="create-listing.php" enctype="multipart/form-data">

    <!-- Image Upload -->
    <div class="listing-section" style="margin-bottom:20px;">
      <div class="listing-section-title">Photo</div>
      <label class="upload-zone" for="image">
        <div style="font-size:32px;margin-bottom:8px;">📷</div>
        <div style="font-weight:600;font-size:14px;">Click to upload a photo</div>
        <div style="font-size:12px;color:var(--text-muted);margin-top:4px;">JPG, PNG or WEBP — max 5MB</div>
        <input type="file" id="image" name="image" accept=".jpg,.jpeg,.png,.webp" style="display:none;" onchange="previewImage(this)">
      </label>
      <img id="preview" src="" alt="" style="display:none;max-height:200px;margin-top:12px;border-radius:var(--radius);object-fit:cover;">
    </div>

    <div class="listing-section">
      <div class="listing-section-title">Item Details</div>

      <div class="form-group">
        <label class="form-label">TITLE *</label>
        <input class="form-input" type="text" name="title" placeholder="e.g. Vintage 90s Oversized Wool Blazer" required maxlength="150">
      </div>

      <div class="form-group">
        <label class="form-label">DESCRIPTION</label>
        <textarea class="form-input" name="description" rows="4" placeholder="Tell the story of this item — brand, age, condition details..."></textarea>
      </div>

      <div class="input-row">
        <div class="form-group">
          <label class="form-label">CATEGORY *</label>
          <select class="form-input" name="category" required>
            <option value="">Select Category</option>
            <option>Clothing</option>
            <option>Accessories</option>
            <option>Footwear</option>
            <option>Outerwear</option>
            <option>Jewelry</option>
            <option>Vintage</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">CONDITION *</label>
          <select class="form-input" name="condition_" required>
            <option value="">Select Condition</option>
            <option>New with Tags</option>
            <option>Like New</option>
            <option>Good</option>
            <option>Fair</option>
          </select>
        </div>
      </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
      <div class="listing-section">
        <div class="listing-section-title">Price *</div>
        <div class="price-wrap">
          <span class="price-prefix">R</span>
          <input class="form-input" type="number" name="price" min="1" step="0.01" placeholder="0.00" required>
        </div>
        <div style="font-size:12px;color:var(--text-muted);margin-top:8px;"> Zero selling fees — keep 100%</div>
      </div>

      <div class="listing-section">
        <div class="listing-section-title">Delivery Options</div>
        <div class="delivery-grid">
          <label class="delivery-option"><input type="checkbox" name="delivery[]" value="Paxi"> Paxi Point</label>
          <label class="delivery-option"><input type="checkbox" name="delivery[]" value="PUDO">  PUDO Locker</label>
          <label class="delivery-option"><input type="checkbox" name="delivery[]" value="Aramex">  Aramex</label>
          <label class="delivery-option"><input type="checkbox" name="delivery[]" value="Collection">  Collection</label>
        </div>
      </div>
    </div>

    <div class="escrow-notice" style="margin-top:20px;">🛡 Your sale is protected by <strong>South African Escrow</strong>. Funds are only released once the buyer confirms delivery.</div>

    <div style="margin-top:24px;">
      <button class="btn btn-primary btn-lg" type="submit" style="max-width:400px;">Submit Listing for Review →</button>
      <div style="font-size:12px;color:var(--text-muted);margin-top:8px;">Your listing will go live once approved by our admin team.</div>
    </div>
  </form>
  <?php endif; ?>
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