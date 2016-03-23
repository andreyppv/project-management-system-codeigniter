<?php

require_once("__freshbooksinit.php");
foreach (getTasks()[1]['tasks']['task'] as $task) {
	echo "<option value='".$task['task_id']."'>".$task['name']."</option>";
}

?>