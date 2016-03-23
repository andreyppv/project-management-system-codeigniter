<?php
ini_set('display_errors', 1);

$db = new PDO("mysql:host=localhost;dbname=admin_freelancer", "freelancer_db", "t8Z6MxzvB?1bipjc");

if(isset($_GET['projectid'])){ 
    $projectid = $_GET['projectid'];
}

function getteammembers($db, $projectid){
    /*project_members_team_id = Profile ID*/
    $prepared = $db->prepare("SELECT `project_members_team_id` FROM project_members WHERE project_members_project_id = ?");
    $prepared->execute(array($projectid));
    if($prepared->rowCount() > 0){
        $teamMembers = $prepared->fetchAll();
        return $teamMembers;
    }else{
        return array(); /*Blank*/
    }
}

function getteammembers2($db){
    return $db->query("SELECT * FROM team_profile")->fetchAll();
}

function getTeamMember($db, $teamMemberID){
    $prepared = $db->prepare("SELECT * FROM team_profile WHERE team_profile_id = ?");
    $prepared->execute(array($teamMemberID));
    return $prepared->fetch(PDO::FETCH_ASSOC);
}

if(!isset($_GET['all'])){
    foreach (getteammembers($db, $projectid) as $teammemberid) {
    	$teammember = getTeamMember($db, $teammemberid[0]);
    	echo "<option value='".$teammember['team_profile_id']."'>".$teammember['team_profile_full_name']."</option>";
    }
}else{
    foreach (getteammembers2($db) as $teammember) {
        echo "<option value='".$teammember['team_profile_id']."'>".$teammember['team_profile_full_name']."</option>";
    }
}

?>