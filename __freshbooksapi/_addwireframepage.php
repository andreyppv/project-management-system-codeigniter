<?php 
$db = new PDO("mysql:host=localhost;dbname=admin_freelancer", "freelancer_db", "t8Z6MxzvB?1bipjc"); 

if (isset($_GET['wireframeid'], $_GET['pagelink'])){
$prepared = $db->prepare("SELECT * FROM wireframe_page WHERE id = ?");
$prepared->execute(array($_GET['wireframeid']));
$row = $prepared->fetch(PDO::FETCH_ASSOC);
$projectid = $row['project_id'];

$prepared = $db->prepare("SELECT * FROM wireframe_page WHERE project_id = ? AND link = ?");
$prepared->execute(array($projectid, $_GET['pagelink']));
if($prepared->rowCount() == 0){
   $prepared = $db->prepare("INSERT INTO wireframe_page VALUES (NULL, ?, ?, ?)");
$prepared->execute(array($projectid, "", $_GET['pagelink']));

$prepared = $db->prepare("SELECT * FROM wireframe_page WHERE project_id = ? ORDER BY id DESC");
$prepared->execute(array($projectid));
$row = $prepared->fetch(PDO::FETCH_ASSOC);
echo "http://pms.isodeveloper.com/admin/wireframeexpanded/".$row['id'];

}else{
    $row = $prepared->fetch(PDO::FETCH_ASSOC);
    echo "http://pms.isodeveloper.com/admin/wireframeexpanded/".$row['id'];
}

}
?>