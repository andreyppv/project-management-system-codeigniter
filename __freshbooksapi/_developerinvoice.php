<?php
ini_set('display_errors', 1);
$db = new PDO("mysql:host=localhost;dbname=admin_freelancer", "freelancer_db", "t8Z6MxzvB?1bipjc");
if(isset($_GET['invoiceid'])){
	$status = "";

	/*switch(intval($_GET['newstatus'])){
		case 1:
			$status = "Approved, Pending Payment";
			break;

		case 2:
			$status = "Paid"
			break;

		default:
			$status = "Pending Approval";
			break;
	}*/
	if(isset($_GET['newstatus'])){
		if($_GET['newstatus'] == 1){
			$status = "Approved, Pending Payment";
		}elseif($_GET['newstatus'] == 2){
			$status = "Paid";
		}else{
			$status = "Pending Approval";
		}
	}else{
		$status = "Pending Approval";
	}

	$prepared = $db->prepare("UPDATE developer_invoices SET status = ? WHERE id = ?");
	$prepared->execute(array($status, $_GET['invoiceid']));
	echo "Invoice updated";
}
?>