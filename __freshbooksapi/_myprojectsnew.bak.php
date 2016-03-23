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
	$prepared = $db->prepare("SELECT * FROM team_profile WHERE team_profile_id = ? AND team_profile_groups_id = ?");
	$prepared->execute(array($userid, 1));
	$row = $prepared->fetch(PDO::FETCH_ASSOC);

	if( !empty($row) ){
		return true;
	}else{
		return false;
	}
}

function getProject($db, $projectid){
	$prepared = $db->prepare("SELECT * FROM projects WHERE projects_id = ?");
	$prepared->execute(array($projectid));
	return $prepared->fetch(PDO::FETCH_ASSOC);
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

function getTeamMember($db, $teamMemberID){
	$prepared = $db->prepare("SELECT * FROM team_profile WHERE team_profile_id = ?");
	$prepared->execute(array($teamMemberID));
	return $prepared->fetch(PDO::FETCH_ASSOC);
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
	$prepared = $db->prepare("SELECT * FROM tasks WHERE tasks_project_id = ? AND tasks_status = ?");
	$prepared->execute(array($projectID, $type));
	return $prepared->rowCount();
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
			              <div class="dashboard-pinned-project-header" style="background-color: #ffffff;  border-top: solid 1px #e7e7e7; border-left: solid 1px #e7e7e7; border-right: solid 1px #e7e7e7;">
			              	<h3 class="project-title">
			              	<a href="http://pms.isodeveloper.com/admin/messages/'.htmlentities($project['projects_id']).'/view">'.htmlentities($project['projects_title']).'
			                </a></h3>
	';
	if(in_array($userid, array(43, 1, 17)))$html .= '<small style="color:#555;">Client balance:$&nbsp;'.$client['credit_amount_remaining'].'</small>';

	if(isAdmin($db, $userid)){
	$html .= '<select class="pull-left" id="leadSelector" currentstatus="'.$status.'" projectid="'.htmlentities($project['projects_id']).'" data-toggle="tooltip" data-placement="bottom" title="Project Stage" data-original-title="Project Stage" style="font-size: 75%; font-weight: bold; color: white; background-color: #1aaada; border: none;">
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
			              	<div class="clearfix" style="margin-bottom: 15px;"></div>
			              	
			                
							<div class="progress active progress-table">
	                          <div class="progress-bar progress-bar-primary progress-bar-table" style="width:'.htmlentities($project['projects_progress_percentage']).'%" aria-valuemax="100" aria-valuemin="0" aria-valuenow="100" role="progressbar">
	                          	<span class="progress-text">'.htmlentities($project['projects_progress_percentage']).'%</span>
	                          </div>
	                        </div>
                        
							
			                </div>
			                
			              <div style="background-color: #ffffff; border-left: solid 1px #e7e7e7; border-right: solid 1px #e7e7e7;">
			              	<div style="display: table; width: 100%; padding: 15px; padding-top: 20px;">
	';
/*
								<div class="row">
									<div class="col-md-12">
										<ul class="list-group">
											<li class="list-group-item pdn-2-15">
												<label class="label label-warning pull-right">'.getTasksCount($db, $project['projects_id'], 'behind schedule').'</label>
												Behind Schedule
					  						</li>
					  						<li class="list-group-item pdn-2-15">
												<label class="label label-success pull-right">'.getTasksCount($db, $project['projects_id'], 'pending').'</label>
												In progress
					  						</li>
					  						<li class="list-group-item pdn-2-15">
												<label class="label label-primary pull-right">'.getTasksCount($db, $project['projects_id'], 'completed').'</label>
												Completed
					  						</li>
										</ul>
									</div>
								</div>
*/
	foreach (getteammembers($db, $project['projects_id']) as $teamMember) {
		$member = getTeamMember($db, $teamMember['project_members_team_id']);
		if(!empty($member['team_profile_avatar_filename'])){
			$html .= '
			<img alt="'.htmlentities($member['team_profile_full_name']).'" src="http://pms.isodeveloper.com/files/avatars/'.$member['team_profile_avatar_filename'].'" class="avatar-small image-boardered pull-left mrg5R" data-toggle="tooltip" data-placement="bottom" title="'.htmlentities($member['team_profile_full_name']).'">
			';
		}else{
			$html .= '
			<img alt="'.htmlentities($member['team_profile_full_name']).'" src="http://pms.isodeveloper.com/files/avatars/default.png" class="avatar-small image-boardered pull-left mrg5R" data-toggle="tooltip" data-placement="bottom" title="'.htmlentities($member['team_profile_full_name']).'">
			';
		}
	}
	$html .= '
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