<?php
ini_set('display_errors', 0);
function Size($bytes)
	{ 

    if ($bytes > 0)
    {
        $unit = intval(log($bytes, 1024));
        $units = array('B', 'KB', 'MB', 'GB');

        if (array_key_exists($unit, $units) === true)
        {
            return sprintf('%d %s', $bytes / pow(1024, $unit), $units[$unit]);
        }
    }

    return $bytes;
	}
if(isset(
	$_GET['files_project_id'], 
	$_GET['files_client_id'], 
	$_GET['files_events_id'], 
	$_GET['files_uploaded_by'], 
	$_GET['files_uploaded_by_id'], 
	$_GET['files_foldername'], 
	$_GET['files_extension'], 
	$_GET['files_size'])){
	/*Upload file*/

	$files_project_id = $_GET['files_project_id'];
	$files_client_id = $_GET['files_client_id'];
	$files_events_id = $_GET['files_events_id'];
	$files_uploaded_by = $_GET['files_uploaded_by']; 
	$files_uploaded_by_id = $_GET['files_uploaded_by_id']; 
	$files_foldername = $_GET['files_foldername']; 
	$files_extension = $_GET['files_extension'];
	$files_size = $_GET['files_size'];

	chdir("../../files/projects/");
	
	if(is_dir(intval($files_project_id))){
		chdir(intval($files_project_id));
	}else{
		mkdir(intval($files_project_id));
		chdir(intval($files_project_id));
	}

	$db = new PDO("mysql:host=localhost;dbname=admin_freelancer", "freelancer_db", "t8Z6MxzvB?1bipjc");
	
	$randomNumber = mt_rand(9999, 99999);
	$folderName = md5($randomNumber);

	mkdir($folderName); /*Makes dir for upload*/
	chdir($folderName); /*Move to active dir for upload process*/

	$name = preg_replace('/[^a-zA-Z0-9_.]/', '_', $_FILES['file']['name']);
	$ext= pathinfo($name, PATHINFO_EXTENSION);
	$tmp_name = $_FILES['file']['tmp_name'];
	$type = $_FILES['file']['type'];
	$size=Size($_FILES["file"]["size"]);
	move_uploaded_file($tmp_name, $name);

	$link = "http://pms.isodeveloper.com/files/projects/".$files_project_id."/".$folderName."/".$name;
	$prepared = $db->prepare("INSERT INTO files VALUES (NULL,
		?,0, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,?,?)");
	$prepared->execute(array(
		$files_project_id,
		$files_client_id,
		$files_uploaded_by,
		$files_uploaded_by_id,
		$name,
		$name,
		"",
		$_FILES["file"]["size"],
		$size,
		date("Y-m-d"),
		date ("H:i:s"),
		$folderName,
		$ext,
		$files_events_id,
		1,
		null
		));
	
	
	echo $link;
	
	
	
}
?>