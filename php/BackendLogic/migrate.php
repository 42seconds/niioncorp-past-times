<?php

include 'dbConn.php';
echo "<pre style='font-family:monospace;font-size:14px;'>";
echo "Past Times — Migration\n======================\n\n";

// tblMessages
$conn->query("
CREATE TABLE IF NOT EXISTS tblMessages (
    messageID   INT          NOT NULL AUTO_INCREMENT,
    senderID    INT          NOT NULL,
    receiverID  INT          NOT NULL,
    listingID   INT          DEFAULT NULL,
    body        TEXT         NOT NULL,
    isRead      TINYINT(1)   NOT NULL DEFAULT 0,
    createdAt   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (messageID),
    FOREIGN KEY (senderID)   REFERENCES tblUser(userID) ON DELETE CASCADE,
    FOREIGN KEY (receiverID) REFERENCES tblUser(userID) ON DELETE CASCADE,
    FOREIGN KEY (listingID)  REFERENCES tblListings(listingID) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
") or die("✘ tblMessages failed: " . $conn->error . "\n");
echo "✔  tblMessages — ready.\n";


//lets checkk if the table exists before we try to alter it, otherwise it will throw an error if we run this on a fresh database

$tableCheck = $conn->query("SHOW TABLES LIKE 'tblCart'");

if ($tableCheck && $tableCheck -> num_rows > 0){
    echo " tblCart already exists, skipping creation.\n";


    $colCheck = $conn->query("SHOW COLUMNS FROM tblCart LIKE 'quantity'");

    if ($colCheck && $colCheck -> num_rows == 0){
        echo " Adding missing 'quantity' column to tblCart...\n";
        $conn->query("ALTER TABLE tblCart ADD COLUMN quantity INT NOT NULL DEFAULT 1");
        echo "✔ 'quantity' column added.\n";
    } else {
        echo " 'quantity' column already exists, skipping alteration.\n";
    }


     // Check if note column exists
    $colCheck = $conn->query("SHOW COLUMNS FROM tblCart LIKE 'note'");
    if ($colCheck && $colCheck->num_rows == 0) {
        $conn->query("ALTER TABLE tblCart ADD COLUMN note TEXT DEFAULT NULL");
        echo "✔  Added 'note' column to tblCart\n";
    } else {
        echo "ℹ  'note' column already exists in tblCart\n";
    }
    
    // Check if unique constraint exists
    $indexCheck = $conn->query("SHOW INDEX FROM tblCart WHERE Key_name = 'unique_cart'");
    if ($indexCheck && $indexCheck->num_rows == 0) {
        // Remove any duplicates before adding unique constraint
        $dupCheck = $conn->query("
            SELECT userID, listingID, COUNT(*) as cnt 
            FROM tblCart 
            GROUP BY userID, listingID 
            HAVING cnt > 1
        ");


         if ($dupCheck && $dupCheck->num_rows > 0) {
            echo "⚠  Found duplicate cart entries. Removing duplicates (keeping most recent)...\n";
            $conn->query("
                DELETE c1 FROM tblCart c1
                INNER JOIN tblCart c2 
                WHERE c1.userID = c2.userID 
                AND c1.listingID = c2.listingID 
                AND c1.cartID < c2.cartID
            ");
            echo "✔  Duplicates removed.\n";
        }

         $conn->query("ALTER TABLE tblCart ADD UNIQUE KEY unique_cart (userID, listingID)");
        echo "✔  Added unique constraint to tblCart\n";
    } else {
        echo "ℹ  Unique constraint already exists on tblCart\n";
    }

} else {

 // Table doesn't exist - create it with all columns
    echo "ℹ  tblCart doesn't exist — creating new table...\n";
    $conn->query("
    CREATE TABLE IF NOT EXISTS tblCart (
        cartID      INT          NOT NULL AUTO_INCREMENT,
        userID      INT          NOT NULL,
        listingID   INT          NOT NULL,
        quantity    INT          NOT NULL DEFAULT 1,
        note        TEXT         DEFAULT NULL,
        addedAt     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (cartID),
        UNIQUE KEY unique_cart (userID, listingID),
        FOREIGN KEY (userID)    REFERENCES tblUser(userID)        ON DELETE CASCADE,
        FOREIGN KEY (listingID) REFERENCES tblListings(listingID) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ") or die("✘ tblCart failed: " . $conn->error . "\n");
    echo "✔  tblCart — created with quantity column.\n";
}

echo "\n✔  Migration complete. <a href='../../html/home.php'>→ Go to site</a>\n";
echo "</pre>";
?>