<?php
$db = new PDO("mysql:host=localhost;dbname=admin_freelancer", "freelancer_db", "t8Z6MxzvB?1bipjc");
$clients = $db->query("SELECT * FROM clients")->fetchAll();
foreach ($clients as $client) {
	echo "<option value='".$client['clients_id']."'>".htmlentities($client['clients_company_name'])."</option>";
}
?>