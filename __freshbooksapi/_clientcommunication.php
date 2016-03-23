<?php 
ini_set('display_errors', 1);
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
    $prepared = $db->prepare("SELECT * FROM tasks WHERE tasks_project_id = ? AND tasks_milestones_id = ?");
    $prepared->execute(array($projectID, $milestoneID));
    if($prepared->rowCount() > 0){
        return $prepared->fetchAll();
    }else{
        return array();
    }
}

function getMessages($db, $projectID){
  $prepared = $db->prepare("SELECT * FROM messages WHERE messages_project_id = ? ORDER BY messages_date DESC");
  $prepared->execute(array($projectID));
  return $prepared->fetchAll();
}

function getMessageReplies($db, $messageid, $projectID){
  $prepared = $db->prepare("SELECT * FROM messages_replies WHERE messages_replies_project_id = ? AND messages_replies_message_id = ?  ORDER BY messages_replies_date DESC");
  $prepared->execute(array($projectID, $messageid));
  return $prepared->fetchAll();
}

function getAvatar($db, $userid){
  $prepared = $db->prepare("SELECT team_profile_avatar_filename FROM team_profile WHERE team_profile_id = ?");
  $prepared->execute(array($userid));
  $row = $prepared->fetch(PDO::FETCH_ASSOC);
  $avatar = $row['team_profile_avatar_filename'];
  if(empty($avatar)){
    $avatar = 'default.png';
  }
  return $avatar;
}

