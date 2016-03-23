<?php
function statusIDToText($id){
	$types = array(
		"Uncategorized Lead", 
		"Lead",
		"On Going",
		"Lost Lead",
		"Post Sale",
		"In-Progress",
		"Behind Schedule",
		"On Hold",
		"Completed");
	return $types[$id];
}

$db = new PDO("mysql:host=localhost;dbname=admin_freelancer", "freelancer_db", "t8Z6MxzvB?1bipjc");
$leads = $db->query("SELECT * FROM leads")->fetchAll();
foreach ($leads as $lead) {
	echo '<tr>
	    <td>'.$lead['id'].'</td>
	    <td>Unknown</td>
	    <td>'.$lead['name'].'</td>
	    <td>Unknown</td>
	    <td>'.$lead['email'].'</td>
	    <td>0</td>
	    <td>'.date('m-d-Y h:i:s', $lead['timestamp']).'</td>
	    <td>Uncategorised</td>
	    <td><a href="/admin/sales/view/13" class="btn btn-primary">Lead Details</a></td>
	</tr>
	<tr>';
}

function getClientRow($db, $clientID){
	$prepared = $db->prepare("SELECT * FROM clients WHERE clients_id = ?");
	$prepared->execute(array($clientID));
	return $prepared->fetch(PDO::FETCH_ASSOC);
}

function getClient($db, $clientID){
	$prepared = $db->prepare("SELECT * FROM client_users WHERE client_users_id = ?");
	$prepared->execute(array($clientID));
	return $prepared->fetch(PDO::FETCH_ASSOC);
}

function getProjectCount($db, $clientID){
	$prepared = $db->prepare("SELECT * FROM projects WHERE projects_clients_id = ?");
	$prepared->execute(array($clientID));
	return $prepared->rowCount();
}

$projectsLeads = $db->query("SELECT * FROM projects WHERE status <= 4");
foreach ($projectsLeads as $projectlead) {
	$clientRow = getClientRow($db, $projectlead['projects_clients_id']);
	$client = getClient($db, $projectlead['projects_clients_id']);
		echo '<tr>
	    <td>'.$projectlead['projects_id'].'</td>
	    <td>'.$clientRow['clients_company_name'].'</td>
	    <td>'.$client['client_users_full_name'].'</td>
	    <td>'.$projectlead['projects_description'].'</td>
	    <td>'.$client['client_users_email'].'</td>
	    <td>'.getProjectCount($db, $projectlead['projects_clients_id']).'</td>
	    <td>N/A</td>
	    <td>'.statusIDToText($projectlead['status']-1).'</td>
	    <td><a href="/admin/sales/view/'.$projectlead['projects_id'].'/project" class="btn btn-primary">Lead Details</a></td>
	</tr>
	<tr>';
}

?>