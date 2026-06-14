<?php
session_start();
$loggedIn = isset($_SESSION['userID']);
$initials = $loggedIn
    ? strtoupper(substr($_SESSION['firstName'], 0, 1) . substr($_SESSION['lastName'], 0, 1))
    : '';
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About – Past Times</title>
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="../css/responsive.css">

    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
</head>

<body>

    <nav class="navbar">
        <div class="navbar-brand" onclick="location.href='home.php'">
            <div class="logo-icon">🏠︎</div>
            <span style="font-family:var(--font-display);font-size:16px;font-weight:700;">Past Times</span>
        </div>
        <button class="navbar-toggle" aria-label="Toggle menu" aria-expanded="false"
            onclick="
            var nav = this.parentElement.querySelector('.navbar-nav');
            var open = nav.classList.toggle('open');
            this.setAttribute('aria-expanded', open);">
            <span></span>
            <span></span>
            <span></span>
        </button>


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
    <div style="max-width:800px;margin:60px auto;padding:0 24px;">
        <div style="font-size:11px;font-weight:600;letter-spacing:0.15em;text-transform:uppercase;color:var(--primary);margin-bottom:12px;">Our Story</div>
        <h1 style="font-family:var(--font-display);font-size:48px;font-weight:700;line-height:1.15;margin-bottom:24px;">Preserving the past,<br>
            <em style="color:var(--primary);">styling the future.</em>
        </h1>
        <p style="font-size:16px;color:var(--text-muted);line-height:1.8;margin-bottom:32px;">Past Times was born from a simple belief: that every piece of clothing has a story worth telling. Founded in 2023 in South Africa, we created a space where pre-loved fashion meets conscious consumerism — a curated marketplace for people who care about style <em>and</em> sustainability.</p>

        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:24px;margin-bottom:48px;">
            <div style="background:white;border-radius:var(--radius);padding:28px;border:1px solid var(--border-light);text-align:center;">
                <div style="font-family:var(--font-display);font-size:40px;font-weight:700;color:var(--primary);">12k+</div>
                <div style="font-size:13px;color:var(--text-muted);margin-top:6px;text-transform:uppercase;letter-spacing:0.06em;">Items Re-homed</div>
            </div>

            <div style="background:white;border-radius:var(--radius);padding:28px;border:1px solid var(--border-light);text-align:center;">
                <div style="font-family:var(--font-display);font-size:40px;font-weight:700;color:var(--teal);">4.8t</div>
                <div style="font-size:13px;color:var(--text-muted);margin-top:6px;text-transform:uppercase;letter-spacing:0.06em;">CO₂ Saved</div>
            </div>

            <div style="background:white;border-radius:var(--radius);padding:28px;border:1px solid var(--border-light);text-align:center;">
                <div style="font-family:var(--font-display);font-size:40px;font-weight:700;color:var(--dark);">3.2k</div>
                <div style="font-size:13px;color:var(--text-muted);margin-top:6px;text-transform:uppercase;letter-spacing:0.06em;">Active Sellers</div>
            </div>
        </div>

        <h2 style="font-family:var(--font-display);font-size:28px;font-weight:700;margin-bottom:16px;">Why Past Times?</h2>
        <p style="font-size:15px;color:var(--text-muted);line-height:1.8;margin-bottom:16px;">The fashion industry is one of the world's largest polluters. By choosing pre-loved, you reduce demand for new production, cut textile waste, and give beautiful items a second life. Our platform makes this process seamless, safe (every transaction is escrow-protected via Ozow), and stylish.</p>
        <p style="font-size:15px;color:var(--text-muted);line-height:1.8;">We are proudly South African, serving buyers and sellers across Johannesburg, Cape Town, Durban, Pretoria and beyond — with delivery via PUDO lockers, Paxi Points, and Aramex Express.</p>

        <div style="margin-top:48px;text-align:center;">
            <button class="btn btn-primary btn-lg" style="width:auto;" onclick="location.href='home.php'">Start Shopping</button>
        </div>
    </div>

   
<footer class="footer">
    <div class="footer-bottom">
        <span class="footer-brand-name">Past Times</span>
        <div style="display:flex;gap:24px;font-size:13px;color:var(--text-muted);">
            <a href="about.php">About</a>
            <a href="contactUs.php">Contact</a>
            <span>Terms of Service</span>
        </div>

        <div class="footer-badges">PROUDLY SOUTH AFRICAN</div>
    </div>
</footer>

</body>

</html>