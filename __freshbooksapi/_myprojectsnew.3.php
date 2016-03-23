<?php
ini_set('display_errors', 1);
$db = new PDO("mysql:host=localhost;dbname=admin_freelancer", "freelancer_db", "t8Z6MxzvB?1bipjc");
$userid = isset($_GET['userid']) ? intval($_GET['userid']) : 0;

function getprojects($db, $userid){
	if(!isAdmin($db, $userid)){
		$prepared = $db->prepare("SELECT `project_members_project_id` FROM project_members WHERE project_members_team_id = ?");
		$prepared->execute(array($userid));
		if($prepared->rowCount() > 0){
			return $prepared->fetchAll();
		}else{
			return array();
		}
	}else{
		$query = $db->query("SELECT `projects_id` FROM projects");
		if($query->rowCount() > 0){
			return $query->fetchAll();
		}else{
			return array();
		}
	}
}

function isAdmin($db, $userid){
	$prepared = $db->prepare("SELECT 1 FROM team_profile WHERE team_profile_id = ? AND team_profile_groups_id = ?");
	$prepared->execute(array($userid, 1));
	$row = $prepared->fetch(PDO::FETCH_ASSOC);

	if( !empty($row) ){
		return true;
	}else{
		return false;
	}
}

function getProject($db, $projectid){
	$result = array();
	$prepared = $db->prepare("
		SELECT
			p.*,
			(
				SELECT m.project_members_team_id
				FROM project_members m
				WHERE m.project_members_project_id=p.projects_id
				      AND m.project_members_project_lead='yes'
			) lead_id
		FROM projects p
		WHERE p.projects_id = ?
	");
	$prepared->execute(array($projectid));
	$result = $prepared->fetch(PDO::FETCH_ASSOC);

	if($result['lead_id'])
	{
		$prepared = $db->prepare("
			SELECT
				team_profile_full_name lead_name,
				team_profile_telephone lead_phone
			FROM team_profile
			WHERE team_profile_id = ?
		");
		$prepared->execute(array($result['lead_id']));
		$result += $prepared->fetch(PDO::FETCH_ASSOC);
	}
	else
	{
		$result['lead_name'] = $result['lead_phone'] = '---';
	}

	return $result;
}

function getClient($db, $clientid){
	$prepared = $db->prepare("SELECT * FROM clients WHERE clients_id = ?");
	$prepared->execute(array($clientid));
	return $prepared->fetch(PDO::FETCH_ASSOC);
}

function getteammembers($db, $projectid){
	/*project_members_team_id = Profile ID*/
	$prepared = $db->prepare("SELECT `project_members_team_id` FROM project_members WHERE project_members_project_id = ?");
	$prepared->execute(array($projectid));
	if($prepared->rowCount() > 0){
		$teamMembers = $prepared->fetchAll();
		return $teamMembers;
	}else{
		return array(); /*Blank*/
	}
}

function getTasks($db, $projectID, $type){
	$prepared = $db->prepare("SELECT * FROM tasks WHERE tasks_project_id = ? AND tasks_status = ?");
	$prepared->execute(array($projectID, $type));
	if($prepared->rowCount() > 0){
		return $prepared->fetchAll();
	}else{
		return array();
	}
}

function getTasksCount($db, $projectID, $type){
	switch($type) {
		case 'uncompleted':
			$sql = "SELECT COUNT(*) FROM tasks WHERE tasks_project_id = ".$projectID." AND tasks_status != 'completed'";
			break;
		case 'needing_rewiew':
			$sql = "SELECT COUNT(*) FROM tasks WHERE tasks_project_id = ".$projectID." AND tasks_status = 'completed' AND memo!=''";
			break;
		case 'bugs':
			$sql = "SELECT COUNT(*) FROM bugs WHERE bugs_project_id = ".$projectID;
			break;
		case 'unread_messages':
			$sql = "
				SELECT COUNT(*)
				FROM messages m
				WHERE m.messages_project_id = ".$projectID."
				      AND m.messages_deleted = 0
				      AND NOT EXISTS (select 1 from messages_replies r where r.messages_replies_message_id=m.messages_id)
			";
			break;
		default:
			$sql = "SELECT COUNT(*) FROM tasks WHERE tasks_project_id = ".$projectID." AND tasks_status = '".$type."'";
			break;
	}
	$prepared = $db->query($sql);
	return $prepared->fetchColumn(0);
}

//This show the total amount from all the task that have been added to the project
function getTotalBilliable($db, $projectid){
	$prepared = $db->prepare("SELECT billingcategory, estimatedhours FROM tasks WHERE tasks_project_id = ?");
	$prepared->execute(array($projectid));
	$totalcost = 0;

	$costperhour = array(
        1 => 75, 2 => 75,
        3 => 95,
        4 => 125, 5 => 125,
        6 => 65,
        7 => 125
	);
	while($row = $prepared->fetch(PDO::FETCH_ASSOC)) {
		$cost = isset($costperhour[$row['billingcategory']]) ? $costperhour[$row['billingcategory']] : 125;
		$totalcost += $cost * $row['estimatedhours'];
	}
	return number_format($totalcost, 0, '.', '&thinsp;');
}

$index = 1;
$html = "";
foreach(getprojects($db, $userid) as $projectrow){
	if(!isAdmin($db, $userid)){
		$project = getProject($db, $projectrow['project_members_project_id']);
	}else{
		$project = getProject($db, $projectrow['projects_id']);
	}

	if(empty($project)){
		continue;
	}
	
	$client = getClient($db, $project['projects_clients_id']);

	$class_user_id='';
	foreach (getteammembers($db, $project['projects_id']) as $teamMember) {
		$class_user_id .="user".$teamMember['project_members_team_id']." ";
	}

	$status = $project['status'];
	$filterClass=strtolower(str_replace(" ", "-", htmlentities($project['projects_title']))).' '.$status.' '.$class_user_id;
	
	$html .= '
	<div class="col-md-3 mix '.$filterClass.' ">
			            <div class="dashboard-pinned-project dashboard-pinned-project-info" data-link="http://pms.isodeveloper.com/admin/messages/'.htmlentities($project['projects_id']).'/view">
			              <div class="dashboard-pinned-project-header">
			              	<h3 class="project-title">
			              	<a href="http://pms.isodeveloper.com/admin/messages/'.htmlentities($project['projects_id']).'/view">'.htmlentities($project['projects_title']).'
			                </a></h3>
	';
	if(in_array($userid, array(43, 1, 17))) {
		$html .= '
							<div class="myprojectsnew-small">
								<label class="label label-primary pull-right">$&nbsp;'.$client['credit_amount_remaining'].'</label>
								Client balance
							</div>
		';
	}

	if(isAdmin($db, $userid)) {
	$html .= '<select class="pull-left" id="leadSelector" currentstatus="'.$status.'" projectid="'.htmlentities($project['projects_id']).'" data-toggle="tooltip" data-placement="bottom" title="Project Stage" data-original-title="Project Stage">
			              		<option value="1">Uncategorized Lead</option>
			              		<option value="2">Lead</option>
			              		<option value="3">On Going</option>
			              		<option value="4">Lost Lead</option>
			              		<option value="5">Post Sale</option>
			              		<option value="6">In-Progress</option>
<option value="7">On Hold - Pending Payment</option>
								<option value="8">On Hold</option>
			              		<option value="9">Completed</option>
							</select>';
	}
	$html .= '
			              	
			              	<span class="label label-info pull-right" id="bns-status-badge" data-toggle="tooltip" data-placement="bottom" title="Project Status Related to Deadline" data-original-title="Project Status Related to Deadline">In Progress</span>
			              	<div class="clearfix" style="margin-bottom: 0px;"></div>
			              	
			                
							<div class="progress active progress-table">
	                          <div class="progress-bar progress-bar-primary progress-bar-table" style="width:'.htmlentities($project['projects_progress_percentage']).'%" aria-valuemax="100" aria-valuemin="0" aria-valuenow="100" role="progressbar">
	                          	<span class="progress-text">'.htmlentities($project['projects_progress_percentage']).'%</span>
	                          </div>
	                        </div>
                        
							
			              </div>
 
 						  <div class="myprojectsnew-qlinks">
 						    <div>
 						  		<a href="/admin/messages/'.$project['projects_id'].'/view">client chat</a>&nbsp;|&nbsp;
 						  		<a href="/admin/teammessages/'.$project['projects_id'].'/view">team chat</a>&nbsp;|&nbsp;
 						  		<a href="/admin/project/'.$project['projects_id'].'/view">details</a>&nbsp;|&nbsp;
 						  		<a href="/admin/members/'.$project['projects_id'].'/view">users</a>
 						  	</div>
 						  </div
			              <div class="myprojectsnew-box1">
			              	<div>
								<div class="row">
									<div class="col-md-12">
										<ul class="list-group">
											<li class="list-group-item pdn-2-15">
												<label class="label label-default pull-right">'.$project['lead_name'].'</label>
												Primary User Name
					  						</li>
											<li class="list-group-item pdn-2-15">
												<label class="label label-default pull-right">'.$project['lead_phone'].'</label>
												Phone Number
					  						</li>


											<li class="list-group-item pdn-2-15">
												<label class="label label-warning pull-right">'.getTasksCount($db, $project['projects_id'], 'uncompleted').'</label>
												<a href="/admin/tasksfilter/'.$project['projects_id'].'/uncompleted">Uncompleted Tasks</a>
					  						</li>
					  						<li class="list-group-item pdn-2-15">
												<label class="label label-success pull-right">'.getTasksCount($db, $project['projects_id'], 'bugs').'</label>
												<a href="/admin/bugs/list-project/'.$project['projects_id'].'">Bugs</a>
					  						</li>
					  						<li class="list-group-item pdn-2-15">
												<label class="label label-primary pull-right">'.getTasksCount($db, $project['projects_id'], 'unread_messages').'</label>
												<a href="/admin/teammessages/'.$project['projects_id'].'/view">Unread Messages</a>
					  						</li>
					  						<li class="list-group-item pdn-2-15">
												<label class="label label-default pull-right">'.$project['projects_date_created'].'</label>
												Project Start Date
					  						</li>
					  						<li class="list-group-item pdn-2-15">
												<label class="label label-danger pull-right">'.getTasksCount($db, $project['projects_id'], 'needing_rewiew').'</label>
												<a href="/admin/tasksfilter/'.$project['projects_id'].'/needing_rewiew">Tasks Needing Review</a>
					  						</li>
	';
	if(isAdmin($db, $userid)) {
		$html .= '
					  						<li class="list-group-item pdn-2-15">
												<label class="label label-success pull-right">$&nbsp;'.getTotalBilliable($db, $project['projects_id']).'</label>
												Total Billable Tasks
					  						</li>
		';
	}
	$html .= '
										</ul>
									</div>
								</div>
							</div>
							
							<div class="clearfix"></div>
			              </div>
	';
	/*
			              <ul class="dashboard-pinned-project-footer">
			                <li class="border-right">
			                  <div class="text-count">
			                    '.htmlentities($project['projects_progress_percentage']).'%</div>
			                  <div class="text-description text-muted">Completed</div>
			                </li>
			                <li class="border-right">
			                  <div class="text-count">
			                    Tasks
			                  </div>
			                  <div class="text-description text-muted">'.getTasksCount($db, $project['projects_id'], 'pending').'</div>
			                </li>
			                <li>
			                  <div class="text-count">
			                    Days Due
			                  </div>
			                  <div class="text-description text-muted">0 Days</div>
			                </li>
			              </ul>
	*/
	$html .='
			            </div>
			            <!--WI_PINNED_PROJECT_1-->
		        	</div>
		        ';
	



	if ($index == 4){ $index = 1; }else{ $index += 1; }
}
echo $html;
?>