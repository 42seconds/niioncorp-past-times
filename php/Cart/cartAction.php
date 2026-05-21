
<?php
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['userID'])) { echo json_encode(['error'=>'not_logged_in']); exit; }
require_once '../BackendLogic/dbConn.php';
$userID=(int)$_SESSION['userID'];
$action=$_POST['action']??'';
$listingID=(int)($_POST['listingID']??0);
if(!$listingID){echo json_encode(['error'=>'invalid']);exit;}
// Sellers cannot add to cart or purchase
if(isset($_SESSION['role']) && in_array($_SESSION['role'],['seller','admin'])){
    echo json_encode(['error'=>'sellers_cannot_buy']); exit;
}
if($action==='add'){
    $s=$conn->prepare("SELECT sellerID FROM tblListings WHERE listingID=? AND status='approved'");
    $s->bind_param("i",$listingID);$s->execute();$l=$s->get_result()->fetch_assoc();$s->close();
    if(!$l){echo json_encode(['error'=>'not_available']);exit;}
    if((int)$l['sellerID']===$userID){echo json_encode(['error'=>'own_listing']);exit;}
    $ins=$conn->prepare("INSERT INTO tblCart (userID,listingID) VALUES (?,?) ON DUPLICATE KEY UPDATE addedAt=NOW()");
    $ins->bind_param("ii",$userID,$listingID);$ins->execute();$ins->close();
    $cnt=$conn->query("SELECT COUNT(*) c FROM tblCart WHERE userID=$userID")->fetch_assoc()['c'];
    echo json_encode(['action'=>'added','cartCount'=>(int)$cnt]);
}elseif($action==='remove'){
    $d=$conn->prepare("DELETE FROM tblCart WHERE userID=? AND listingID=?");
    $d->bind_param("ii",$userID,$listingID);$d->execute();$d->close();
    $cnt=$conn->query("SELECT COUNT(*) c FROM tblCart WHERE userID=$userID")->fetch_assoc()['c'];
    echo json_encode(['action'=>'removed','cartCount'=>(int)$cnt]);
}elseif($action==='update'){
    $note=htmlspecialchars(trim($_POST['note']??''));
    $u=$conn->prepare("UPDATE tblCart SET note=? WHERE userID=? AND listingID=?");
    $u->bind_param("sii",$note,$userID,$listingID);$u->execute();$u->close();
    echo json_encode(['action'=>'updated']);
}else{echo json_encode(['error'=>'unknown_action']);}
$conn->close();