function getName($db, $userid){
  $prepared = $db->prepare("SELECT team_profile_full_name FROM team_profile WHERE team_profile_id = ?");
  $prepared->execute(array($userid));
  $row = $prepared->fetch(PDO::FETCH_ASSOC);
  return $row['team_profile_full_name'];
}
function getClientName($db, $userid){
  $prepared = $db->prepare("SELECT client_users_full_name FROM client_users WHERE client_users_id = ?");
  $prepared->execute(array($userid));
  $row = $prepared->fetch(PDO::FETCH_ASSOC);
  return $row['client_users_full_name'];
}
?>
<div class="toggle-div message-input" style="">
                  <div id="message_11">
                        <?php foreach(getMessages($db, $projectid) as $message){ ?>
                        <!--message-->
                        <div class="messaging messaging-boxed"> 
                          <img alt="" src="http://pms.isodeveloper.com/files/avatars/<?php echo getAvatar($db, $message['messages_by_id']); ?>" class="avatar-small image-boardered pull-left">
                          <div class="messaging-container">
                            <div class="messaging-meta">
                              <!--posted by-->
                              <?php if($message['messages_by']=='client'):?>
                               <a class="links-blue iframeModal" data-height="250" data-width="100%" data-toggle="modal" data-target="#modalIframe" data-modal-window-title="User Profile" data-src="http://pms.isodeveloper.com/admin/people/team/<?php echo $message['messages_by_id']; ?>" href="#"><?php echo getClientName($db, $message['messages_by_id']); ?></a>                             	
                              <?php endif ?>
                              <?php if($message['messages_by']=='team'):?>
                               <a class="links-blue iframeModal" data-height="250" data-width="100%" data-toggle="modal" data-target="#modalIframe" data-modal-window-title="User Profile" data-src="http://pms.isodeveloper.com/admin/people/team/<?php echo $message['messages_by_id']; ?>" href="#"><?php echo getName($db, $message['messages_by_id']); ?></a>                             	
                              <?php endif ?>
                              <!--posted by -->
                              <small class="text-muted pull-right"><i class="icon-time"></i>
                              <?php echo $message['messages_date']; ?>
                              </small> </div>
                              <script>
                                $.ajax({
                                  url: "http://pms.isodeveloper.com/__freshbooksapi/_whattask.php?messageid=<?php echo $message['messages_id']; ?>"
                                }).done(function(data){
                                  //$("#task11").text(data);
                                });
                              </script>
                              <div id="task11" style="text-decoration:underline;"></div>
                            <!--message text-->
                            <div class="messaging-text line-height-normal">
                              <?php echo $message['messages_text']; ?>
                            </div>
                            <!--message text-->
                            <!--div class="messaging-actions"> <a class="btn-group pull-right btn btn-xs btn-default divminimize" href="#"> <span class="lowercase">Reply</span-->
                            <!--permissions-->
                            </a>
                              <!--WI_MESSAGE_CONTROL-->
                              <div class="btn-group pull-right">
                                <!--edit message-->
                                <!--span>

                                <button class="btn btn-xs btn-default iframeModal" data-toggle="modal" data-height="250" data-width="100%" data-src="http://pms.isodeveloper.com/admin/teammessages/5/edit-message/11/0/modal" data-target="#modalIframe"><i class="icon-pencil"></i>
                                
                                </button>
                                </span-->
                                <!--edit message-->
                                <!--control buttons delete-->
                                <span>
                                <!--permissions-->
                                <!--button class="btn btn-xs btn-default ajax-delete-content"
                                        data-popconfirm-yes="Yes"
			                            data-popconfirm-no="No"
                                        data-popconfirm-title="Confirm - delete item"
                                        data-popconfirm-placement="left"
                                        data-parent-div-id="message_11"
                                        data-mysql-record-id="11"
                                        data-mysql-record-id2="5"
                                        data-ajax-url="http://pms.isodeveloper.com/admin/ajax/delete-project-team-message"> <i class="icon-remove"></i>
                                
                                </button-->
                                </span>
                                <!--control buttons delete-->
                              </div>
                              <!--WI_MESSAGE_CONTROL_END-->
                              <div class="clearfix"></div>
                              <!--message-reply-box-->
                              
                              <!--message-reply-box-->
                            </div>
                            <!--main message end-->
                          </div>
                          <!--reply message-->
                          <?php foreach(getMessageReplies($db, $message['messages_id'], $projectid) as $reply){ ?>
                          <div class="messaging-reply" id="reply_9"> <img alt="" src="http://pms.isodeveloper.com/files/avatars/<?php echo getAvatar($db, $reply['messages_replies_by_id']); ?>" class="avatar-small image-boardered pull-left">
                            <div class="messaging-container">
                              <div class="messaging-meta"><a class="links-blue iframeModal" data-height="250" data-width="100%" data-toggle="modal" data-target="#modalIframe" data-modal-window-title="User Profile" data-src="http://pms.isodeveloper.com/admin/people/team/<?php echo $reply['messages_replies_by_id']; ?>" href="#"><?php echo getName($db, $reply['messages_replies_by_id']); ?></a> <small class="text-muted pull-right"><i class="icon-time"></i>
                                <?php echo $reply['messages_replies_date']; ?>
                                </small> </div>
                              <div class="messaging-reply-text"><?php echo $reply['messages_replies_text']; ?></div>
                              <div class="messaging-actions">
                                <!--WI_REPLY_CONTROL-->
                                <div class="btn-group pull-right">
                                  
                                </div>
                                <div class="clearfix"></div>
                              </div>
                            </div>
                            <div class="clearfix"></div>
                          </div>
                          <?php } ?>
                          
                          <!--reply message end-->
                        </div>
                        <!--div class="toggle-div message-input" style="">
                              <script>
                                $(document).ready(function(){
                                  $(".addreplyform<?php echo htmlentities($message['messages_id']); ?>").submit(function(e){
                                    $.post( 
                                      "http://pms.isodeveloper.com/__freshbooksapi/_createreply.php?client", 
                                      { 

                                        messages_replies_text: $("#message_reply_11_client_<?php echo htmlentities($message['messages_id']); ?>").val(), 
                                        messages_replies_message_id: $("#messages_replies_message_id<?php echo htmlentities($message['messages_id']); ?>").val(),
                                        messages_replies_by_id: $("#messages_replies_by_id<?php echo htmlentities($message['messages_id']); ?>").val(),
                                        posted_by: $("#posted_by").val(),
                                        messages_replies_project_id: $("#messages_replies_project_id<?php echo htmlentities($message['messages_id']); ?>").val()
                                      }).done(function( data ) {
                                        alert(data);
                                        //location.href = location.href;
                                      });
                                    e.preventDefault();
                                  });
                                });
                              </script>
                                <form class="addreplyform<?php echo htmlentities($message['messages_id']); ?>" action="" method="post">
                                  <div class="form-group form-group-standard">
                                    <div class="col-lg-12">
                                      <input type="text" name="messages_replies_text" id="message_reply_11_client_<?php echo htmlentities($message['messages_id']); ?>"/>
                                    </div>
                                  </div>
                                  <div class="form-group form-group-standard">
                                    <div class="col-lg-12">
                                      <input type="hidden" name="messages_replies_message_id" id="messages_replies_message_id<?php echo htmlentities($message['messages_id']); ?>" value="<?php echo htmlentities($message['messages_id']); ?>" required="required">
                                      <input type="hidden" name="messages_replies_by_id" id="messages_replies_by_id<?php echo htmlentities($message['messages_id']); ?>" value="6" required="required">
                                      <input type="hidden" name="posted_by" value="" required="required">
                                      <input type="hidden" name="messages_replies_project_id" id="messages_replies_project_id<?php echo htmlentities($message['messages_id']); ?>" value="<?php echo $projectid; ?>" required="required">
                                      <button class="btn btn-mid btn-primary" type="submit">Post Message</button>

                                    </div>
                                  </div>
                                </form>
                              </div-->
                        <div class="clearfix"></div>
                        <?php } ?>
                      </div>