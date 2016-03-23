<?php
ini_set('display_errors', 1);

$db = new PDO("mysql:host=localhost;dbname=admin_freelancer", "freelancer_db", "t8Z6MxzvB?1bipjc");

function getclientusers($db){
    return $db->query("SELECT * FROM client_users")->fetchAll();
}



    foreach (getclientusers($db) as $client) {
        echo "<option value='".$client['client_users_id']."'>".$client['client_users_full_name']."</option>";
    }


?>