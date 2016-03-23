<?php
 $db = new PDO("mysql:host=localhost;dbname=admin_freelancer", "freelancer_db", "t8Z6MxzvB?1bipjc");
 $projectid = $_GET['projectid'];
 $tasks = $db->prepare("SELECT * FROM tasks WHERE `tasks_project_id` = ?");
 $tasks->execute(array($projectid));
 foreach ($tasks->fetchAll() as $task) {
 	echo "<option value='".intval($task['tasks_id'])."'>".htmlentities($task['tasks_text'])."</option>";
 }
?>