<?php
require_once("__freshbooksinit.php");

if(!isset($_GET['profileid'])){
	die("Missing profileid");
	exit;
}else{
	$profileid = intval($_GET['profileid']);
}

if(isset($_GET['rate'])){
	echo getRateFromStaffId(getFreshbooksStaffIdFromId($profileid));
}elseif(isset($_GET['yesterdayhours'])){
	echo getYesterdaysHours(getFreshbooksStaffIdFromId($profileid));
}elseif(isset($_GET['todayhours'])){
	echo getTodaysHours(getFreshbooksStaffIdFromId($profileid));
}elseif(isset($_GET['weekhours'])){
	echo getWeekHours(getFreshbooksStaffIdFromId($profileid));
}elseif(isset($_GET['skills'])){
	echo getSkillsFromId($profileid);
}elseif(isset($_GET['amountpaid'])){
	echo getTotalPaid(getFreshbooksStaffIdFromId($profileid));
}
?>