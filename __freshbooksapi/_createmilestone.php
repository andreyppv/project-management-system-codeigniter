<?php
$db = new PDO("mysql:host=localhost;dbname=admin_freelancer", "freelancer_db", "t8Z6MxzvB?1bipjc");
foreach ($_POST as $key => $value) {
	$$key = $value;
}
$prepared = $db->prepare("INSERT INTO milestones (
                                          milestones_project_id,
                                          milestones_title,
                                          milestones_created_by,
                                          milestones_events_id,
                                          milestones_client_id
                                          )VALUES(
                                          ?,
                                          ?,
                                          ?,
                                          ?,
                                          ?)");
$prepared->execute(array($milestones_project_id, $milestones_title, $milestones_created_by, $milestones_events_id, $milestones_client_id));
print_r($prepared->errorInfo());
?>