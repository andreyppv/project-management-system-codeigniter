<?php
ob_start();
ini_set('display_errors', 1);
$assignedTo = $_GET['assignedTo'];
$db = new PDO("mysql:host=localhost;dbname=admin_freelancer", "freelancer_db", "t8Z6MxzvB?1bipjc"); 
$prepared = $db->prepare("SELECT * FROM team_profile WHERE team_profile_id = ?");
$prepared->execute(array($assignedTo));
$row = $prepared->fetch(PDO::FETCH_ASSOC);
$fileName = $row["team_profile_avatar_filename"];
$link = getcwd() . "../files/avatars/".$fileName;
if (!isset($_GET['text'])){
    header('Content-type: image/jpeg');
    readfile("http://pms.isodeveloper.com/files/avatars/".$fileName);
}else{
    echo $row['team_profile_full_name'];
}
ob_flush();
?>