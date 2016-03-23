<?php

function refreshTokens(){
	$client_id = "97_b164tzz0v88cgkw4cwc0gsg8gsk88ogg4scg4c04g0kgcwco8";
	$client_secret = "5ob1yb3nwxogcg4488wocwko8w8cc480kw44wc8owc88sowckw";

    $db = new PDO("mysql:host=localhost;dbname=admin_freelancer", "freelancer_db", "t8Z6MxzvB?1bipjc");

    $users = $db->query("SELECT * FROM oauth")->fetchAll();
    foreach($users as $user){
        foreach($user as $key => $value){
            $$key = $value;
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://webapi.timedoctor.com/oauth/v2/token?client_id=".$client_id."&client_secret=".$client_secret."&grant_type=refresh_token&refresh_token=" . $refresh_token);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $response = json_decode(curl_exec($ch), true);

        if(empty($response['error'])){
            $new_access_token = $response['access_token'];
            $new_expires_in = $response['expires_in'];
            $new_token_type = $response['token_type'];
            $new_refresh_token = $response['refresh_token'];

            $prepared = $db->prepare("UPDATE oauth set access_token = ? WHERE id = ?");
            $prepared->execute(array($new_access_token, $id));
            $prepared = $db->prepare("UPDATE oauth set refresh_token = ? WHERE id = ?");
            $prepared->execute(array($new_refresh_token, $id));
            $prepared = $db->prepare("UPDATE oauth set expiresOn = ? WHERE id = ?");
            $prepared->execute(array(date("Y-m-d h:i:s A", strtotime("+2 hours")), $id));
        }
    }
}
refreshTokens();
?>