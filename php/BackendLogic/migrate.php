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

// tblCart
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
echo "✔  tblCart — ready.\n\n";

$conn->close();
echo "✔  Migration complete. <a href='../../html/home.php'>→ Go to site</a>\n";
echo "</pre>";