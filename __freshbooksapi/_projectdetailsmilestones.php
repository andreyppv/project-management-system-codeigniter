<?php
$db = new PDO("mysql:host=localhost;dbname=admin_freelancer", "freelancer_db", "t8Z6MxzvB?1bipjc");
$projectid = $_GET['projectid'];
function getprojects($db, $userid){
    $prepared = $db->prepare("SELECT `project_members_project_id` FROM project_members WHERE project_members_team_id = ?");
    $prepared->execute(array($userid));
    if($prepared->rowCount() > 0){
        return $prepared->fetchAll();
    }else{
        return array();
    }
}

function getProject($db, $projectid){
    $prepared = $db->prepare("SELECT * FROM projects WHERE projects_id = ?");
    $prepared->execute(array($projectid));
    return $prepared->fetch(PDO::FETCH_ASSOC);
}

function getProjectMilestones($db, $projectid){
    $prepared = $db->prepare("SELECT * FROM milestones WHERE milestones_project_id = ?");
    $prepared->execute(array($projectid));
    if($prepared->rowCount() > 0){
        return $prepared->fetchAll();
    }else{
        return array();
    }
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

function getMilestoneTasks($db, $projectID, $milestoneID){
    $prepared = $db->prepare("SELECT * FROM tasks WHERE tasks_project_id = ? AND tasks_milestones_id = ? AND tasks_status != 'completed'");
    $prepared->execute(array($projectID, $milestoneID));
    if($prepared->rowCount() > 0){
        return $prepared->fetchAll();
    }else{
        return array();
    }
}
?>
          <div class="widget">
              <div class="widget-content widget-content-project height-auto">
                <div class="project-info-tabs">
                  
                  <div class="res-project-summary">
                    <!--project head-->
                    <div class="project-details">
                      <!--WI_PROJECT_HEAD-->
                      <div class="row">
	                    <div class="col-lg-12">
	                      <div class="tabs-sub-nav"> <span class="pull-left">
	                        <h3>Milestone Tasks</h3>
	                        </span>
	                        <a data-toggle="modal" class="btn btn-mid btn-info pull-right" href="#addNewTaskModal"><i class="icon-plus"></i> Add New Task </a>
	                        <div class="clearfix"></div>
	                      </div>
	                    </div>
	                  </div>
                      
  
                      <div class="project-home">
                        <?php 
                        $color_index = 0; 
						$color[0] = 'text-primary';
						$color[1] = 'text-success';
						$color[2] = 'text-warning';
						$color[3] = 'text-danger';
                        foreach (getProjectMilestones($db, $projectid) as $milestone) {
                        
                        if($index == 0){ ?><div class="row"><?php } ?>
                        	<div class="col-md-12">
                        		<div class="widget widget-content widget-content-project" style="min-height: 0; height: 200px; overflow-y:scroll;">
                        			<div class="tabs-sub-nav">
                        				<h3 class="pull-left"><?php echo htmlentities($milestone['milestones_title']); ?></h3>
                        				<div class="clearfix"></div>
                        			</div>
                                    
                        			<?php foreach(getMilestoneTasks($db, $projectid, $milestone['milestones_id']) as $task){ 
                        				$color_index++;
										if($color_index == 4) $color_index = 0;
                        				?>
                                    	<a onclick="location.href = 'http://pms.isodeveloper.com/admin/tasksexpanded/<?php echo $projectid; ?>/<?php echo htmlentities($task['tasks_id']); ?>';" herf="http://pms.isodeveloper.com/admin/tasksexpanded/<?php echo htmlentities($task['tasks_id']); ?>" class="project-milestone-task <?php echo $color[$color_index]; ?>"><?php echo htmlentities($task['tasks_text']); ?></a><br/>
                                    <?php } ?>
                                    
                        		</div>
                        	</div>
                        <?php if($index == 2){ $index = 0; ?></div><?php }else{ $index += 1; } ?>
                        <?php } ?>
                      </div>

                   
                    </div>
                  </div>
                </div>
                <!--WI_TABS_NOTIFICATION-->
                
                <!--WI_TABS_NOTIFICATION-->
              </div>
            </div>