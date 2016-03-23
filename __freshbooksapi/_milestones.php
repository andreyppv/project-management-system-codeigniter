<?php

$db = new PDO("mysql:host=localhost;dbname=admin_freelancer", "freelancer_db", "t8Z6MxzvB?1bipjc");
$projectid = $_GET['projectid'];
function getProjectMilestones($db, $projectid){
    $prepared = $db->prepare("SELECT * FROM milestones WHERE milestones_project_id = ?");
    $prepared->execute(array($projectid));
    if($prepared->rowCount() > 0){
        return $prepared->fetchAll();
    }else{
        return array();
    }
}

foreach (getProjectMilestones($db, $projectid) as $milestone) {
	echo "<option value='".$milestone['milestones_id']."'>".$milestone['milestones_title']."</option>";
}

?>