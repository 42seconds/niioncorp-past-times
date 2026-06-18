<?php

include 'dbConn.php';

$reset = isset($_GET['reset']) && $_GET['reset'] === '1'
      && isset($_GET['confirm']) && $_GET['confirm'] === 'yes';

echo "<pre style='font-family:monospace;font-size:14px;'>";
echo "Past Times — Table Setup\n";
echo "========================\n\n";

// OPTIONAL HARD RESET (both params required) 
if ($reset) {
    echo "⚠  RESET requested — dropping existing tables...\n";
    $conn->query("DROP TABLE IF EXISTS tblOrderItems");
     $conn->query("DROP TABLE IF EXISTS tblCart");
    $conn->query("DROP TABLE IF EXISTS tblOrders");
    $conn->query("DROP TABLE IF EXISTS tblListings");
    $conn->query("DROP TABLE IF EXISTS tblUser");
    $conn->query("DROP TABLE IF EXISTS tblFavourites");
   
    $conn->query("DROP TABLE IF EXISTS tblMessages");
    echo "✔  All tables dropped.\n\n";
}

//  tblUser 
$conn->query("
CREATE TABLE IF NOT EXISTS tblUser (
    userID       INT          NOT NULL AUTO_INCREMENT,
    username     VARCHAR(50)  NOT NULL UNIQUE,
    firstName    VARCHAR(50)  NOT NULL,
    lastName     VARCHAR(50)  NOT NULL,
    email        VARCHAR(100) NOT NULL UNIQUE,
    passwordHash VARCHAR(255) NOT NULL,
    role         ENUM('admin','customer','seller') NOT NULL DEFAULT 'customer',
    status       ENUM('pending','verified')        NOT NULL DEFAULT 'pending',
    createdAt    DATE,
    PRIMARY KEY (userID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
") or die("✘ tblUser failed: " . $conn->error . "\n");
echo "✔  tblUser — ready.\n";

// tblListings 
$conn->query("
CREATE TABLE IF NOT EXISTS tblListings (
    listingID   INT            NOT NULL AUTO_INCREMENT,
    sellerID    INT            NOT NULL,
    title       VARCHAR(150)   NOT NULL,
    description TEXT,
    category    VARCHAR(50),
    condition_  VARCHAR(50),
    price       DECIMAL(10,2)  NOT NULL DEFAULT 0.00,
    delivery    VARCHAR(150),
    imagePath   VARCHAR(255)   DEFAULT NULL,
    status      ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    createdAt   DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (listingID),
    FOREIGN KEY (sellerID) REFERENCES tblUser(userID) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
") or die("✘ tblListings failed: " . $conn->error . "\n");
echo "✔  tblListings — ready.\n";

// tblOrders 
$conn->query("
CREATE TABLE IF NOT EXISTS tblOrders (
    orderID       INT            NOT NULL AUTO_INCREMENT,
    buyerID       INT            NOT NULL,
    listingID     INT            NOT NULL,
    sellerID      INT            NOT NULL,
    quantity      INT            NOT NULL DEFAULT 1,
    totalPrice    DECIMAL(10,2)  NOT NULL,
    deliveryMethod VARCHAR(50),
    deliveryAddress TEXT,
    status        ENUM('pending','paid','shipped','delivered','cancelled','refunded')
                  NOT NULL DEFAULT 'pending',
    paymentRef    VARCHAR(100)   DEFAULT NULL,
    createdAt     DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updatedAt     DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP
                  ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (orderID),
    FOREIGN KEY (buyerID)   REFERENCES tblUser(userID)     ON DELETE CASCADE,
    FOREIGN KEY (listingID) REFERENCES tblListings(listingID) ON DELETE CASCADE,
    FOREIGN KEY (sellerID)  REFERENCES tblUser(userID)     ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
") or die("✘ tblOrders failed: " . $conn->error . "\n");
echo "✔  tblOrders — ready.\n";

//  tblOrderItems (future bundled orders) 
$conn->query("
CREATE TABLE IF NOT EXISTS tblOrderItems (
    itemID      INT           NOT NULL AUTO_INCREMENT,
    orderID     INT           NOT NULL,
    listingID   INT           NOT NULL,
    price       DECIMAL(10,2) NOT NULL,
    PRIMARY KEY (itemID),
    FOREIGN KEY (orderID)   REFERENCES tblOrders(orderID)      ON DELETE CASCADE,
    FOREIGN KEY (listingID) REFERENCES tblListings(listingID)  ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
") or die("✘ tblOrderItems failed: " . $conn->error . "\n");
echo "✔  tblOrderItems — ready.\n\n";


//  tblFavourites 
$conn->query("
CREATE TABLE IF NOT EXISTS tblFavourites (
    favID       INT      NOT NULL AUTO_INCREMENT ,
    userID      INT      NOT NULL,
    listingID   INT      NOT NULL,
    createdAt   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (favID),
    UNIQUE KEY unique_fav (userID, listingID),
    FOREIGN KEY (userID)    REFERENCES tblUser(userID)     ON DELETE CASCADE,
    FOREIGN KEY (listingID) REFERENCES tblListings(listingID) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
") or die("✘ tblFavourites failed: " . $conn->error . "\n");
echo "✔  tblFavourites — ready.\n\n";


// tblCart
$conn->query("
CREATE TABLE IF NOT EXISTS tblCart (
    cartID      INT NOT NULL AUTO_INCREMENT,
    userID      INT NOT NULL,
    listingID   INT NOT NULL,
    quantity    INT NOT NULL DEFAULT 1,
    note        TEXT DEFAULT NULL,
    addedAt     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (cartID),

    UNIQUE KEY unique_cart_item (userID, listingID),

    FOREIGN KEY (userID)
        REFERENCES tblUser(userID)
        ON DELETE CASCADE,

    FOREIGN KEY (listingID)
        REFERENCES tblListings(listingID)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
") or die("✘ tblCart failed: " . $conn->error . "\n");

echo "✔  tblCart — ready.\n\n";



// SEED tblUser only if empty 
$count = $conn->query("SELECT COUNT(*) c FROM tblUser")->fetch_assoc()['c'];

if ($count > 0) {
    echo "ℹ  tblUser already has $count rows — skipping seed. Pass ?reset=1&confirm=yes to wipe and re-seed.\n";
} else {
    echo "ℹ  tblUser is empty — seeding from userData.txt...\n";

    $dataFile = realpath(__DIR__ . '/../../userData.txt');
    $dataFile = $dataFile ? str_replace("\\", "/", $dataFile) : '';

    if (!$dataFile || !file_exists($dataFile)) {
        echo "✘  userData.txt not found at: " . __DIR__ . "/../../userData.txt\n";
    } else {
        $loadSQL = "LOAD DATA LOCAL INFILE '$dataFile'
                    INTO TABLE tblUser
                    FIELDS TERMINATED BY '|'
                    LINES TERMINATED BY '\n'
                    (userID, username, firstName, lastName, email, passwordHash, role, status, createdAt)";

        if ($conn->query($loadSQL)) {
            echo "✔  Seeded " . $conn->affected_rows . " rows via LOAD DATA.\n";
        } else {
            echo "⚠  LOAD DATA unavailable — using INSERT fallback...\n";
            $stmt = $conn->prepare(
                "INSERT IGNORE INTO tblUser
                 (userID,username,firstName,lastName,email,passwordHash,role,status,createdAt)
                 VALUES (?,?,?,?,?,?,?,?,?)"
            );
            $inserted = 0;
            foreach (file($dataFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                $c = explode('|', trim($line));
                if (count($c) < 9) continue;
                $stmt->bind_param("issssssss",
                    $c[0],$c[1],$c[2],$c[3],$c[4],$c[5],$c[6],$c[7],$c[8]);
                $stmt->execute();
                $inserted++;
            }
            $stmt->close();
            echo "✔  Inserted $inserted rows via prepared statements.\n";
        }
    }
}

$conn->close();
echo "\n✔  All done. <a href='../../html/home.php'>→ Go to site</a>\n";
echo "</pre>";
