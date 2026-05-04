<!---session management --->
<?php
session_start();
$loggedIn = isset($_SESSION['userID']);
$initials = $loggedIn 
    ? strtoupper(substr($_SESSION['firstName'],0,1).substr($_SESSION['lastName'],0,1)) 
    : '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Contact Us – Past Times</title>
  <link rel="stylesheet" href="../css/styles.css">
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
</head>
<body>

<!-- NAVBAR — adaptive navigation bar -->

<nav class="navbar">
  <div class="navbar-brand" onclick="location.href='home.php'">
    <div class="logo-icon">🏠︎</div>
    <span style="font-family:var(--font-display);font-size:16px;font-weight:700;">Past Times</span>
  </div>

  <div class="navbar-nav">
    <a class="nav-link" href="home.php">Explore</a>
    <a class="nav-link" href="about.php">About</a>
    <?php if ($loggedIn): ?>
      <a class="nav-link" href="favorites.php">Favourites</a>
    <?php endif; ?>
    <a class="nav-link active" href="contactUs.php">Contact</a>
  </div>

  <div class="navbar-actions">
    <?php if ($loggedIn): ?>

      <div class="icon-btn" onclick="location.href='favorites.php'">🛒</div>
      <div class="icon-btn">🔔</div>
      <div class="avatar-btn" onclick="location.href='dashboard.php'" title="My Dashboard"><?= $initials ?></div>


    <?php else: ?>

      <a href="../php/AuthSystem/login.php" class="btn btn-secondary btn-sm" style="margin-right:8px;">
        Log In
      </a>
      <a href="../php/AuthSystem/register.php" class="btn btn-primary btn-sm">
        Sign Up
      </a>

    <?php endif; ?>
  </div>
</nav>


<div style="max-width:640px;margin:60px auto;padding:0 24px;">
  <h1 style="font-family:var(--font-display);font-size:40px;font-weight:700;margin-bottom:8px;">Get in Touch</h1>
  <p style="color:var(--text-muted);font-size:15px;margin-bottom:36px;">Have a question, dispute, or partnership enquiry? We're here to help.</p>
  <div style="background:white;border-radius:var(--radius-lg);padding:36px;border:1px solid var(--border-light);box-shadow:var(--shadow-sm);">
    
    <form onsubmit="submitContact(event)">
      <div class="input-row">
        <div class="form-group">
            <label class="form-label">First Name</label>
            <input class="form-input" type="text" placeholder="Thabo" required>
        </div>

        <div class="form-group">
            <label class="form-label">Last Name</label>
            <input class="form-input" type="text" placeholder="Mokoena" required>
        </div>
      </div>

      <div class="form-group">
            <label class="form-label">Email Address</label>
            <input class="form-input" type="email" placeholder="thabo@example.com" required>
        </div>

      <div class="form-group">
        <label class="form-label">Subject</label>
        <select class="form-input" title="subject">
          <option>General Enquiry</option>
          <option>Order Dispute</option>
          <option>Report a Listing</option>
          <option>Partnership</option>
          <option>Technical Issue</option>
        </select>
      </div>

      <div class="form-group">
            <label class="form-label">Message</label>
            <textarea class="form-input" rows="5" placeholder="Tell us how we can help..." required style="resize:vertical;"></textarea>
        </div>

      <button class="btn btn-primary btn-lg" type="submit">Send Message</button>
    </form>

  </div>

  <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;margin-top:32px;">
    <div style="text-align:center;padding:20px;background:white;border-radius:var(--radius);border:1px solid var(--border-light);">
    <div style="font-size:24px;margin-bottom:8px;">✉</div>
        <div style="font-weight:600;font-size:14px;margin-bottom:6px;">Email</div>
        <div style="font-size:12px;color:var(--text-muted);">support@pasttimes.co.za</div>
    </div>
    
    <div style="text-align:center;padding:20px;background:white;border-radius:var(--radius);border:1px solid var(--border-light);">
     <div style="font-size:24px;margin-bottom:8px;">☎</div> 
        <div style="font-weight:600;font-size:14px;margin-bottom:6px;">Phone</div>
        <div style="font-size:12px;color:var(--text-muted);">+27 11 000 1234</div>
    </div>
    <div style="text-align:center;padding:20px;background:white;border-radius:var(--radius);border:1px solid var(--border-light);">
    <div style="font-size:24px;margin-bottom:8px;">⚲</div> 
        <div style="font-weight:600;font-size:14px;margin-bottom:6px;">Office</div>
        <div style="font-size:12px;color:var(--text-muted);">Johannesburg, SA</div>
    </div>
  </div>
  
</div>

<footer class="footer">
    <div class="footer-bottom">
        <span class="footer-brand-name">Past Times</span>
        <div style="display:flex;gap:24px;font-size:13px;color:var(--text-muted);">
            <a href="about.html">About</a>
            <a href="contactUs.html">Contact</a>
            <span>Terms of Service</span>
        </div>

        <div class="footer-badges">PROUDLY SOUTH AFRICAN</div>
    </div>
</footer>

<script>
function submitContact(e) {
  e.preventDefault();
  alert('Message sent! We will get back to you within 24 hours.');
  e.target.reset();
}
</script>
</body>
</html>