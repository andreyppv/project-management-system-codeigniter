<?php
$db = new PDO("mysql:host=localhost;dbname=admin_freelancer", "freelancer_db", "t8Z6MxzvB?1bipjc"); 
if (isset($_GET['projectid'])){
$prepared = $db->prepare("SELECT * FROM wireframe_page WHERE project_id = ?");
$prepared->execute(array($_GET['projectid']));
$rows = $prepared->fetchAll();
foreach($rows as $row){
echo "<option value='".$row['id']."'>".$row['page_title']."</option>";
}
}
?>