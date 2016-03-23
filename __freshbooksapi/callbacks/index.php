<?php
require_once("../../lib/FreshBooksRequest.php");
$domain = 'isodevelopers'; // https://your-subdomain.freshbooks.com/
$token = '84c2ca6356fc7f358cc8d23089fe4197'; // your api token found in your account
FreshBooksRequest::init($domain, $token);

function makeFreshbooksRequest($apiMethod, $params){
	$fb = new FreshBooksRequest($apiMethod);
	$fb->post($params);
	$fb->request();
	if($fb->success()){ return array(true, $fb->getResponse()); }else{ return array(false, $fb->getResponse()); }
}

/*print_r(makeFreshbooksRequest("callback.create", array(
	"callback" => array(
			"event" => "task",
			"uri" => "http://pms.isodeveloper.com/__freshbooksapi/callbacks/task_handler.php"
		)
)));

print_r(makeFreshbooksRequest("callback.create", array(
	"callback" => array(
			"event" => "time_entry",
			"uri" => "http://pms.isodeveloper.com/__freshbooksapi/callbacks/timeentry_handler.php"
		)
)));*/
?>