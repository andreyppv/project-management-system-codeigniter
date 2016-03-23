<?php
$db = new PDO("mysql:host=localhost;dbname=admin_freelancer", "freelancer_db", "t8Z6MxzvB?1bipjc");
$projects = $db->query("SELECT * FROM projects")->fetchAll();
foreach ($projects as $project) {
	echo '<option value="'.$project['projects_id'].'">'.$project['projects_title'].'</option>';
}
?>