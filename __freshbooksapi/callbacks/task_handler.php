<?php
require_once("../../lib/FreshBooksRequest.php");
$domain = 'isodevelopers'; // https://your-subdomain.freshbooks.com/
$token = '84c2ca6356fc7f358cc8d23089fe4197'; // your api token found in your account
FreshBooksRequest::init($domain, $token);

header("HTTP/1.1 200 OK");
$file = fopen("task_responses.txt","a");

$headers = apache_request_headers();
$headers_ = "";
foreach ($headers as $header => $value) {
    $headers_ .= "$header: $value\n";
}
$headers_ .= "POST VALUES:";
foreach ($_POST as $header => $value) {
    $headers_ .= "$header: $value\n";
}

fwrite($file,$headers_);
fclose($file);

function makeFreshbooksRequest($apiMethod, $params){
	$fb = new FreshBooksRequest($apiMethod);
	$fb->post($params);
	$fb->request();
	if($fb->success()){ return array(true, $fb->getResponse()); }else{ return array(false, $fb->getResponse()); }
}

switch($_POST['name']){
	case "callback.verify":
		makeFreshbooksRequest("callback.verify", array(
		"callback" =>
			array(
				"callback_id" => $_POST['object_id'],
				"verifier" => $_POST['verifier']
			)
		));
	break;

	/*Handle other api methods*/
}
?>