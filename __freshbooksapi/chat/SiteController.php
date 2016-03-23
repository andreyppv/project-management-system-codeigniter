<?php

class SiteController extends Controller {

    public function init() {
        $this->actions['change_password'] = 'application.controllers.ChangePasswordAction';
        $this->actions['forgot_password'] = 'application.controllers.ForgotPasswordAction';
        $this->actions['captcha'] = array(
            'class' => 'CCaptchaAction',
        );
        $this->actions['page'] = array(
            'class' => 'CViewAction',
        );

        $this->accessRules[] = array('allow','actions' => array('delete', 'publish', 'update_profile', 'follow', 'unfollow', 'memelike', 'memeunlike', 'flag', 'mymemes'), 'users' => array('@'));
        $this->accessRules[] = array('allow','actions' => array('login', 'index', 'register', 'contact'), 'users' => array('*'));
        $this->accessRules[] = array('deny','actions' => array('change_password'), 'users' => array('?'));
        $this->accessRules[] = array('deny','actions' => array('delete', 'update_profile', 'follow', 'unfollow', 'memelike', 'memeunlike', 'flag'), 'users' => array('?'));


        parent::init();
    }

    public function filters() {
        return array('accessControl');
    }
	
	
	public function actionDynamicchatbox() {
      
	    $limit   =  15;
        $page    =  Yii::app()->request->getQuery('page');
        $offset  =  (max($page, 1) - 1) * $limit;
        $action  =  Yii::app()->request->getParam('action');
        $id      =  Yii::app()->request->getParam('id');
				
		
        $pagination = true;

        $q       =   new CDbCriteria(array(
					'condition' => 't.is_published = 1',
					'join' => 'INNER JOIN user ON t.user_fk = user.user_id',
					'order' => 't.meme_id DESC',
					'limit' => $limit,
					'offset' => $offset,
	    ));

        $trendingCriteria = clone $q;
        $trendingCriteria->select = 't.*, COUNT(ml.meme_fk) as meme_liked';
        $trendingCriteria->join .= ' INNER JOIN meme_like ml ON ml.meme_fk = t.meme_id';
        $trendingCriteria->condition .= ' AND DATE(ml.created_at) >= DATE_SUB(ml.created_at, INTERVAL 14 DAY) and DATE(ml.created_at) <= DATE_SUB(ml.created_at, INTERVAL 7 DAY)';
        $trendingCriteria->group = 'ml.meme_fk';
        $trendingCriteria->order = 'meme_liked DESC, t.created_at DESC';

        $q->condition .= ' AND t.meme_id = :id';
        $q->params = array(
                ':id' => $id,
        );
        $pagination = false;
		
		$memes = Meme::model()->findAll($q);
				
		foreach ($memes as $i => $meme){
	    
		echo '<div class="chat_box" onmouseover="Stop_Scroll()"><div class="chat_inner chat_inner_'.$meme->meme_id.'"></div></div>';
	  ?>
      <div class="chat_input">
                    <?php 
					if(!Yii::app()->user->isGuest) {
					?>
                       <form action=""  class='commentForm' style="float:left; width:93%; margin-bottom:0px;">
                          <input type='text' name="chat_input" id="chat_input"  data-room='<?= $meme->meme_id ?>' class='msginput' value='Commentez, Réagissez ici ..' />
                       </form>
                       <img src="<?php echo Yii::app()->theme->baseUrl; ?>/img/chat_expand.png" data-original-title="Ouvrir cette conversation dans une fenêtre plus grande" class="ttip chat_expand" data-room='<?= $meme->meme_id ?>'/>
                    <?php } else { ?>
                        <a href="<?php echo Yii::app()->baseUrl; ?>/login" id="comments_link">Connectes-toi</a> ou <a href="<?php echo Yii::app()->baseUrl; ?>/register" id="comments_link">Enregistres-toi</a> pour commenter et réagir ...
                    <?php } ?>
                    
                    </div>
      <script>
      </script>
      <link rel="stylesheet" type="text/css" href="<?php echo Yii::app()->theme->baseUrl; ?>/js/content-scroller/jquery.mCustomScrollbar.css" />
      <script type="text/javascript" src="<?php echo Yii::app()->theme->baseUrl; ?>/js/content-scroller/jquery.mCustomScrollbar.concat.min.js"></script>
      <div class="chat_popup">                        
                    <div class="chatpopup_inner chat_mod">
                           <div class="chat_header">
                                <?php 
                                                $meme_url   = 'http://'.$_SERVER['SERVER_NAME'].Yii::app()->baseUrl.'/'.$meme->meme_type.'/'.$meme->slug;

                                                 if($meme->meme_type!='videos')  {
                                                ?>
                                                              <img src="<?php echo Yii::app()->baseUrl.'/timthumb.php?src='.$meme->url.'&w=52&h=53'; ?>"/> 
                                                              <?php
                                                } else {
                                                
                                                  $meme_video  =  explode(',',$meme->file);
                                                    
                                                  if($meme_video[1]=='youtube'){
                                                   echo '<img src="'.Yii::app()->baseUrl.'/timthumb.php?src=http://img.youtube.com/vi/'.$meme_video[0].'/default.jpg&w=w=52&h=53"/> '; 
                                                  } else if($meme_video[1]=='viemo'){
                                                     $vimeo_thumb  = SiteController::get_vimeo_thumb($meme_video[0]);                           
                                                     echo '<img src="'.Yii::app()->baseUrl.'/timthumb.php?src='.$vimeo_thumb.'/default.jpg&w=52&h=53"/>'; 
                                                  } else {
                                                    echo '<img src="'.Yii::app()->baseUrl.'/timthumb.php?src=http://www.dailymotion.com/thumbnail/video/'.$meme_video[0].'&w=52&h=53"/> ';
                                                  }
                                                  
                                                }
                                                 ?>
                                              &nbsp;<b><?php echo CHtml::encode($meme->title) ?></b>
                                <img src="<?php echo Yii::app()->theme->baseUrl;?>/img/close_button.png"  class="chat_close" width="60"/>
                           </div>
                           <div class="chat_box_popup">
                           <div class="chat_inner  chat_inner_<?= $meme->meme_id ?>">
                              
                            </div>
                           </div>
                           <div class="chat_input">
                            <form action=""  class='commentForm' style="float:left; width:93%; margin-bottom:0px;">
                               <input type='text' name="chat_input" id="chat_input"  data-room='<?= $meme->meme_id ?>' class='msginput' value='Commentez, Réagissez ici ..' />                                                   
                            </form>
                            </div>
                    </div>        
                                                                               
       </div>
       <script type="text/javascript">                        
	   $(document).ready(function(){
	  
			var chatroom = {
					title      : '<?php echo CHtml::encode($meme->title) ?>',
					itemID     : '<?php echo $meme->meme_id ?>',
          meme_type   :'<?php echo $meme->meme_type ?>',
          slug      :'<?php echo $meme->slug ?>',
          url      :'<?php echo $meme->url ?>',
          file      :'<?php echo $meme->file ?>',
          user_fk    :'<?php echo $meme->user_fk ?>',
					owner      : { 
					Member_id: '<?php echo $meme->user->user_id ?>', 
					name:'<?php echo CHtml::encode("{$meme->user->first_name} {$meme->user->last_name}"); ?>', 
					nickname:'<?php echo $meme->user->username; ?>',
					image: "<?php echo Yii::app()->baseUrl.'/timthumb.php?src='.Yii::app()->user->getAvatar_url($meme->user->user_id).'&w=34&h=33'; ?>"
				  }
			 };
	
			 socket.emit("join", chatroom, chat_user);
			 current_room = chatroom;
			 $(".msginput").keypress(function(e){
			   room = $(this).attr('data-room')
			   if(e.which == 13) {
				 var msg = $(this).val().replace(/^\s+|\s+$/g, '');
				 if(msg !== ''){
				  if($(this).hasClass('msginput')){
				   socket.emit("send", msg, room);
				  }else{
				   msgid = $(this).attr('data-msgid')
				   socket.emit("update", msg, room, msgid);                        
				  }
				   $(this).val('');
				}
			   }
			});
			
			$(".commentForm").submit(function(event){
                            event.preventDefault();
                        });
			
	  });
      </script> 
      <script type="text/javascript" src="<?php echo Yii::app()->theme->baseUrl; ?>/js/chatbox.js"></script>
      <?php
	  }
	  exit;	
	
	}
	
	
	public function actionChatmeme() {
	
	    $limit   = 15;
        $page    = Yii::app()->request->getQuery('page');
        $offset  = (max($page, 1) - 1) * $limit;
        $action  = Yii::app()->request->getParam('action');
        $id      = Yii::app()->request->getParam('id');
				
		
        $pagination = true;

        $q = new CDbCriteria(array(
				'condition' => 't.is_published = 1',
				'join' => 'INNER JOIN user ON t.user_fk = user.user_id',
				'order' => 't.meme_id DESC',
				'limit' => $limit,
				'offset' => $offset,
			 ));

        $trendingCriteria = clone $q;
        $trendingCriteria->select = 't.*, COUNT(ml.meme_fk) as meme_liked';
        $trendingCriteria->join .= ' INNER JOIN meme_like ml ON ml.meme_fk = t.meme_id';
        $trendingCriteria->condition .= ' AND DATE(ml.created_at) >= DATE_SUB(ml.created_at, INTERVAL 14 DAY) and DATE(ml.created_at) <= DATE_SUB(ml.created_at, INTERVAL 7 DAY)';
        $trendingCriteria->group = 'ml.meme_fk';
        $trendingCriteria->order = 'meme_liked DESC, t.created_at DESC';

        $q->condition .= ' AND t.meme_id = :id';
        $q->params = array(
                ':id' => $id,
        );
        $pagination = false;
		
		$memes = Meme::model()->findAll($q);
		
		foreach ($memes as $i => $meme){
		
		$meme_url   = 'http://'.$_SERVER['SERVER_NAME'].Yii::app()->baseUrl.'/'.$meme->meme_type.'/'.$meme->slug;
		?>
              <?php if($meme->meme_type!='videos')  { ?>                      
                <a href="<?php echo $meme_url; ?>" target="_blank">
                 <img src="<?php echo $meme->url; ?>" style=" width:238px;"/>
               </a>
              <?php }
               else { 
                $meme_video  =  explode(',',$meme->file);
                if($meme_video[1]=='youtube'){
                  echo '<iframe width="238" height="230" src="//www.youtube.com/embed/'.$meme_video[0].'?rel=0" frameborder="0" allowfullscreen></iframe>';
                } else if($meme_video[1]=='viemo'){	                          
                  echo '<iframe src="//player.vimeo.com/video/'.$meme_video[0].'" width="238" height="230" frameborder="0" allowfullscreen></iframe>';
                } else {
                   echo '<iframe frameborder="0" width="238" height="230" src="http://www.dailymotion.com/embed/video/'.$meme_video[0].'"></iframe>'; 
                }
               }
              ?>    
              <div class="convers_title">
                <a href="<?php echo $meme_url; ?>" target="_blank"><b><?php echo CHtml::encode($meme->title) ?></b></a>
              </div>
              <div class="userProf_feed">                   
               <?php if(Yii::app()->user->id != $meme->user->user_id) { ?>
                     <a href="<?php echo Yii::app()->createUrl('site/profile', array('profile' => $meme->user->username)) ?>"><img src="<?php echo Yii::app()->user->getAvatar_url($meme->user_fk) ?>" alt="<?php echo CHtml::encode("{$meme->user->first_name} {$meme->user->last_name}") ?>" title="<?php echo CHtml::encode("{$meme->user->first_name} {$meme->user->last_name}") ?>" class="meta_image ttip" style="float:left; padding-right:5px;"/></a> 
                     Par <br>                             
                     <b><?php echo $meme->user->first_name.'&nbsp;'.$meme->user->last_name; ?></b>
                <?php  } ?>                       
               </div> 
		 <?php
		 }	
		 exit;			 
	}

    /**
     * This is the default 'index' action that is invoked
     * when an action is not explicitly requested by users.
     */
	 
    public function actionPopup() {
	    
		$limit   = 15;
        $page    = Yii::app()->request->getQuery('page');
        $offset  = (max($page, 1) - 1) * $limit;
        $action  = Yii::app()->request->getParam('action');
        $id      = Yii::app()->request->getParam('id');
				
		
        $pagination = true;

        $q = new CDbCriteria(array(
				'condition' => 't.is_published = 1',
				'join' => 'INNER JOIN user ON t.user_fk = user.user_id',
				'order' => 't.meme_id DESC',
				'limit' => $limit,
				'offset' => $offset,
			 ));

        $trendingCriteria = clone $q;
        $trendingCriteria->select = 't.*, COUNT(ml.meme_fk) as meme_liked';
        $trendingCriteria->join .= ' INNER JOIN meme_like ml ON ml.meme_fk = t.meme_id';
        $trendingCriteria->condition .= ' AND DATE(ml.created_at) >= DATE_SUB(ml.created_at, INTERVAL 14 DAY) and DATE(ml.created_at) <= DATE_SUB(ml.created_at, INTERVAL 7 DAY)';
        $trendingCriteria->group = 'ml.meme_fk';
        $trendingCriteria->order = 'meme_liked DESC, t.created_at DESC';

        if ($id) {
            $q->condition .= ' AND t.meme_id = :id';
            $q->params = array(
                ':id' => $id,
            );
            $pagination = false;
        } else if ($action == 'popular') {
            $q->order = 't.likes_count DESC, t.created_at DESC';
        } else if ($action == 'mis-en-avant') {
            $q->condition .= ' AND is_featured = 1';
            $q->order = 't.created_at DESC';
        } else if ($action == 'trending') {
            $q = $trendingCriteria;
        }

        $q->condition .= ' AND t.is_published = 1';

        $memes = Meme::model()->findAll($q);
	


	    foreach ($memes as $i => $meme):
		
		
		$meme_url   = 'http://'.$_SERVER['SERVER_NAME'].Yii::app()->baseUrl.'/'.$meme->meme_type.'/'.$meme->slug;
		
		if($meme->meme_type!='videos')  {
		  list($width, $height) = getimagesize($meme->url);
		  $tot_width  = $width + 319;
		  $tot_height = $height + 450;
		  
		  if($width > 596 )
		   $add_class  = 'large_image_width';
		  else if($height > 502)
		   $add_class  = 'large_image_height';
		  else
		   $add_class  = '';
		  
	    } else  {

		   $tot_width = 919;
			 $width = 600;
			 $tot_height = 500;
		     $set_margin = 0;
			 
			 $add_class  = '';
		}
        $user              = User::model()->findByPk(Yii::app()->user->id);
        ?>

        <div id="meme_contentbox1" style="display:none;">
           <div id="meme_popup1" style="">        
              
              <span  id="close_popup1" style=" float:right; padding:10px 20px 0px 0px;">
                <img src="<?php echo Yii::app()->theme->baseUrl; ?>/img/close_button.png" />
              </span>
              
             
              <div style="margin:auto; width:487px;">
                  <div style="width:487px;  text-align:center;min-height:300px; margin:130px auto 130px auto; background:#FFFFFF; float:left;padding:0px 0px 15px 0px;">
                    
                         <h2><?php echo CHtml::encode($meme->title) ?></h2>                 
                          <?php 
						  if($meme->meme_type!='videos')  {
						  ?>                      
                          <img class="meme-img" src="<?php echo $meme->url ?>" alt="<?php echo CHtml::encode($meme->title) ?>" width="430"/>
                          <?php } else {                             
                            $meme_video  =  explode(',',$meme->file);               
                            if($meme_video[1]=='youtube'){
                              echo '<iframe width="430" height="342" src="//www.youtube.com/embed/'.$meme_video[0].'?rel=0" frameborder="0" allowfullscreen></iframe>';
                            } else if($meme_video[1]=='viemo'){	                          
                              echo '<iframe src="//player.vimeo.com/video/'.$meme_video[0].'" width="430" height="342" frameborder="0" allowfullscreen></iframe>';
                            } else {
                               echo '<iframe frameborder="0" width="430" height="342" src="http://www.dailymotion.com/embed/video/'.$meme_video[0].'"></iframe>'; 
                            }
                           }
						   
                           ?>    
                           <div style=" text-align:left; padding-left:27px; line-height:44px;">
                              <?php
							   if(Yii::app()->user->id) {
							     $user_type   =  User::model()->findByAttributes(array('user_id' => Yii::app()->user->id));
								 if($user_type->token==''){
								   echo '<input type="checkbox" name="facebook_share" id="facebook_share_check" value="1"/>&nbsp;<b>Partager sur Facebook</b>';
								 }
							   }
							  ?>                              
                              <input type="hidden" name="meme_url" id="meme_url" value="<?php echo $meme_url; ?>"/>
                              <img src="<?php echo Yii::app()->theme->baseUrl; ?>/img/favourite_share.png" style="float:right; margin-right:27px; margin-top:5px;" id="favourite_share"/>
                                <script type="text/javascript">
							    $('#facebook_share_check').click(function(event){
								   event.stopPropagation();  								
								});
							  </script>
                           </div>                
                   </div>
              </div>
              
            </div>
     </div>
        <div id="meme_popup" style="">        
              <span  id="close_popup" style=" float:right; padding:10px 20px 0px 0px;">
                <img src="<?php echo Yii::app()->theme->baseUrl; ?>/img/close_button.png" />
              </span>
              <div class="corgi_feed_well <?php echo $add_class; ?>" style="width:988px; margin:auto;padding-top:70px; left:6px;">
                <div class="feed_stacked">
                    
                    <div class="feed_item meme">
                    
                        <div class="feed_body" style="float:left;padding:0px; margin-right:15px; width:669px;">
                            <div class="row" style="margin-left:0px;">
                                <!--<div class="feed_profile_pic">
                                    <a href="<?php echo Yii::app()->createUrl('profile', array('profile' => $meme->user->username)) ?>"><img src="<?php echo Yii::app()->user->getAvatar_url($meme->user_fk) ?>" alt="<?php echo CHtml::encode("{$meme->user->first_name} {$meme->user->last_name}") ?>" title="<?php echo CHtml::encode("{$meme->user->first_name} {$meme->user->last_name}") ?>" class="meta_image ttip" /></a>
                                </div>
                                <span class="timesago"><?php echo Yii::app()->format->formatTimeago($meme->created_at) ?></span>-->
                                <div class="feed_text text-center" style="padding:2px; height:502px; float:none; vertical-align:middle; display:table-cell;background:#000000;width:665px; text-align:center;">
                                       
                                       <div id="favorite_meme_button">
									   <?php
                                        
										$check_favorite   =  UserFavorite::model()->findByAttributes(array('user_fk' => Yii::app()->user->id, 'favorite_id' => $id));
                                        
										if(Yii::app()->user->id!=$meme->user_fk) {
											if($check_favorite){
										  
											 if(!Yii::app()->user->isGuest)
											  echo '<div id="userfav_buttons"><span id="unfavorite_button" href="site/favorite/id/'.$id.'"><img src="'.Yii::app()->theme->baseUrl.'/img/mattre_dans_fav.png"/></span></div>';
											 else
											  echo '<a href="'.Yii::app()->createUrl('login').'"><img src="'.Yii::app()->theme->baseUrl.'/img/mattre_dans_fav.png"/></a>';
										   
											} else { 
											 
											   if(!Yii::app()->user->isGuest)
												echo '<div id="userfav_buttons"><span id="favorite_button" href="site/unfavorite/id/'.$id.'"><img src="'.Yii::app()->theme->baseUrl.'/img/mattre_dans.png"/></span></div>';
											   else
												echo '<a  href="'.Yii::app()->createUrl('login').'"><img src="'.Yii::app()->theme->baseUrl.'/img/mattre_dans.png"/></a>';
												
											}
										 }										                                        
                                        ?>
                                        
                                        </div>       
                                      
                                       <!-- <h3><a href="<?php echo $meme->url?>"><?php echo CHtml::encode($meme->title) ?></a></h3>-->
                                   
                                       <?php if($meme->meme_type!='videos')  { 									   
										   list($width_ovnis, $height_ovnis) = getimagesize($meme->url);
										   if($width_ovnis > 600 || $height_ovnis > 500 )
										   echo '<a href="'.$meme->url.'" target="_blank"><img class="meme-img" src="'.$meme->url.'" alt="'.CHtml::encode($meme->title).'" /></a>';
										   else
										   echo '<img class="meme-img" src="'.$meme->url.'" alt="'.CHtml::encode($meme->title).'" />';
									   ?>                      

										  <?php } else { 
                                            
                                            $meme_video  =  explode(',',$meme->file);
                                
                                            if($meme_video[1]=='youtube'){
                                              echo '<iframe width="660" height="450" src="//www.youtube.com/embed/'.$meme_video[0].'?rel=0" frameborder="0" allowfullscreen></iframe>';
                                            } else if($meme_video[1]=='viemo'){	                          
                                              echo '<iframe src="//player.vimeo.com/video/'.$meme_video[0].'" width="660" height="450" frameborder="0" allowfullscreen></iframe>';
                                            } else {
                                               echo '<iframe frameborder="0" width="660" height="450" src="http://www.dailymotion.com/embed/video/'.$meme_video[0].'"></iframe>'; 
                                            }
                                           }
                                        ?>                                      
                                    
                                </div>
                                
                                <div style="background:#ffffff;float:left; width:100%; line-height:40px;">
                              
                                <div style="width:100%; padding-top:10px;">
                                     <div style="width:auto; float:right; line-height:11px;">                                                                              
                                         <?php 										 																				 
										  if($meme->source && ($meme->meme_type!='videos' && $meme->meme_type!='detournement-images')){										      
											   if (!preg_match("~^(?:f|ht)tps?://~i", $meme->source))
											   echo '<a href="http://'.$meme->source.'" target="_blank"><b>Source</b></a>&nbsp;';											   
											   else
											   echo '<a href="'.$meme->source.'" target="_blank"><b>Source</b></a>&nbsp;';											   
									      }
										  ?>                                         										 
                                     </div>                                                                          
                                </div>
                                
                                <div style="width:100%; text-align:center; font-size:15px; line-height:18px; padding:17px 0px 17px 0px;">
                                   <b><?php echo CHtml::encode($meme->title) ?></b>
                                </div>
                                
                                <!--<fb:comments href="<?php echo $meme_url; ?>" width="665px" numposts="5" colorscheme="light"></fb:comments>-->
                                              
                                
                                <div class="singlepage chat_mod" style="width:480px; float:left;">
                                     <!-- Chat Box Start --> 
                   
                                       <div class="chat_box">
                                            <div class="chat_inner chat_inner_<?= $meme->meme_id ?>">


                                           </div>
                                        </div>
                                        
                                        <div class="chat_input">
                                            <?php 
											if(!Yii::app()->user->isGuest) {
											?>
											   <form action="" class='commentForm' style="float:left; width:93%; margin-bottom:0px;">
												  <input name="chat_input" id="chat_input" data-room='<?= $meme->meme_id ?>' class='msginput' value='Commentez, Réagissez ici ..' />
                                                          <input id='hiddeninput' type='hidden'/>
											   </form>
                       <img src="<?php echo Yii::app()->theme->baseUrl; ?>/img/chat_expand.png" data-original-title="Ouvrir cette conversation dans une fenêtre plus grande" class="ttip chat_expand" data-room='<?= $meme->meme_id ?>'/>
											<?php } else { ?>
											 <a href="<?php echo Yii::app()->baseUrl; ?>/login" id="comments_link">Connectes-toi</a> ou <a href="<?php echo Yii::app()->baseUrl; ?>/register" id="comments_link">Enregistres-toi</a> pour commenter et réagir ...
											<?php } ?>                                           
                                        </div>
                                        
                                    </div>
                                    
                                    <div class="chat_popup">                        
                                        <div class="chatpopup_inner chat_mod">
                                               <div class="chat_header">
                                                <?php 
                                                $meme_url   = 'http://'.$_SERVER['SERVER_NAME'].Yii::app()->baseUrl.'/'.$meme->meme_type.'/'.$meme->slug;

                                                 if($meme->meme_type!='videos')  {
                                                ?>
                                                              <img src="<?php echo Yii::app()->baseUrl.'/timthumb.php?src='.$meme->url.'&w=52&h=53'; ?>"/> 
                                                              <?php
                                                } else {
                                                
                                                  $meme_video  =  explode(',',$meme->file);
                                                    
                                                  if($meme_video[1]=='youtube'){
                                                   echo '<img src="'.Yii::app()->baseUrl.'/timthumb.php?src=http://img.youtube.com/vi/'.$meme_video[0].'/default.jpg&w=w=52&h=53"/> '; 
                                                  } else if($meme_video[1]=='viemo'){
                                                     $vimeo_thumb  = SiteController::get_vimeo_thumb($meme_video[0]);                           
                                                     echo '<img src="'.Yii::app()->baseUrl.'/timthumb.php?src='.$vimeo_thumb.'/default.jpg&w=52&h=53"/>'; 
                                                  } else {
                                                    echo '<img src="'.Yii::app()->baseUrl.'/timthumb.php?src=http://www.dailymotion.com/thumbnail/video/'.$meme_video[0].'&w=52&h=53"/> ';
                                                  }
                                                  
                                                }
                                                 ?>
                                              &nbsp;<b><?php echo CHtml::encode($meme->title) ?></b>
                                                    <img src="<?php echo Yii::app()->theme->baseUrl;?>/img/close_button.png"  class="chat_close" width="60"/>
                                               </div>
                                               <div class="chat_box_popup">
                                                 <div class="chat_inner chat_inner_<?= $meme->meme_id ?>">

                                                 </div>
                                               </div>
                                                <div class="chat_input">
                                                       <form action=""  class='commentForm' style="float:left; width:93%; margin-bottom:0px;">
                          <input type='text' name="chat_input" id="chat_input"  data-room='<?= $meme->meme_id ?>' class='msginput' value='Commentez, Réagissez ici ..' />
                                                       </form>
                                                </div>
                                        </div>        
                                                                                
                                    </div>
                                
                                
                                
                                </div>
                                
                                
                                
                                
                            </div>  
                             <script>                        
              jQuery(document).ready(function($){

               
                   var chatroom = {
                        title      : '<?php echo CHtml::encode($meme->title) ?>',
                        itemID     : '<?php echo $meme->meme_id ?>',
                        meme_type   :'<?php echo $meme->meme_type ?>',
                        slug      :'<?php echo $meme->slug ?>',
                        url      :'<?php echo $meme->url ?>',
                        file      :'<?php echo $meme->file ?>',
                        user_fk    :'<?php echo $meme->user_fk ?>',
                        owner      :  { 
                          Member_id: '<?php echo $meme->user->user_id ?>', 
                          name:'<?php echo CHtml::encode("{$meme->user->first_name} {$meme->user->last_name}"); ?>', 
                          nickname:'<?php echo $meme->user->username; ?>',
                          image: "<?php echo Yii::app()->baseUrl.'/timthumb.php?src='.Yii::app()->user->getAvatar_url($meme->user->user_id).'&w=34&h=33'; ?>"
                      }
                    };
                  socket.emit("join", chatroom, chat_user);
                 $(".msginput").keypress(function(e){
                   room = $(this).attr('data-room')
                   if(e.which == 13) {
                     var msg = $(this).val().replace(/^\s+|\s+$/g, '');
                     if(msg !== ''){
                      if($(this).hasClass('msginput')){
                       socket.emit("send", msg, room);
                      }else{
                       msgid = $(this).attr('data-msgid')
                       socket.emit("update", msg, room, msgid);                        
                      }
                       $(this).val('');
                    }
                   }
                });
				
				
				
				
				
				
              });

            </script> 
                           <div style="width:100%; padding-top:10px;">
                                
                                
                               <div style="width:auto; float:left; height:50px; width:114px;">                                                                            
									 <?php if(!Meme::hasFlagged(Yii::app()->request->getParam('id'))): ?>
                                        
                                        <?php if(!Yii::app()->user->isGuest) { ?>
                                        <span class="ttip flag_meme no-underline" title="<?php echo Yii::t('yii', 'mark as inappropriate') ?>" href="<?php echo Yii::app()->createUrl('site/flag', array('id' => Yii::app()->request->getParam('id'))) ?>">
                                            <i class="icon-flag"></i> Report
                                        </span>
                                        <?php } else { ?>
                                         <a class="ttip flag_meme1 no-underline" title="<?php echo Yii::t('yii', 'mark as inappropriate') ?>" href="<?php echo Yii::app()->createUrl('login') ?>">
                                            <i class="icon-flag"></i> Report
                                        </a>
                                        <?php } ?>
                                        
                                       <?php endif ?>                                                                     
                                 </div>
                                    
                                 <?php  if($meme->meme_type!='videos') { ?>   
                                     <div style="width:auto; float:right;">                                     
                                         
                                         <?php if(!Yii::app()->user->isGuest) { ?>
                                            <a href="<?php echo Yii::app()->createUrl('site/download', array('id' => $meme->meme_id)) ?>" id="download_link">
                                               <?php echo 'Télécharger';  ?>
                                            </a>
                                          <?php } else { ?>
                                            <a href="<?php echo Yii::app()->createUrl('login') ?>" id="download_link">
                                               <?php echo 'Télécharger';  ?>
                                            </a>
                                          <?php } ?>
                                     </div>
                                  <?php } ?>                                
                                
                                
                                </div>
                            
                         </div>
                         
                         
                         <?php         
				             echo '<img src="'.Yii::app()->theme->baseUrl.'/img/'.$meme->meme_type.'.png" style="float:right; position:absolute; margin-top:-44px; right:0px;"/>';         				  	  
				         ?> 
                         <div style="width:303px; float:right;">
                         <div class="meme-menu">
                           <div class="alert alert-success" style="display:none;"><button class="close" data-dismiss="alert">x</button>&nbsp;<span></span></div>
                           <?php 						    	
						     $tot_portage = $this->get_partages($meme_url);
							 echo '<h2 id="portage_title">';
							 echo  $tot_portage;
							 							 
							 if($tot_portage==0 || $tot_portage==1)
							  echo "&nbsp;Partage";
							 else
							  echo "&nbsp;Partages";
							  
							 echo '</h2>'; 
							 														 
						   ?>
                           <div class="fb-share-button" data-href="<?php echo $meme_url; ?>" data-type="box_count"></div>

                           <div class="fb_likebutton"> 
                             <fb:like href="<?php echo $meme_url; ?>" layout="box_count" action="like" show_faces="false" share="false" style="margin-right:7px;"></fb:like>
                           </div>
                           <div class="tw_sharebutton">
                             <!--<a href="https://twitter.com/share" class="twitter-share-button"  data-url="<?php echo $meme_url; ?>" data-via="Lovelybuzz-Ovnis" data-lang="fr" data-related="anywhereTheJavascriptAPI" data-count="vertical">Tweet</a>
<script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0];if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src="https://platform.twitter.com/widgets.js";fjs.parentNode.insertBefore(js,fjs);}}(document,"script","twitter-wjs");</script>-->
<iframe allowtransparency="true" frameborder="0" scrolling="no" src="http://platform.twitter.com/widgets/tweet_button.html?url=<?php echo $meme_url; ?>&via=Lovelybuzz-Ovnis&text=<?php echo $meme->title; ?>&count=vertical" style="width:55px; height:69px;"></iframe>

                           </div>
                           <div class="gp_share" style="width:50px; float:left; height:64px;">
                              <iframe src="https://plusone.google.com/_/+1/fastbutton?bsv&amp;size=tall&amp;hl=en-US&amp;url=<?php echo $meme_url; ?>&amp;parent=<?php echo Yii::app()->baseUrl; ?>" allowtransparency="true" frameborder="0" scrolling="no" title="+1"></iframe>
                              <!-- <div class="g-plusone" data-size="tall"></div>                            
								<script type="text/javascript">
                                  window.___gcfg = {lang: 'fr'};
                                  (function() {
                                    var po = document.createElement('script'); po.type = 'text/javascript'; po.async = true;
                                    po.src = 'https://apis.google.com/js/platform.js';
                                    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(po, s);
                                  })();
                                </script>-->
                             </div>
                             
                             <div style="clear:both;">&nbsp;</div>
                             
                             <div id="user_link">
                               <a href="<?php echo Yii::app()->createUrl('site/profile', array('profile' => $meme->user->username)) ?>" style="float:left;">
                               
                               <img src="<?php echo Yii::app()->baseUrl;?>/timthumb.php?src=<?php echo Yii::app()->user->getAvatar_url($meme->user_fk); ?>&w=68&h=auto" alt="<?php echo CHtml::encode("{$meme->user->first_name} {$meme->user->last_name}") ?>" title="<?php echo CHtml::encode("{$meme->user->first_name} {$meme->user->last_name}") ?>" class="meta_image ttip" />                               
                               </a>
                               <div style="float:left; width:218px; padding-left:10px; height:55px;">
                                 Voir le profil de : <br /> <a href="<?php echo Yii::app()->createUrl('site/profile', array('profile' => $meme->user->username)) ?>"><b><?php echo $meme->user->first_name.'&nbsp;'.$meme->user->last_name; ?></b></a>
                               </div>
                             </div>
                           <link rel="stylesheet" type="text/css" href="<?php echo Yii::app()->theme->baseUrl; ?>/js/content-scroller/jquery.mCustomScrollbar.css" />
                           <script type="text/javascript" src="<?php echo Yii::app()->theme->baseUrl; ?>/js/content-scroller/jquery.mCustomScrollbar.concat.min.js"></script>
     
                           <?php						   						   
						  
						   
						   if ($user = User::model()->findByAttributes(array('username' => $meme->user->username))) {
							  
							  $q = new CDbCriteria(array(
								 'condition' => 't.is_active = 1 AND t.is_published = 1 AND t.user_fk = :user_id',
								 'params' => array(':user_id' => $user->user_id),
								 'order' => 't.meme_id DESC',
								 'limit' => 20,
							   ));
				
							  $memes = Meme::model()->findAll($q);
						   }						   						  							
			               ?>
                           <div style="height:35px;">
                           
                            <?php
						     $check_follow     = UserFollow::model()->findByAttributes(array('user_fk' => Yii::app()->user->id, 'following_id' => $meme->user_fk));												
						    ?>
                           <?php
								 if(Yii::app()->user->id!=$meme->user_fk) {
                                  
								  if(!$check_follow){
								                                      
									if(!Yii::app()->user->isGuest)
									 echo '<div id="follow_button"><img src="'.Yii::app()->theme->baseUrl.'/img/unfollow.png" id="follow_user"/></div>';
									else
									 echo '<a href="'.Yii::app()->createUrl('login').'"><img src="'.Yii::app()->theme->baseUrl.'/img/unfollow.png" id="follow_nonuser"/></a>';
									
                                  } else {
								      
									 if(!Yii::app()->user->isGuest)                            
                                        echo '<div id="follow_button"><img src="'.Yii::app()->theme->baseUrl.'/img/follow.png" id="unfollow_user"/></div>';                                  	 else
							           echo '<a href="'.Yii::app()->createUrl('login').'"><img src="'.Yii::app()->theme->baseUrl.'/img/unfollow.png" id="follow_nonuser"/></a>';
									   
								   }
									
								 }
                               ?>
                             
                           </div>
                           
                           <div class="ovnis_sidebar"> 
							   <?php
                                if(count($memes)>1){
                                echo "Ses autres Ovni's :";
                               ?>
                               <div id="image-preset">                                                                        
							         <?php 
                                        foreach($memes as $meme):
										if($id!=$meme->meme_id){
                                        $img_url = preg_replace('/\.png$/','_thumb.png',$meme->url);
										$meme_path = Yii::app()->baseUrl.'/'.$meme->meme_type.'/'.$meme->slug;                                      
                                     ?>                                            
									 <?php 
                                       if($meme->meme_type!='videos')  {
									   									   
                                         if($meme->meme_type!='gif-animes' && $meme->meme_type!='images')												 
                                             echo '<a class="add-image" href="'.$meme_path.'"><img src="'.$img_url.'" id="meme_img"/></a>';
										  else
                                             echo '<a class="add-image" href="'.$meme_path.'"><img src="'.Yii::app()->baseUrl.'/timthumb.php?src='.$meme->url.'&w=120&h=120" id="meme_img"></a>';												                                             											
                                        }
                                        else { 
                                          $meme_video  =  explode(',',$meme->file);
                                          if($meme_video[1]=='youtube'){
                                             echo '<a class="add-image" href="'.$meme_path.'">
                                                   <img src="'.Yii::app()->baseUrl.'/timthumb.php?src=http://img.youtube.com/vi/'.$meme_video[0].'/0.jpg&w=120&h=120" id="meme_img">                                     </a>';
                                           } 
                                           else if($meme_video[1]=='viemo'){
                                             $id    = $meme_video[0];
                                             $data  = file_get_contents("http://vimeo.com/api/v2/video/$id.json");
                                             $data  = json_decode($data);
                                             echo '<a class="add-image" href="'.$meme_path.'">
                                             <img src="'.Yii::app()->baseUrl.'/timthumb.php?src='.$data[0]->thumbnail_large.'&w=120&h=120" id="meme_img"/></a>';
                                            } else {
                                              echo '<a class="add-image" href="'.$meme_path.'"><img src="'.Yii::app()->baseUrl.'/timthumb.php?src=http://www.dailymotion.com/thumbnail/video/'.$meme_video[0].'&w=120&h=120" id="meme_img"/></a>'; 
                                             }
                                          }
                                        ?>    
                                        <?php 
										}
										endforeach; ?>
                                 </div>
                                 <?php } ?>
                                 <br />
                                 <?php
								  $fav_users1 = UserFavorite::model()->findAll('favorite_id = :favorite_id', array(':favorite_id' => Yii::app()->request->getParam('id'))); 
								  
								  foreach($fav_users1 as $key => $fav_user){
									 if($fav_user->user_fk && Yii::app()->user->id != $fav_user->user_fk) {
									 $user_fav             = User::model()->findByPk($fav_user->user_fk);
										if(!empty($user_fav)){
										  echo "<span>Ovni's dans les coups de cœur de :<br /></span>";	
										  break;	                                        	
										}
									 }
								  }
										
								 ?>
                                 <div id="image-preset-fav">                                    
                                     <?php									   
									   foreach($fav_users1 as $key => $fav_user){
									     if($fav_user->user_fk && Yii::app()->user->id != $fav_user->user_fk) {
										 $user_fav             = User::model()->findByPk($fav_user->user_fk);
									   ?>                                       
                                          <a class="ttip follow-user-avatar" title="<?php echo CHtml::encode($user_fav->first_name . ' ' . $user_fav->last_name) ?>" href="<?php echo Yii::app()->createUrl('site/profile', array('profile' => $user_fav->username)) ?>"><img src="<?php echo Yii::app()->baseUrl.'/timthumb.php?src='.Yii::app()->user->getAvatar_url($user_fav->user_id).'&w=51&h=52'; ?>" alt="<?php echo CHtml::encode($user_fav->first_name . ' ' . $user_fav->last_name) ?>" /></a>									   
                                       <?php
									     }
									    }							 									 
								  	   ?>                                     
                                 </div>
                            </div>
        
            <script type="text/javascript">

							  	$('.ttip').tooltip({html: true});						    
								$("#favorite_button").click(function(event){
								     event.stopPropagation();							    		  
									 $('#main_container,body').css('overflow','hidden');
									 $('#meme_contentbox1,#meme_popup1').show();
								     $('#meme_popup1').css('height','100%');

								});	
								
								$('.close').click(function(){
								  $('.alert-success').hide();							  
								}); 
								
								
								$("#chat_input")
								  .focus(function() {
										if (this.value === this.defaultValue) {
											this.value = '';
										}
								  })
								  .blur(function() {
										if (this.value === '') {
											this.value = this.defaultValue;
										}
								});
								
								
					
			
								$("#favourite_share").click(function(event){
								      event.stopPropagation();
									   var  formData = "fb_share="+$('#facebook_share_check').prop('checked')+"&meme_url="+$('#meme_url').val();
								      $.ajax({
										url: '<?php echo Yii::app()->baseUrl; ?>/site/favorite/id/<?php echo Yii::app()->request->getParam('id'); ?>',
										data : formData,
										type: "post",
										success: function(response){
										     $('#meme_popup1').hide();
											 $('.alert-success').show();
											 $('.alert-success span').html('Added to the favorite');
									         $('#meme_contentbox1').hide();										
											 $('#userfav_buttons').html(response);													                                 
											 //$('#favorite_button').attr('id','unfavorite_button');											 
										},
										error:function(){	
											alert("failure");
										}
								      });								  
								  });
								  
								  $("#unfavorite_button").click(function(event){
									   event.stopPropagation();
                                       $.ajax({
										url: '<?php echo Yii::app()->baseUrl; ?>/site/unfavorite/id/<?php echo Yii::app()->request->getParam('id'); ?>',
										type: "post",
										success: function(response){
										    $('.alert-success').show();
										    $('.alert-success span').html('Removed from favorite');										     										
											$('#userfav_buttons').html(response);								           									 
										},
										error:function(){	
											alert("failure");
										}
								   });
									  
									   
									   								  
								  });
							  
							  
							   $(document).click(function() {
                                   $('#meme_contentbox').html(" ");
				                   $('#meme_contentbox').hide();
								   //$('#main_container').css('position','relative');
				                   $('#main_container,body').css('overflow','inherit');								   
                               });
							   
							   $(".corgi_feed_well").click(function(event) {									
									event.stopPropagation();
								});
								
								$("#close_popup1").click(function(event){
									 event.stopPropagation();	
									 $('#meme_popup1').hide();
									 $('#meme_contentbox1').hide();
		                        });
								
								
								
								$(".flag_meme").click(function(event) {								 
								  $.ajax({
										url: $(this).attr('href'),
										type: "post",
										success: function(response){										
											 $('.flag_meme').hide();
										},
										error:function(){
											alert("failure");
										}
								  });
								  
								});  
								
								$("#follow_user").click(function(event) {								 
								  $.ajax({
										url: '<?php echo Yii::app()->baseUrl; ?>/site/follow/id/<?php echo $meme->user->user_id; ?>',
										type: "post",
										success: function(response){	
										      $('.alert-success').show();	
										     $('.alert-success span').html('You are now following this user');								
											 $('#follow_button').html(response);
										},
										error:function(){
											alert("failure");
										}
								  });
								  
								});  
								
								$("#unfollow_user").click(function(event) {	
																
								$.ajax({
										url: '<?php echo Yii::app()->baseUrl; ?>/site/unfollow/id/<?php echo $meme->user->user_id; ?>',
										type: "post",
										success: function(response){
										      $('.alert-success').show();	
										     $('.alert-success span').html('You have unfollowed this user');										    											
											 $('#follow_button').html(response);
										},
										error:function(){
											alert("failure");
										}
								  });
								
								});
							   $(document).ready(function(){
							   		     
							   $(".commentForm").submit(function(event){
                            event.preventDefault();
                        });
								
								
								/*$('body').on("click", "#unfollow_user", function() {							   								  
								  $.ajax({
										url: '<?php echo Yii::app()->baseUrl; ?>/site/unfollow/id/<?php echo $meme->user->user_id; ?>',
										type: "post",
										success: function(response){
										    alert(response);
											if(response=='sucess')
											 $('#follow_button').html('<img src="<?php echo Yii::app()->theme->baseUrl; ?>/img/follow.png" id="follow_user"/>');
										},
										error:function(){
											alert("failure");
										}
								  });
								  
								}); */
								
								 var $image_container = $('#image-preset');
								  
								 $wind_height         =  $(document).height();
								   								   
								 $image_container.imagesLoaded( function() {
								  
								   $("#image-preset,#image-preset-fav,.chat_box").mCustomScrollbar({
										scrollButtons: {
											enable: true,
											scrollSpeed: 90
										},
										theme: "dark",
                                        scrollTo: 'last'
								   });
							//	   $('.chat_box').mCustomScrollbar("update");
							
								   $('.chat_box').mCustomScrollbar("scrollTo","last");
								   
								  });
		 
							   });
							</script>
                            <script type="text/javascript" src="<?php echo Yii::app()->theme->baseUrl; ?>/js/chatbox.js"></script>                 
                                                                                                                     
                        </div>
                        <div style="width:303px; float:right;">
                           <div id="fan_like">
                             <fb:like href="https://www.facebook.com/lovelybuzzfr" layout="button_count" action="like" show_faces="true" share="false"></fb:like>
                           </div>
                           <a href="<?php echo Yii::app()->baseUrl.'/generate'; ?>">
						     <img src="<?php echo Yii::app()->theme->baseUrl; ?>/img/create_and_send.png" style="margin-top:6px;"/>
                           </a>                          
                        </div>
                        </div> 
                           
                       
                        <?php /*?><?php */?>
                    </div>
                </div>
            </div>

    
          </div>

       <?php
	   
		
        endforeach;
		
		exit;
	 
    } 
   
     public function actionImages12() {
        $limit = 10;
        $page = Yii::app()->request->getQuery('page');
        $offset = (max($page, 1) - 1) * $limit;
        $action = Yii::app()->request->getParam('action');
        $id = Yii::app()->request->getParam('id');
        $pagination = true;

        $q = new CDbCriteria(array(
            'condition' => 't.is_active = 1 and t.is_published = 1 and user.is_active = 1',
            'join' => 'INNER JOIN user ON t.user_fk = user.user_id',
            'order' => 't.meme_id DESC',
            'limit' => $limit,
            'offset' => $offset,
        ));

        $trendingCriteria = clone $q;
        $trendingCriteria->select = 't.*, COUNT(ml.meme_fk) as meme_liked';
        $trendingCriteria->join .= ' INNER JOIN meme_like ml ON ml.meme_fk = t.meme_id';
        $trendingCriteria->condition .= ' AND DATE(ml.created_at) >= DATE_SUB(ml.created_at, INTERVAL 14 DAY) and DATE(ml.created_at) <= DATE_SUB(ml.created_at, INTERVAL 7 DAY)';
        $trendingCriteria->group = 'ml.meme_fk';
        $trendingCriteria->order = 'meme_liked DESC, t.created_at DESC';

        if ($id) {
            $q->condition .= ' AND t.meme_id = :id';
            $q->params = array(
                ':id' => $id,
            );
            $pagination = false;
        } else if ($action == 'popular') {
            $q->order = 't.likes_count DESC, t.created_at DESC';
        } else if ($action == 'mis-en-avant') {
            $q->condition .= ' AND is_featured = 1';
            $q->order = 't.created_at DESC';
        } else if ($action == 'trending') {
            $q = $trendingCriteria;
        }

        $q->condition .= ' AND t.is_active = 1 AND t.is_published = 1';

        $memes = Meme::model()->findAll($q);

        $count = Meme::model()->count($q);
        $pages = new CPagination($count);

        // results per page
        $pages->pageSize = $limit;
        $pages->applyLimit($q);

        $this->registerSharrre();

        $has_featured_posts = Meme::model()->count('is_featured = 1 and is_active = 1 and is_published = 1');
        $trendingCriteria->limit = 1;
        $has_trending_posts = count(Meme::model()->find($trendingCriteria));
        $trendingCriteria->limit = 0;

        $this->render('index', array(
            'memes' => $memes,
            'pages' => $pages,
            'top_users' => User::model()->top_users,
            'pagination' => $pagination,
            'has_featured_posts' => $has_featured_posts,
            'has_trending_posts' => $has_trending_posts,
            'single' => (bool) $id,
        ));
    }
	
	
	public function actionAnimation() {
        $limit = 10;
        $page = Yii::app()->request->getQuery('page');
        $offset = (max($page, 1) - 1) * $limit;
        $action = Yii::app()->request->getParam('action');
        $id = Yii::app()->request->getParam('id');
        $pagination = true;

        $q = new CDbCriteria(array(
            'condition' => 't.is_active = 1 and t.is_published = 1 and user.is_active = 1',
            'join' => 'INNER JOIN user ON t.user_fk = user.user_id',
            'order' => 't.meme_id DESC',
            'limit' => $limit,
            'offset' => $offset,
        ));

        $trendingCriteria = clone $q;
        $trendingCriteria->select = 't.*, COUNT(ml.meme_fk) as meme_liked';
        $trendingCriteria->join .= ' INNER JOIN meme_like ml ON ml.meme_fk = t.meme_id';
        $trendingCriteria->condition .= ' AND DATE(ml.created_at) >= DATE_SUB(ml.created_at, INTERVAL 14 DAY) and DATE(ml.created_at) <= DATE_SUB(ml.created_at, INTERVAL 7 DAY)';
        $trendingCriteria->group = 'ml.meme_fk';
        $trendingCriteria->order = 'meme_liked DESC, t.created_at DESC';

        if ($id) {
            $q->condition .= ' AND t.meme_id = :id';
            $q->params = array(
                ':id' => $id,
            );
            $pagination = false;
        } else if ($action == 'popular') {
            $q->order = 't.likes_count DESC, t.created_at DESC';
        } else if ($action == 'mis-en-avant') {
            $q->condition .= ' AND is_featured = 1';
            $q->order = 't.created_at DESC';
        } else if ($action == 'trending') {
            $q = $trendingCriteria;
        }

        $q->condition .= ' AND t.is_active = 1 AND t.is_published = 1';

        $memes = Meme::model()->findAll($q);

        $count = Meme::model()->count($q);
        $pages = new CPagination($count);

        // results per page
        $pages->pageSize = $limit;
        $pages->applyLimit($q);

        $this->registerSharrre();

        $has_featured_posts = Meme::model()->count('is_featured = 1 and is_active = 1 and is_published = 1');
        $trendingCriteria->limit = 1;
        $has_trending_posts = count(Meme::model()->find($trendingCriteria));
        $trendingCriteria->limit = 0;

        $this->render('index', array(
            'memes' => $memes,
            'pages' => $pages,
            'top_users' => User::model()->top_users,
            'pagination' => $pagination,
            'has_featured_posts' => $has_featured_posts,
            'has_trending_posts' => $has_trending_posts,
            'single' => (bool) $id,
        ));
    }
	
	
	public function actionMessages() {
		   
	    $id = Yii::app()->request->getParam('id');
        $memes = array();
        if($id) {  
		     	   	  
			$q = new CDbCriteria(array(
					'condition' => 't.is_published = 1',
					'join' => 'INNER JOIN user ON t.user_fk = user.user_id',
					'order' => 't.meme_id DESC',
			));		
			
			
			$trendingCriteria = clone $q;
			$trendingCriteria->select = 't.*, COUNT(ml.meme_fk) as meme_liked';
			$trendingCriteria->join .= ' INNER JOIN meme_like ml ON ml.meme_fk = t.meme_id';
			$trendingCriteria->condition .= ' AND DATE(ml.created_at) >= DATE_SUB(ml.created_at, INTERVAL 14 DAY) and DATE(ml.created_at) <= DATE_SUB(ml.created_at, INTERVAL 7 DAY)';
			$trendingCriteria->group = 'ml.meme_fk';
			$trendingCriteria->order = 'meme_liked DESC, t.created_at DESC';
	
			if ($id) {
				 $q->condition .= ' AND t.slug = :slug';
				$q->params = array(
					 ':slug' => $id,
				);
				$pagination = false;
			}
	
			$memes = Meme::model()->findAll($q);
	    }
		
		$this->render('messages', array(
            'memes' => $memes,          
        ));

   } 
	
    public function actionIndex() {
	
        $limit      =  20;
        $page       =  Yii::app()->request->getQuery('page');
        $offset     =  (max($page, 1) - 1) * $limit;
        $action     =  Yii::app()->request->getParam('action');
        $id         =  Yii::app()->request->getParam('id');
		
		$title      =  Yii::app()->request->getParam('title');		
		
		$meme_type  =  $title;
		
        $pagination =  true;

       	   	  
	    if ($action != 'recent' && $id=='') {
			 $q = new CDbCriteria(array(
				'condition' => 't.is_active = 1 and t.is_published = 1 and user.is_active = 1',
				'join' => 'INNER JOIN user ON t.user_fk = user.user_id',
				'order' => 't.meme_id DESC',
				'limit' => $limit,
				'offset' => $offset,
			 ));
        } else {	
		    $q = new CDbCriteria(array(
				'condition' => 't.is_published = 1',
				'join' => 'INNER JOIN user ON t.user_fk = user.user_id',
				'order' => 't.meme_id DESC',
				'limit' => $limit,
				'offset' => $offset,
			 ));		
		}
		
		
		
        $trendingCriteria = clone $q;
        $trendingCriteria->select = 't.*, COUNT(ml.meme_fk) as meme_liked';
        $trendingCriteria->join .= ' INNER JOIN meme_like ml ON ml.meme_fk = t.meme_id';
        $trendingCriteria->condition .= ' AND DATE(ml.created_at) >= DATE_SUB(ml.created_at, INTERVAL 14 DAY) and DATE(ml.created_at) <= DATE_SUB(ml.created_at, INTERVAL 7 DAY)';
        $trendingCriteria->group = 'ml.meme_fk';
        $trendingCriteria->order = 'meme_liked DESC, t.created_at DESC';

        if ($id) {
             $q->condition .= ' AND t.slug = :slug';
            $q->params = array(
                 ':slug' => $id,
            );
            $pagination = false;
        } else if ($action == 'popular') {
            $q->order = 't.likes_count DESC, t.created_at DESC';
        } else if ($action == 'mis-en-avant') {
            $q->condition .= ' AND is_featured = 1';
            $q->order = 't.created_at DESC';
        } else if ($action == 'trending') {
            $q = $trendingCriteria;
        }
		
		if($title!='')
        $q->condition .= ' AND t.meme_type = "'.$meme_type.'"';
				       	   
	    if ($action != 'recent' && $id=='')
	    $q->condition .= ' AND t.is_active = 1 AND t.is_published = 1';

        $memes = Meme::model()->findAll($q);


        $count = Meme::model()->count($q);
        $pages = new CPagination($count);
			
		$fav_users = UserFavorite::model()->findAll('favorite_id = :favorite_id', array(':favorite_id' => $memes[0]->meme_id));

        // results per page
        $pages->pageSize = $limit;
        $pages->applyLimit($q);

        $this->registerSharrre();

        $has_featured_posts = Meme::model()->count('is_featured = 1 and is_active = 1 and is_published = 1');
        $trendingCriteria->limit = 1;
        $has_trending_posts = count(Meme::model()->find($trendingCriteria));
        $trendingCriteria->limit = 0;


        $this->render('index', array(
            'memes'      => $memes,
            'pages'      => $pages,
			'fav_users'  => $fav_users,
            'top_users'  => User::model()->top_users,
            'pagination' => $pagination,
            'has_featured_posts' => $has_featured_posts,
            'has_trending_posts' => $has_trending_posts,
            'single' => (bool) $id,
        ));
    }
    
	public function actionLoadmore() {
	
	    $limit = 20;

        $page = Yii::app()->request->getQuery('page');
        $offset = (max($page, 1) - 1) * $limit;
        $action = Yii::app()->request->getParam('action');
        $id = Yii::app()->request->getParam('id');		
		$title      =  Yii::app()->request->getParam('title');	
		
			   
		if($title!=''){
			if($title!='gifanimes' && $title!='detournement-images')	
			   $meme_type  = $title;
			else if($title=='detournement-images')
			   $meme_type  = 'meme';
			else
			   $meme_type  = 'gif';
		}
				
        $pagination = true;

        $q = new CDbCriteria(array(
            'condition' => 't.is_active = 1 and t.is_published = 1 and user.is_active = 1',
            'join' => 'INNER JOIN user ON t.user_fk = user.user_id',
            'order' => 't.meme_id DESC',
            'limit' => $limit,
            'offset' => $offset,
        ));

        $trendingCriteria = clone $q;
        $trendingCriteria->select = 't.*, COUNT(ml.meme_fk) as meme_liked';
        $trendingCriteria->join .= ' INNER JOIN meme_like ml ON ml.meme_fk = t.meme_id';
        $trendingCriteria->condition .= ' AND DATE(ml.created_at) >= DATE_SUB(ml.created_at, INTERVAL 14 DAY) and DATE(ml.created_at) <= DATE_SUB(ml.created_at, INTERVAL 7 DAY)';
        $trendingCriteria->group = 'ml.meme_fk';
        $trendingCriteria->order = 'meme_liked DESC, t.created_at DESC';

        if ($id) {
            $q->condition .= ' AND t.meme_id = :id';
            $q->params = array(
                ':id' => $id,
            );
            $pagination = false;
        } else if ($action == 'popular') {
            $q->order = 't.likes_count DESC, t.created_at DESC';
        } else if ($action == 'mis-en-avant') {
            $q->condition .= ' AND is_featured = 1';
            $q->order = 't.created_at DESC';
        } else if ($action == 'trending') {
            $q = $trendingCriteria;
        }
        
		if($title!='')
        $q->condition .= ' AND t.meme_type = "'.$meme_type.'"';
		
        $q->condition .= ' AND t.is_active = 1 AND t.is_published = 1';

        $memes = Meme::model()->findAll($q);
		
		$meme_output = "";
		
		foreach($memes as $meme){
		
		  $memeUrl             = $meme->post_url;
		  
		  $meme_path = Yii::app()->baseUrl.'/'.$meme->meme_type.'/'.$meme->slug;
			   
		  
		  $meme_img            = '<img src="'.Yii::app()->theme->baseUrl.'/img/'.$meme->meme_type.'.png">';
		  
		  
		  if($meme->meme_type!='videos')  { 
		     list($width_,$height_) = getimagesize('http://'.$_SERVER['SERVER_NAME'].Yii::app()->baseUrl.'/timthumb.php?src='.$meme->url.'&w=239&h=auto');
			 $meme_main  = '<img src="'.Yii::app()->baseUrl.'/timthumb.php?src='.$meme->url.'&w=239&h=auto" id="meme_img" style="height:'.$height_.'px"/>';
          } else { 
			$meme_video  =  explode(',',$meme->file);
			if($meme_video[1]=='youtube'){			
			   $meme_main = '<img src="http://img.youtube.com/vi/'.$meme_video[0].'/0.jpg" id="meme_img" style="height:183px;">';
			} else if($meme_video[1]=='viemo'){
			  $id    = $meme_video[0];
			  $data  = file_get_contents("http://vimeo.com/api/v2/video/$id.json");
              $data  = json_decode($data);
			  $meme_main = '<img src="'.$data[0]->thumbnail_large.'" id="meme_img" style="height:183px;"/>';
			} else {
			   $meme_main = '<img src="http://www.dailymotion.com/thumbnail/video/'.$meme_video[0].'" id="meme_img" style="height:183px;"/>'; 
			}
		  } 
		  
		  $meme_output .= '<div class="item detourme">
					       <div class="overlay_box more_view" id="'.$meme->meme_id.'">
					       <div class="meme_type">'.$meme_img.'</div>       
					       <div class="mid_cont">
					       <br />      
					       </div>
					       <div class="meme_title"><a href="'.$meme_path.'">'.CHtml::encode($meme->title).'</a></div>
					       </div>'.$meme_main.'</div>';
						   		
		}	
		
		if(Yii::app()->user->isGuest && $page==3)
		 $meme_output = 'login';
				
		echo $meme_output;	
		exit;
	
    }

    public function actionDelete() {
        $id = Yii::app()->request->getParam('id');
        $meme = Meme::model()->findByPk($id);
        if ($meme) {
            if (Yii::app()->user->checkAccess('ownMeme', array('meme' => $meme))) {
                $meme->delete();
                Yii::app()->plugin->onMemeDelete(new CEvent($meme));
                Utility::setFlash(Yii::t('yii', 'Meme deleted!'), 'success');
            } else {
                Utility::setFlash(Yii::t('yii', 'You are not allowed to delete this meme!'), 'error');
            }
        }

        Yii::app()->request->redirect(Yii::app()->createUrl('site/mymemes'));
    }

    public function actionPublish() {
        $id = Yii::app()->request->getParam('id');
        $meme = Meme::model()->findByPk($id);
        if ($meme) {
            if (Yii::app()->user->checkAccess('ownMeme', array('meme' => $meme))) {
                Utility::setFlash(Yii::t('yii', 'Meme ' . ($meme->is_published ? 'UnPublished' : 'Published') . '!'), 'success');
                $meme->saveAttributes(array('is_published' => !$meme->is_published));
                Yii::app()->plugin->onMemePublished(new CEvent($meme));
            } else {
                Utility::setFlash(Yii::t('yii', 'You are not allowed to that!'), 'error');
            }
        }

        Yii::app()->request->redirect(Yii::app()->createUrl('site/mymemes'));
    }

    public function actionDownload() {
        $id = Yii::app()->request->getParam('id');
        $meme = Meme::model()->findByPk($id);
        if ($meme) {
            //if (Yii::app()->user->checkAccess('ownMeme', array('meme' => $meme))) {
                Yii::app()->plugin->onMemeDownload(new CEvent($meme));
                return Yii::app()->getRequest()->sendFile(basename($meme->absolute_path), @file_get_contents($meme->absolute_path));
            //} else {
                Utility::setFlash(Yii::t('yii', 'You are not allowed to that!'), 'error');
            //}
        }

        Yii::app()->request->redirect(Yii::app()->createUrl('site/mymemes'));
    }

    protected function registerSharrre() {

        $baseUrl = Yii::app()->theme->baseUrl;
        $twitterUser = Yii::app()->params['hauth']['config']['providers']['Twitter']['username'];
        $js = <<<JS
        $('.social-share').sharrre({
               urlCurl: '{$baseUrl}/js/sharrre/sharrre.php',
               share: {
                   googlePlus: true,
                   facebook: true,
                   twitter: true
               },
               buttons: {
                   googlePlus: {size: 'tall', annotation: 'bubble'},
                   facebook: {layout: 'box_count'},
                   twitter: {count: 'vertical', via: '$twitterUser'}
               },
               hover: function(api, options) {
                   $(api.element).find('.buttons').show();
               },
               hide: function(api, options) {
                   $(api.element).find('.buttons').hide();
               },
               enableTracking: true
           });
JS;

        Yii::app()->clientScript
                ->registerScriptFile($baseUrl . '/js/sharrre/jquery.sharrre-1.3.4.js')
                ->registerScript('sharrre-init', $js, CClientScript::POS_END);
    }

    /**
     * This is the action to handle external exceptions.
     */
    public function actionError() {
        if ($error = Yii::app()->errorHandler->error) {
            if (Yii::app()->request->isAjaxRequest)
                echo $error['message'];
            else
                $this->render('error', $error);
        }
    }

    /**
     * Displays the contact page
     */
    public function actionContact() {
        $model = new ContactForm;
        if (isset($_POST['ContactForm'])) {
            $model->attributes = $_POST['ContactForm'];
            if ($model->validate()) {
                $name = '=?UTF-8?B?' . base64_encode($model->name) . '?=';
                $subject = '=?UTF-8?B?' . base64_encode($model->subject) . '?=';
                $headers = "From: $name <{$model->email}>\r\n" .
                        "Reply-To: {$model->email}\r\n" .
                        "MIME-Version: 1.0\r\n" .
                        "Content-type: text/plain; charset=UTF-8";

                mail(Yii::app()->params['adminEmail'], $subject, $model->body, $headers);
                Yii::app()->user->setFlash('contact', Yii::t('yii', 'Thank you for contacting us. We will respond to you as soon as possible.'));
                $this->refresh();
            }
        }
        $this->render('contact', array('model' => $model));
    }

    /**
     * Displays the login page
     */
    public function actionLoginNormal() {
        $model = new LoginForm;

        // if it is ajax validation request
        if (isset($_POST['ajax']) && $_POST['ajax'] === 'login-form') {
            echo CActiveForm::validate($model);
            Yii::app()->end();
        }

        // collect user input data
        if (isset($_POST['LoginForm'])) {
            $model->attributes = $_POST['LoginForm'];
            // validate user input and redirect to the previous page if valid
            if ($model->validate() && $model->login()) {			    
                Yii::app()->plugin->onUserLogin(new CEvent($model));
				
				$user              = User::model()->findByPk(Yii::app()->user->id);
				$followers = $user->followers(array('order' => 'followers.created_at DESC', 'with' => 'follower', 'scopes' => 'follower_visible'));
				
				if(count($followers)<3)
                 $this->redirect(Yii::app()->homeUrl.'followuser/'.Yii::app()->user->id);
			   
			    $this->redirect(Yii::app()->baseUrl.'/mis-en-avant');
            }
        }
        // display the login form

        if (Yii::app()->request->isAjaxRequest) {
            $this->renderPartial('login', array('model' => $model));
        } else {
            $this->render('login', array('model' => $model));
        }
    }

    public function loadUserLikes() {
        $likes = MemeLike::model()->findByAttributes(array('user_fk' => Yii::app()->user->getId()));
        $user_likes = array();
        foreach ($likes as $like) {
            $user_likes[] = $like->meme_fk;
        }
        Yii::app()->session[Meme::LIKE_KEY] = $user_likes;
    }

    //action only for the login from third-party authentication providers, such as Google, Facebook etc. Not for direct login using username/password
    public function actionLogin() {
        if (!isset($_GET['provider'])) {
            $this->forward('site/loginNormal');
            return;
        }

        try {
            Yii::import('application.components.HybridAuthIdentity');
            $haComp = new HybridAuthIdentity();
            if (!$haComp->validateProviderName($_GET['provider']))
                throw new CHttpException('500', Yii::t('yii', 'Invalid Action. Please try again.'));

            $haComp->adapter = $haComp->hybridAuth->authenticate($_GET['provider']);
            $haComp->userProfile = $haComp->adapter->getUserProfile();
            $haComp->provider = strtolower($_GET['provider']);
            $haComp->login();
			
			
            $user              = User::model()->findByPk(Yii::app()->user->id);
			
			$q                 = new CDbCriteria(array(
								'condition' => 't.user_fk = '.Yii::app()->user->id,
            ));
 						
            $followers         = UserFollow::model()->findAll($q);	
						
			if(count($followers)<3)
			$this->redirect(Yii::app()->homeUrl.'followuser/'.Yii::app()->user->id);
			
            if (Yii::app()->user->returnUrl) {
                Yii::app()->plugin->onUserLogin(new CEvent($haComp));
                $this->redirect(Yii::app()->baseUrl.'/mis-en-avant');
            }

           
			
			
            $haComp->processLogin();  //further action based on successful login or re-direct user to the required url
        } catch (Exception $e) {
            //process error message as required or as mentioned in the HybridAuth 'Simple Sign-in script' documentation
            $this->redirect('/ovnis/site/index');
            return;
        }
    }

    public function actionMemeLike() {
        $out = array('error' => true);
        $id = Yii::app()->request->getParam('id');

        if (!Yii::app()->user->isGuest && ($meme = Meme::model()->findByPk($id))) {
            $out['error'] = !Meme::like($meme);
        }

        echo json_encode($out);
    }

    public function actionMemeUnlike() {
        $out = array('error' => true);
        $id = Yii::app()->request->getParam('id');

        if (!Yii::app()->user->isGuest && ($meme = Meme::model()->findByPk($id))) {
            $out['error'] = !Meme::unlike($meme);
        }

        echo json_encode($out);
    }

    public function actionSocialLogin() {
        Yii::import('application.components.HybridAuthIdentity');
        $path = Yii::getPathOfAlias('ext.HybridAuth');
        require_once $path . '/hybridauth-' . HybridAuthIdentity::VERSION . '/hybridauth/index.php';
    }

    /**
     * Logs out the current user and redirect to homepage.
     */
    public function actionLogout() {
        Yii::app()->user->logout();
        $this->redirect(Yii::app()->homeUrl);
    }

    /**
     * Displays the registeration page
    */
    public function actionRegister() {
        $model = new RegisterForm;

        // if it is ajax validation request
        if (isset($_POST['ajax']) && $_POST['ajax'] === 'register-form') {
            echo CActiveForm::validate($model);
            Yii::app()->end();
        }

        // collect user input data
        if (isset($_POST['RegisterForm'])) {
            $model->attributes = $_POST['RegisterForm'];
            // validate user input and redirect to the previous page if valid
            if ($model->validate()) {
                $user = new User();
                $user->attributes = $_POST['RegisterForm'];
                $user->password   = md5($user->password);
                $user->token      = md5(uniqid());
                $user->is_admin   = 0;
				$user->is_active  = 1;
                $user->created_at = new CDbExpression('NOW()');
                if ($user->save()) {
					
				    $message = new MyYiiMailMessage();
                    $message->view = 'registeration';

                    
					//userModel is passed to the view
                    
					 $message->setBody(array(
                       'model' => $user,
                       'verify_url' => $this->createAbsoluteUrl('site/email_verify', array('code' => $user->token,))
                        ), 'text/html');
				

                    $message->addTo($user->email);
                    $message->setFrom(array(Yii::app()->params['adminEmail'] => Yii::app()->name));
                    $message->setSubject(Yii::t('yii', 'Successfully registerd!'));
                    Yii::app()->mail->send($message);
                    Utility::setFlash(Yii::t('yii', 'You have been sucessfully registered to the application.'), 'success');
					
					$identity = new UserIdentity($user->email,$user->password);
					$identity->authenticate();
					//$identity->setId($user->user_id);
                    Yii::app()->user->login($identity,60 * 20);
					
                    Yii::app()->plugin->onUserRegister(new CEvent($user));
																		

                    $this->redirect(Yii::app()->homeUrl.'followuser/'.$user->user_id);
                    //echo $message->getBody();exit;
                }
            }
        }
        // display the registeration form
        $this->render('register', array('model' => $model));
    }
	
	
	
	/**
    * Displays the user follow page
    */
	
    public function actionFollowuser() {	
		  
		 
		  if(isset($_REQUEST['yt0']) && $_REQUEST['yt0']!=''){
		               
			 foreach($_REQUEST['user_id'] as $id) {	
				  
				  $user_fk = $id;				 		
				  		 
				  if ($user_fk && !Yii::app()->user->checkAccess('ownId', $user_fk)) {
					$user = User::follow($user_fk);
				  }			
				    
		    }									
			 
			Utility::setFlash(Yii::t('yii', 'You are now following the selected users'), 'success');
			
			$this->redirect(Yii::app()->homeUrl);
			
		  }		  
		  else {
		   	
	       $q      = new CDbCriteria(array(
                    'condition' => 't.is_active = 1 AND t.set_list = 1',
                    'order'     => 't.list_order ASC',
           ));
 						
           $users = User::model()->findAll($q);	    		  		  
		   	        
           $this->render('followuser',array('users' => $users));
	     }
	}
	
	
	

    public function actionEmail_verify() {
        $code = trim(Yii::app()->request->getQuery('code'));
        if ($code) {
            $user = User::model()->findByAttributes(array('token' => $code));
            if ($user) {
                $user->is_active = 1;
                $user->token = md5(uniqid());
                $user->save();
                Yii::app()->plugin->onUserEmailVerified(new CEvent($user));
                Utility::setFlash(Yii::t('yii', 'Your email has been verified. Please login below.'), 'success');
                Yii::app()->request->redirect('login');
            }
        }
        echo Yii::t('yii', 'invalid code');
        Yii::app()->end();
    }

    public function actionUpdate_profile() {
        

		$model             = new UserProfileForm();
        $user              = User::model()->findByPk(Yii::app()->user->id);
        $model->first_name = $user->first_name;
        $model->last_name  = $user->last_name;

        if (isset($_POST['UserProfileForm'])) {
            $model->attributes     = $_POST['UserProfileForm'];

            if ($model->validate()) {
                $model->attributes = $_POST['UserProfileForm'];
								

                $user->first_name         =  $model->first_name;
                $user->last_name          =  $model->last_name;
				$user->user_description   = $_POST['user_description'];
				
                $user->save();

                $file = basename($model->avatar);
                $uploaded_avatar_thumb  = Yii::getPathOfAlias('webroot.' . Yii::app()->params['upload_dir']) . DIRECTORY_SEPARATOR . 'thumbnail' . DIRECTORY_SEPARATOR . $file;
                $uploaded_avatar_medium = Yii::getPathOfAlias('webroot.' . Yii::app()->params['upload_dir']) . DIRECTORY_SEPARATOR . 'medium' . DIRECTORY_SEPARATOR . $file;
                $uploaded_avatar_large  = Yii::getPathOfAlias('webroot.' . Yii::app()->params['upload_dir']) . DIRECTORY_SEPARATOR . $file;

                if ($file && file_exists($uploaded_avatar_thumb)) {
                    $avatar = Yii::getPathOfAlias('webroot.' . Yii::app()->params['avatar_dir']) . DIRECTORY_SEPARATOR . Yii::app()->user->avatar;

                    $img = imagecreatefromstring(file_get_contents($uploaded_avatar_thumb));
                    imagesavealpha($img, true);
                    imagepng($img, $avatar);

                    @unlink($uploaded_avatar_large);
                    @unlink($uploaded_avatar_medium);
                    @unlink($uploaded_avatar_thumb);
                }
                Yii::app()->plugin->onUserProfileUpdated(new CEvent($user));
                Utility::setFlash(Yii::t('yii', 'Your profile has been updated successfully.'), 'success');
            }
        }

        $this->render('update_profile', array(
            'model' => $model,
            'user' => $user,
        ));
    }
	
	
	 public function actionProfilelist() {
	 
	    $page      =   Yii::app()->request->getQuery('page');
	    
		echo $page;
	   
	 
	 }


    public function actionProfile() {
	 
        $username  =   Yii::app()->request->getParam('profile');
		$limit     =   10;
        $page      =   Yii::app()->request->getQuery('page');
		$type      =   Yii::app()->request->getQuery('type');						
				
        $offset    =   (max($page, 1) - 1) * $limit;		

        if ($user = User::model()->findByAttributes(array('username' => $username))) {
           
		    $q = new CDbCriteria(array(
                'condition' => 't.is_active = 1 AND t.is_published = 1 AND t.user_fk = :user_id',
                'params' => array(':user_id' => $user->user_id),
                'order'  => 't.meme_id DESC',
				'limit'  => $limit,
				'offset' => $offset
            ));
			
			if(isset($type) && $type=='favorite') {
		     $q               =   new CDbCriteria(array(
							 'condition' => 'user_favorite.user_fk = :user_fk',
							 'join' => 'INNER JOIN user_favorite ON t.meme_id = user_favorite.favorite_id',
							 'params' => array(':user_fk' => $user->user_id),
							 'order' => 'user_favorite.created_at DESC',
							 'limit' => $limit,
                             'offset' => $offset,							 
							 ));
			}
            $memes = Meme::model()->findAll($q);
					
          //  $this->registerSharrre();

           if($page=='') {
				$this->render('user_profile', array(
					'user' => $user,
					'memes' => $memes,
					'total_posts' => $count = Meme::model()->count($q),
				));
			} else {
			  foreach ($memes as $i =>$meme){
			  $meme_url   = 'http://'.$_SERVER['SERVER_NAME'].Yii::app()->baseUrl.'/'.$meme->meme_type.'/'.$meme->slug;
			?>
            <tr style="width:650px;" class="user_pro_row">
              
                <td>
                 <div id="meme_contentbox1" style="display:none;">
                  <div id="meme_popup1" style="">                                           
                      <span  id="close_popup1" style=" float:right; padding:10px 20px 0px 0px;">
                        <img src="<?php echo Yii::app()->theme->baseUrl; ?>/img/close_button.png" />
                      </span>                                   
                 
                       <div style="margin:auto; width:487px;">
                         <div style="width:487px;  text-align:center;min-height:300px; margin:130px auto 130px auto; background:#FFFFFF; float:left;padding:0px 0px 15px 0px;">
                             
                             <h2><?php echo CHtml::encode($meme->title) ?></h2>    
                                          
                              <?php if($meme->meme_type!='videos')  { ?>                      
                              <img class="meme-img" src="<?php echo $meme->url ?>" alt="<?php echo CHtml::encode($meme->title) ?>" width="430"/>
                              <?php } else {                             
                                $meme_video  =  explode(',',$meme->file);               
                                if($meme_video[1]=='youtube'){
                                  echo '<iframe width="430" height="342" src="//www.youtube.com/embed/'.$meme_video[0].'?rel=0" frameborder="0" allowfullscreen></iframe>';
                                } else if($meme_video[1]=='viemo'){	                          
                                  echo '<iframe src="//player.vimeo.com/video/'.$meme_video[0].'" width="430" height="342" frameborder="0" allowfullscreen></iframe>';
                                } else {
                                   echo '<iframe frameborder="0" width="430" height="342" src="http://www.dailymotion.com/embed/video/'.$meme_video[0].'"></iframe>'; 
                                }
                               }
                               ?>    
                               <div style=" text-align:left; padding-left:27px; line-height:44px;">
                                  <?php
                                  if(!Yii::app()->user->isGuest){ 
                                    $user_type   =  User::model()->findByAttributes(array('user_id' => Yii::app()->user->id));
                                    if($user_type->token==''){
                                      echo '<input type="checkbox" name="facebook_share" id="facebook_share" value="1"/>&nbsp;<b>Partager sur Facebook</b>';
                                    }
                                  }
                                  ?>                              
                                  <input type="hidden" name="meme_url" id="meme_url" value="<?php //echo $meme_url; ?>"/>
                                  <img src="<?php echo Yii::app()->theme->baseUrl; ?>/img/favourite_share.png" style="float:right; margin-right:27px; margin-top:5px;" class="favourite_share" id="<?php echo $meme->meme_id; ?>"/>
                               </div>                                               
                          </div>
                       </div>                  
                    </div>
                 </div>
               
                </td>
                <td class="mymeme_type">                
                  <?php         
				   echo '<img src="'.Yii::app()->theme->baseUrl.'/img/'.$meme->meme_type.'_b.png" />';         				  	  
				  ?>                                      
                </td>
                
                <td class="text-center mymeme text-middle <?php if($meme->meme_type=='videos') echo 'video_ovniscont profile_video'; ?>" width="240">
                     <div id="favorite_meme_button">
						   <?php
                            $check_favorite   =  UserFavorite::model()->findByAttributes(array('user_fk' => Yii::app()->user->id, 'favorite_id' => $meme->meme_id));
							if(Yii::app()->user->id!=$meme->user_fk) {
								if($check_favorite){                                  
								  if(!Yii::app()->user->isGuest)
									echo '<div id="userfav_buttons"><span id="'.$meme->meme_id.'" class="unfavorite_button"><img src="'.Yii::app()->theme->baseUrl.'/img/mattre_dans_fav.png"/></span></div>';
								  else
									echo '<a href="'.Yii::app()->createUrl('login').'"><img src="'.Yii::app()->theme->baseUrl.'/img/mattre_dans_fav.png"/></a>';
							   
								} else {                              
								   if(!Yii::app()->user->isGuest)
									echo '<div id="userfav_buttons"><span class="favorite_button_user"><img src="'.Yii::app()->theme->baseUrl.'/img/mattre_dans.png"/></span></div>';
								   else
									echo '<a  href="'.Yii::app()->createUrl('login').'"><img src="'.Yii::app()->theme->baseUrl.'/img/mattre_dans.png"/></a>';
								} 
							 }                                    
                            ?>
                      </div>
                      
                    <?php
                    if($meme->meme_type!='videos')  { 
					?><a class="ttip ovnis_download" title="<?php echo Yii::t('yii', 'download') ?>" href="<?php echo Yii::app()->createUrl('site/download', array('id' => $meme->meme_id)) ?>"><i class="icon-download"><img src="<?php echo Yii::app()->theme->baseUrl; ?>/img/download.png" /></i></a>
                    <?php } ?>
                      
                      <?php if($meme->meme_type!='videos')  { ?>                      
                        <a href="<?php echo $meme_url; ?>" target="_blank" id="ovnis_cont"><img src="<?php echo $meme->url ?>" /></a>
                      <?php } else { 
                        
                        $meme_video  =  explode(',',$meme->file);
            
                        if($meme_video[1]=='youtube'){
                          echo '<iframe width="238" height="230" src="//www.youtube.com/embed/'.$meme_video[0].'?rel=0" frameborder="0" allowfullscreen id="ovnis_cont"></iframe>';
                        } else if($meme_video[1]=='viemo'){	                          
                          echo '<iframe src="//player.vimeo.com/video/'.$meme_video[0].'" width="238" height="230" frameborder="0" allowfullscreen id="ovnis_cont"></iframe>';
                        } else {
                           echo '<iframe frameborder="0" width="238" height="230" src="http://www.dailymotion.com/embed/video/'.$meme_video[0].'" id="ovnis_cont"></iframe>'; 
                        }
                       }
                      ?>    
                   
                   
                   <br />
                    <a href="<?php echo $meme_url; ?>" target="_blank"><b><?php echo CHtml::encode($meme->title) ?></b></a>
                    
                   <div class="userProf_feed">
                   
				   <?php if(Yii::app()->user->id != $meme->user->user_id) { ?>
                         <span style="float:left; margin-top:5px;">
                      <a href="<?php echo Yii::app()->createUrl('site/profile', array('profile' => $meme->user->username)) ?>" style="float:left; padding-right:5px;"><img src="<?php echo Yii::app()->user->getAvatar_url($meme->user_fk) ?>" alt="<?php echo CHtml::encode("{$meme->user->first_name} {$meme->user->last_name}") ?>" title="<?php echo CHtml::encode("{$meme->user->first_name} {$meme->user->last_name}") ?>" class="meta_image ttip" /></a> <span style="float:left; text-align:left; width:165px;"> par <br /> <b><a href="<?php echo Yii::app()->createUrl('site/profile', array('profile' => $meme->user->username)) ?>"><?php echo $meme->user->first_name.'&nbsp;'.$meme->user->last_name; ?></a></b></span>
                     </span> 
                    <?php } ?>   
                    
                   </div> 
                        
                </td>
                <td class="text-center profile_page chat_mod text-middle" width="480">					           
                    <link rel="stylesheet" type="text/css" href="<?php echo Yii::app()->theme->baseUrl; ?>/js/content-scroller/jquery.mCustomScrollbar.css" />
			        <script type="text/javascript" src="<?php echo Yii::app()->theme->baseUrl; ?>/js/content-scroller/jquery.mCustomScrollbar.concat.min.js"></script>
                    <!-- Chat Box Start -->                    
                    <div class="chat_box" onmouseover="Stop_Scroll()">
                        <div class="chat_inner  chat_inner_<?= $meme->meme_id ?>">
                         
                       </div> 
                    </div>
                    
                    <div class="chat_input">
                    <?php 
					if(!Yii::app()->user->isGuest) {
					
					?>
                       <form action=""  class='commentForm' style="float:left; width:93%; margin-bottom:0px;">
                          <input type='text' name="chat_input" id="chat_input"  data-room='<?= $meme->meme_id ?>' class='msginput' value='Commentez, Réagissez ici ..' />
                       </form>
                       <img src="<?php echo Yii::app()->theme->baseUrl; ?>/img/chat_expand.png" data-original-title="Ouvrir cette conversation dans une fenêtre plus grande" class="ttip chat_expand" data-room='<?= $meme->meme_id ?>'/>
                    <?php } else { ?>
                        <a href="<?php echo Yii::app()->baseUrl; ?>/login" id="comments_link">Connectes-toi</a> ou <a href="<?php echo Yii::app()->baseUrl; ?>/register" id="comments_link">Enregistres-toi</a> pour commenter et réagir ...
                    <?php } ?>
                    
                    </div>

                  <div class="chat_popup chat_popup_<?= $meme->meme_id ?>" style="display:none;">                        
                    <div class="chatpopup_inner chat_mod">
                           
                          <div class="chat_header">
                                                <?php 
                                                $meme_url   = 'http://'.$_SERVER['SERVER_NAME'].Yii::app()->baseUrl.'/'.$meme->meme_type.'/'.$meme->slug;

                                                 if($meme->meme_type!='videos')  {
                                                ?>
                                                              <img src="<?php echo Yii::app()->baseUrl.'/timthumb.php?src='.$meme->url.'&w=52&h=53'; ?>"/> 
                                                              <?php
                                                } else {
                                                
                                                  $meme_video  =  explode(',',$meme->file);
                                                    
                                                  if($meme_video[1]=='youtube'){
                                                   echo '<img src="'.Yii::app()->baseUrl.'/timthumb.php?src=http://img.youtube.com/vi/'.$meme_video[0].'/default.jpg&w=w=52&h=53"/> '; 
                                                  } else if($meme_video[1]=='viemo'){
                                                     $vimeo_thumb  = SiteController::get_vimeo_thumb($meme_video[0]);                           
                                                     echo '<img src="'.Yii::app()->baseUrl.'/timthumb.php?src='.$vimeo_thumb.'/default.jpg&w=52&h=53"/>'; 
                                                  } else {
                                                    echo '<img src="'.Yii::app()->baseUrl.'/timthumb.php?src=http://www.dailymotion.com/thumbnail/video/'.$meme_video[0].'&w=52&h=53"/> ';
                                                  }
                                                  
                                                }
                                                 ?>
                                              &nbsp;<b><?php echo CHtml::encode($meme->title) ?></b>
                                                    <img src="<?php echo Yii::app()->theme->baseUrl;?>/img/close_button.png"  class="chat_close" width="60"/>
                                               </div>
                           
                           <div class="chat_box_popup">
                           <div class="chat_inner chat_inner_<?= $meme->meme_id ?>">
                 
                            </div>
                           </div>
                           
                            <div class="chat_input">
                                   <form action=""  class='commentForm' style="float:left; width:93%; margin-bottom:0px;">
                          <input type='text' name="chat_input" id="chat_input"  data-room='<?= $meme->meme_id ?>' class='msginput' value='Commentez, Réagissez ici ..' />
                                   </form>
                            </div>
                           
                      </div>  
                      
                                                 
                   </div>
                    <!-- Chat Box End -->
                </td>             
                
              <?php
			    $curr_id = $meme->meme_id;
			  ?>
            </tr>
            
			
			<script>        
			<?php 
			if(!Yii::app()->user->isGuest) {
			  $user              = User::model()->findByPk(Yii::app()->user->id);
			?>
	
			  var chat_user = {
				  Member_id: '<?= $user->user_id ?>',
				  name:'<?php echo CHtml::encode("{$user->first_name} {$user->last_name}") ?>',
				  nickname:'<?= $user->username ?>',
				  image: "<?php echo Yii::app()->baseUrl.'/timthumb.php?src='.Yii::app()->user->getAvatar_url($user->user_id).'&w=34&h=33'; ?>"
			  };
		   
			  
		   <?php  }else{ ?>
			chat_user = {
			  Member_id : null,
			  name :'Guest ' + Date.now(),
			  nickname : 'Guest' + Date.now(),
			   image : '/ovnis/themes/classic/img/guest.png?'
			}
	
			<?php } ?>
			 var chatroom = {
				  title      : '<?php echo CHtml::encode($meme->title) ?>',
				  itemID     : '<?php echo $meme->meme_id ?>',
          meme_type   :'<?php echo $meme->meme_type ?>',
          slug      :'<?php echo $meme->slug ?>',
          url      :'<?php echo $meme->url ?>',
          file      :'<?php echo $meme->file ?>',
          user_fk    :'<?php echo $meme->user_fk ?>',
				  owner      :  { 
					Member_id: '<?php echo $meme->user->user_id ?>', 
					name:'<?php echo CHtml::encode("{$meme->user->first_name} {$meme->user->last_name}"); ?>', 
					nickname:'<?php echo $meme->user->username; ?>',
					image: "<?php echo Yii::app()->baseUrl.'/timthumb.php?src='.Yii::app()->user->getAvatar_url($meme->user->user_id).'&w=34&h=33'; ?>"
				}
			  };
					socket.emit("join", chatroom, chat_user);

            </script> 
            
			
			<?php						
			 } 
			 ?>
      
             <script type="text/javascript" src="<?php echo Yii::app()->theme->baseUrl; ?>/js/chatbox.js"></script>
			 <script type="text/javascript">
					       $(window).load(function(){
								 $('.user_pro_row').each(function(){
								   $(this).find(".chat_box").mCustomScrollbar("scrollTo","bottom");	
							  });
							});
			 </script>
			 <script type="text/javascript">			   
			       $('.user_pro_row').each(function(){				   
					   $(this).find("#close_popup1").click(function(event){
							 event.stopPropagation();	
							 $(this).parents('.user_pro_row').find('#meme_popup1').hide();
							 $(this).parents('.user_pro_row').find('#meme_contentbox1').hide();
		               });				   
				   });		   
			</script>
			 <?php
			 if(!empty($memes)) {
			 ?>
              <script type="text/javascript">
			
			   
			   $(".favorite_button_user").click(function(event){					   
						   $(this).parents('.user_pro_row').find('#main_container,body').css('overflow','hidden');
			               $(this).parents('.user_pro_row').find('#meme_contentbox1,#meme_popup1').show();
			               $(this).parents('.user_pro_row').find('#meme_popup1').css('height','100%');						   			   
			   });
			   
			   $(".unfavorite_button").click(function(){	
			    				  							   					      						   						  						                           $curr_id = $(this).attr('id');	
																																													                           var show_result = $(this).parent('#userfav_buttons');																																										   					   
						   $.ajax({
							url: '<?php echo Yii::app()->baseUrl; ?>/site/unfavoritelist/id/'+$curr_id,
							type: "post",
							success: function(response){								
								show_result.html(response);								
							},
							error:function(){	
								alert("failure");
							}
						  });														  
				});
				
				$(".favourite_share").click(function(event) {				                               
						  var  formData    = "fb_share="+$('#facebook_share').prop('checked')+"&meme_url="+$('#meme_url').val();						  
						  var show_result = $(this).parents('.user_pro_row').find('#userfav_buttons');	
						
						  $.ajax({
							url: '<?php echo Yii::app()->baseUrl; ?>/site/favoritelist/id/'+$(this).attr('id'),
							data : formData,
							type: "post",
							success: function(response){										
								 show_result.html(response);
								 $('#main_container,body').css('overflow','inherit');
								 $('#meme_contentbox1,#meme_popup1').hide();											
							},
							error:function(){	
								alert("failure");
							}
						  });						  					  
			  });
		  		   
			   
			   
			</script>
             <?php
			   }
			 }
			
        } else {
            throw new CHttpException(404, Yii::t('yii', 'User profile not found!'));
        }
		
		
		
    }

    public function actionFollow() {
        $user_fk = Yii::app()->request->getParam('id');
        if ($user_fk && !Yii::app()->user->checkAccess('ownId', $user_fk)) {
            $user = User::follow($user_fk);
            if ($user) {
            ?>
            <img src="<?php echo Yii::app()->theme->baseUrl; ?>/img/follow.png" id="unfollow_user"/>
            <script type="text/javascript">				
				$("#unfollow_user").click(function(event) {								 
					  $.ajax({
							url: '<?php echo Yii::app()->baseUrl; ?>/site/unfollow/id/<?php echo $user_fk; ?>',
							type: "post",
							success: function(response){	
							      $('.alert-success').show();
							     $('.alert-success span').html('You have unfollowed this user');												
								 $('#follow_button').html(response);
							},
							error:function(){
								alert("failure");
							}
					  });								  
				}); 
             </script>
            <?php   		
            }
        } else {
            //Utility::setFlash(Yii::t('yii', 'You can not follow yourself!'), 'error');
			echo 'failure';
        }
        exit;
        //Yii::app()->request->redirect(Yii::app()->request->urlReferrer);
    }
	
	
	public function actionFollowu() {
        $user_fk = Yii::app()->request->getParam('id');
        if ($user_fk && !Yii::app()->user->checkAccess('ownId', $user_fk)) {
            $user = User::follow($user_fk);
            if ($user) {
            	
            }
        } else {
           
        }
        Yii::app()->request->redirect(Yii::app()->request->urlReferrer);
    }
	
	public function actionFavorite() {
	
            $user_fk = Yii::app()->request->getParam('id');
			
			if(isset($_REQUEST['meme_url']) && $_REQUEST['fb_share']=='true'){
						  
			      require dirname(dirname(dirname(__FILE__))).'/fb_sdk/facebook.php';
	  
				   $facebook = new Facebook(array(
					 'appId'  => '1507112422763131',
					 'secret' => '20db3d01abc83b38d01d6a163a331367',
				   ));
			
				   // Get User ID
				   $user = $facebook->getUser();	    	  
				   
				   if ($user) {	   
					 try { 
					   $facebook->api('/me/feed', 'post', array('message' => '','link' => $_REQUEST['meme_url'],'description' => htmlentities("Lovely Buzz : Pour Découvrir, Créer et Partager les Ovni's du Net")));						 
					 }
					 catch(Exception $e) {
					   echo $e->getMessage();
					 }				   					 
				   }			
			}			
			
            if ($user_fk && !Yii::app()->user->checkAccess('ownId', $user_fk)) {
            $user = User::favorite($user_fk);
			?>             
            <span id="unfavorite_button" href="<?php echo Yii::app()->baseUrl; ?>/site/unfavorite/id/<?php echo $user_fk; ?>"><img src="<?php echo Yii::app()->theme->baseUrl; ?>/img/mattre_dans_fav.png"/></span>
            <script type="text/javascript">				
				 $("#unfavorite_button").click(function(event){
					   event.stopPropagation();
					   $.ajax({
						url:  $(this).attr('href'),
						type: "post",
						success: function(response){
						    $('.alert-success').show();
						    $('.alert-success span').html('Removed from favorite');										     										
							$('#userfav_buttons').html(response);								           									 
						},
						error:function(){	
							alert("failure");
						}
				});
				});
			</script>
            <?php            
        } 
    }
		
	public function actionUnFavorite() {
        $user_fk = Yii::app()->request->getParam('id');
        if ($user_fk && !Yii::app()->user->checkAccess('ownId', $user_fk)) {
            $user = User::unfavorite($user_fk);
			?>
           <span id="favorite_button" href="site/favorite/id/<?php echo $user_fk; ?>"><img src="<?php echo Yii::app()->theme->baseUrl; ?>/img/mattre_dans.png"/> </span>
           <script type="text/javascript">		    
			$("#favorite_button").click(function(event){
				 event.stopPropagation();							    		  
				 $('#main_container,body').css('overflow','hidden');
				 $('#meme_contentbox1,#meme_popup1').show();
				 $('#meme_popup1').css('height','100%');
			});									
		   </script>
            <?php
        }
    }
	
	
	
	public function actionFavoritelist() {
	
            $user_fk = Yii::app()->request->getParam('id');
			
			if(isset($_REQUEST['meme_url']) && $_REQUEST['fb_share']=='true'){
						  
			      require dirname(dirname(dirname(__FILE__))).'/fb_sdk/facebook.php';
	  
				   $facebook = new Facebook(array(
					 'appId'  => '1507112422763131',
					 'secret' => '20db3d01abc83b38d01d6a163a331367',
				   ));
			
				   // Get User ID
				   $user = $facebook->getUser();	    	  
				   
				   if ($user) {	   
					 try { 
					   $facebook->api('/me/feed', 'post', array('message' => '','link' => $_REQUEST['meme_url'],'description' => htmlentities("Lovely Buzz : Pour Découvrir, Créer et Partager les Ovni's du Net")));						 
					 }
					 catch(Exception $e) {
					   echo $e->getMessage();
					 }				   					 
				   }			
			}			
						
            if ($user_fk && !Yii::app()->user->checkAccess('ownId', $user_fk)) {
            $user = User::favorite($user_fk);
			?>  
            <span  href="<?php echo Yii::app()->baseUrl; ?>/site/unfavoritelist/id/<?php echo $user_fk; ?>" class="unfavorite_button">           
              <img src="<?php echo Yii::app()->theme->baseUrl; ?>/img/mattre_dans_fav.png"/>
            </span>
            <script type="text/javascript">				
				$(".unfavorite_button").click(function(event){	
					   event.stopPropagation();
					   var show_result = $(this).parent('#userfav_buttons');
					   $.ajax({
						url:  $(this).attr('href'),
						type: "post",
						success: function(response){										     										
							show_result.html(response);									           									 
						},
						error:function(){	
							alert("failure");
						}
				});
				});
			</script>
            <?php            
        } 
    }
	
	public function actionUnFavoritelist() {
        $user_fk = Yii::app()->request->getParam('id');
        if ($user_fk && !Yii::app()->user->checkAccess('ownId', $user_fk)) {
            $user = User::unfavorite($user_fk);
			?>
           <span class="favorite_button_user" href="site/favoritelist/id/<?php echo $user_fk; ?>"><img src="<?php echo Yii::app()->theme->baseUrl; ?>/img/mattre_dans.png"/> </span>
           <script type="text/javascript">		    
			 $('.user_pro_row').each(function(){
				   
				       $(this).find(".favorite_button_user").click(function(event){		
						   $(this).parents('.user_pro_row').find('#main_container,body').css('overflow','hidden');
			               $(this).parents('.user_pro_row').find('#meme_contentbox1,#meme_popup1').show();
			               $(this).parents('.user_pro_row').find('#meme_popup1').css('height','100%');						   			   
					   });	
					   					   
			  });						
		   </script>
            <?php
        } 
    }
	
	
	public function actionUnFavoritememe() {
        $user_fk = Yii::app()->request->getParam('id');
        if ($user_fk && !Yii::app()->user->checkAccess('ownId', $user_fk)) {
            $user = User::unfavorite($user_fk);			
            Utility::setFlash(Yii::t('yii', 'Removed from the favorite sucessfully'), 'success'); 
			Yii::app()->request->redirect(Yii::app()->request->urlReferrer);
        }
        
    }

    public function actionUnfollow() {
        $user_fk = Yii::app()->request->getParam('id');
        if ($user_fk && !Yii::app()->user->checkAccess('ownId', $user_fk)) {
            $user = User::unfollow($user_fk);
            if ($user) {
            ?>
             <img src="<?php echo Yii::app()->theme->baseUrl; ?>/img/unfollow.png" id="follow_user"/>             
            <?php
            }
        } else {
            //Utility::setFlash(Yii::t('yii', 'You can not unfollow yourself!'), 'error');
			echo 'failure';
        }
        //Yii::app()->request->redirect(Yii::app()->request->urlReferrer);
    }
	
	public function actionUnfollowu() {
        $user_fk = Yii::app()->request->getParam('id');
        if ($user_fk && !Yii::app()->user->checkAccess('ownId', $user_fk)) {
            $user = User::unfollow($user_fk);
            if ($user) {
            
            }
        } else {
            
        }
        Yii::app()->request->redirect(Yii::app()->request->urlReferrer);
    }

    public function actionFlag() {
        $id = Yii::app()->request->getParam('id');
        if (!Meme::hasFlagged($id)) {
            $memeFlag = new MemeFlag();
            $memeFlag->user_fk     = Yii::app()->user->id;
            $memeFlag->meme_fk     = $id;
            $memeFlag->created_at = new CDbExpression('NOW()');
            $memeFlag->save();
			//Utility::setFlash(Yii::t('yii', 'You have flagged sucessfully'), 'success');
			//Yii::app()->request->redirect(Yii::app()->request->urlReferrer);
        }
    }

    public function actionCms() {
        $slug = Yii::app()->request->getParam('slug');
        if ($page = Page::model()->active()->findByAttributes(array('slug' => $slug))) {
            $this->render('cms', array('page' => $page));
        } else {
            throw new CHttpException(404, Yii::t('yii', 'Page not found!'));
        }
    }

    public function actionMymemes() {
	    	    
		
       /* $memes = Meme::model()->current_user()->findAll(array(
            'order' => 'meme_id desc'
        ));			
		
        $this->render('my_memes', array(
            'memes' => $memes,
        ));*/
		
		
		$limit           =   20;
        $page            =   Yii::app()->request->getQuery('page');
        $offset          =   (max($page, 1) - 1) * $limit;
		 
	
		 
        $q               =   new CDbCriteria(array(
							 'condition' => 't.user_fk = :user_fk',
							 'params' => array(':user_fk' => Yii::app()->user->id),
							 'order' => 't.meme_id DESC',
							 'limit' => $limit,
                             'offset' => $offset,							 
							 ));
		 
		$memes  =  Meme::model()->findAll($q);
		if($page==''){
			$this->render('my_memes', array(
				'memes' => $memes,
			));
		} else {
		 foreach($memes as $meme){
		 $meme_url      = Yii::app()->baseUrl.'/'.$meme->meme_type.'/'.$meme->slug; 
		?>
         
         <tr>
               <td class="mymeme_type">               
					<?php         
				      echo '<img src="'.Yii::app()->theme->baseUrl.'/img/'.$meme->meme_type.'_b.png" />';         				  	  
				    ?>  
                </td>
                <td class="mymeme text-middle <?php if($meme->meme_type=='videos') echo 'video_ovniscont profile_video'; ?>" width="300" style="text-align:left;">                                  
                      <?php 
					  if($meme->meme_type!='videos')  {                      
                        
						if($meme->is_active!=0) 						
					     echo '<a href="'.$meme_url.'" target="_blank"><img src="'.$meme->url.'" id="ovnis_cont"/></a>';
						else
						 echo '<img src="'.$meme->url.'" id="ovnis_cont"/>';
						
                       } 
					   else { 
                        
                        $meme_video  =  explode(',',$meme->file);            
                        if($meme_video[1]=='youtube'){
                          echo '<iframe width="238" height="230" src="//www.youtube.com/embed/'.$meme_video[0].'?rel=0" frameborder="0" id="ovnis_cont" allowfullscreen></iframe>';
                        } else if($meme_video[1]=='viemo'){	                          
                          echo '<iframe src="//player.vimeo.com/video/'.$meme_video[0].'" width="238" height="230" frameborder="0" id="ovnis_cont" allowfullscreen></iframe>';
                        } else {
                           echo '<iframe frameborder="0" width="238" height="230" id="ovnis_cont" src="http://www.dailymotion.com/embed/video/'.$meme_video[0].'"></iframe>'; 
                        }
						
                       }
                      ?>    
                      <br />
                      <div style=" float:left; width:239px; text-align:center">
						  <?php
                          if($meme->is_active!=0) 
                           echo '<a href="'.$meme_url.'" target="_blank"><b>'.CHtml::encode($meme->title).'</b></a>';  
                          else
                           echo '<b>'.CHtml::encode($meme->title).'</b>';
                          ?>
                      </div>
                 
                </td>
                <td class="text-center text-middle" width="375">					           
                        <div style="background:#FFFFFF; float:left; height:342px;float:right;width:364px;">
                            &nbsp;
                        </div>			
                </td>
             <?php /*?>   <td class="text-center text-middle" width="200"><?php echo CHtml::encode($meme->likes_count) ?></td>
                <td class="text-center text-middle" width="100"><?php echo $meme->is_published ?></td><?php */?>
                <td class="text-center text-middle" width="300">
				  <?php 
				    if($meme->is_active==0) {
					  echo 'En attente de validation'; 
					}
					else {
				      echo 'Validation OK <br>';
					  if($meme->is_featured) echo 'Dans "Les Mis en Avant"';
					}
				  ?>
                </td>
                <td class="text-center text-middle" width="100">                                    
                    <?php
                    if($meme->meme_type!='videos')  { 
					?><a class="ttip" title="<?php echo Yii::t('yii', 'download') ?>" style="padding-top:7px;" href="<?php echo Yii::app()->createUrl('site/download', array('id' => $meme->meme_id)) ?>"><i class="icon-download"><img src="<?php echo Yii::app()->theme->baseUrl; ?>/img/download.png" /></i></a>
                    <?php } ?>
                    
                    &nbsp;&nbsp;&nbsp;
                    
                    <a class="ttip" onclick="return confirm('Are you sure?')" title="<?php echo Yii::t('yii', 'delete') ?>" href="<?php echo Yii::app()->createUrl('site/delete', array('id' => $meme->meme_id)) ?>"><i class="icon-trash"><img src="<?php echo Yii::app()->theme->baseUrl; ?>/img/delete_button.png" /></i></a>
                </td>
            </tr>                
        
		<?php
		}
	  }
    }
	
	public function actionmy_favoris() {
	    
	    $limit           =   20;
        $page            =   Yii::app()->request->getQuery('page');
        $offset          =   (max($page, 1) - 1) * $limit;
		 
	
		 
        $q               =   new CDbCriteria(array(
							 'condition' => 'user_favorite.user_fk = :user_fk',
							 'join' => 'INNER JOIN user_favorite ON t.meme_id = user_favorite.favorite_id',
							 'params' => array(':user_fk' => Yii::app()->user->id),
							 'order' => 'user_favorite.created_at DESC',
							 'limit' => $limit,
                             'offset' => $offset,							 
							 ));
		 
		$check_favorite  =  Meme::model()->findAll($q);
		
	   /* 
		echo "<pre>";
		print_r($check_favorite);
		echo "</pre>";*/
		
									
		/*$fav_ids   = array();
		
		foreach($check_favorite as $favorite){
		  $fav_ids[] = $favorite->favorite_id;
		}		 
		 */
        //$memes = Meme::model()->findAllByAttributes(array('meme_id' => $fav_ids));	
		if($page==''){
           $this->render('my_favoris', array(
            'memes' => $check_favorite,
          ));
		}else {
		 foreach($check_favorite as $meme){		 
		 $meme_url   = 'http://'.$_SERVER['SERVER_NAME'].Yii::app()->baseUrl.'/meme/'.$meme->meme_id;
		?>
        <tr>
                 <td class="text-middle mymeme_type" style="position:absolute;">                
                  <?php         
				   echo '<img src="'.Yii::app()->theme->baseUrl.'/img/'.$meme->meme_type.'_b.png" />';         				  	  
				  ?>                            
                </td>
                <td class="text-left mymeme text-middle <?php if($meme->meme_type=='videos') echo 'video_ovniscont'; ?>" width="260" style="text-align:left;">
                  
                      <?php if($meme->meme_type!='videos')  { ?>                      
                        <a href="<?php echo $meme_url; ?>" target="_blank" id="ovnis_cont"><img src="<?php echo $meme->url ?>" /></a>
                      <?php } else { 
                        
                        $meme_video  =  explode(',',$meme->file);
            
                        if($meme_video[1]=='youtube'){
                          echo '<iframe width="238" height="230" src="//www.youtube.com/embed/'.$meme_video[0].'?rel=0" frameborder="0" allowfullscreen id="ovnis_cont"></iframe>';
                        } else if($meme_video[1]=='viemo'){	                          
                          echo '<iframe src="//player.vimeo.com/video/'.$meme_video[0].'" width="238" height="230" frameborder="0" allowfullscreen id="ovnis_cont"></iframe>';
                        } else {
                           echo '<iframe frameborder="0" width="238" height="230" src="http://www.dailymotion.com/embed/video/'.$meme_video[0].'" id="ovnis_cont"></iframe>'; 
                        }
                       }
                      ?>    
                      <br />
                      <div style=" float:left; width:239px; text-align:center"><b><a href="<?php echo $meme_url; ?>" target="_blank"><?php echo CHtml::encode($meme->title) ?></a></b></div>
                      <span style="float:left; margin-top:5px;">
                      <a href="<?php echo Yii::app()->createUrl('site/profile', array('profile' => $meme->user->username)) ?>" style="float:left; padding-right:5px;"><img src="<?php echo Yii::app()->user->getAvatar_url($meme->user_fk) ?>" alt="<?php echo CHtml::encode("{$meme->user->first_name} {$meme->user->last_name}") ?>" title="<?php echo CHtml::encode("{$meme->user->first_name} {$meme->user->last_name}") ?>" class="meta_image ttip" /></a> <span style="float:left; width:190px;text-align:left;"> par <br /> <b><a href="<?php echo Yii::app()->createUrl('site/profile', array('profile' => $meme->user->username)) ?>"><?php echo $meme->user->first_name.'&nbsp;'.$meme->user->last_name; ?></a></b></span>	
                     </span>         
                </td>
                                      
                <td class="text-center text-middle" width="375">					           
                        <div style="background:#FFFFFF; float:left; height:342px;float:right;width:364px;">
                            &nbsp;
                        </div>			
                </td>
                <td class="text-center text-middle" width="280">
				 <?php
				    $tot_count    = Meme::model()->findByAttributes(array('meme_id' => $meme->meme_id ))->view_count;
                    echo  $tot_count.'&nbsp;';					
					if($tot_count) echo "vues";
                    else echo "vue";
				 ?>
                </td>
                <td class="text-center text-middle" width="120">                  
                    <?php
                    if($meme->meme_type!='videos')  { 
					?><a class="ttip" title="<?php echo Yii::t('yii', 'download') ?>" style="padding-top:7px;" href="<?php echo Yii::app()->createUrl('site/download', array('id' => $meme->meme_id)) ?>"><i class="icon-download"><img src="<?php echo Yii::app()->theme->baseUrl; ?>/img/download.png" /></i></a>
                    <?php } ?>
                    
                    &nbsp;&nbsp;&nbsp;
                    <a class="ttip" onclick="return confirm('Are you sure?')" title="<?php echo Yii::t('yii', 'delete from favorite') ?>" href="<?php echo Yii::app()->createUrl('site/unfavoritememe', array('id' => $meme->meme_id)) ?>"><i class="icon-trash"><img src="<?php echo Yii::app()->theme->baseUrl; ?>/img/delete_button.png" /></i></a>
                </td>
            </tr>
        
        <?php		
		  }
		  
		}
    }
	
	
	public function actionFavorisuser() {
	     
		$limit           =   20;
        $page            =   Yii::app()->request->getQuery('page');
        $offset          =   (max($page, 1) - 1) * $limit;		 		 
		  
        $q               =   new CDbCriteria(array(
							 'condition' => 't.is_active = 1 AND user_follow.user_fk = :user_fk',
							 'join' => 'INNER JOIN user_follow ON t.user_fk = user_follow.following_id',
							 'params' => array(':user_fk' => Yii::app()->user->id),
							 'order' => 't.meme_id DESC',
							 'limit' => $limit,
                             'offset' => $offset,							 
							 ));
		 
		$memes         =  Meme::model()->findAll($q);
		
		if($page==''){ 		
          $this->render('my_favoris_user', array(
            'memes' => $memes,
          ));
		}else{
		   foreach($memes as $meme){
		   $meme_url   = 'http://'.$_SERVER['SERVER_NAME'].Yii::app()->baseUrl.'/'.$meme->meme_type.'/'.$meme->slug;
		?>
         <tr class="user_pro_row">
                <td>
                 <div id="meme_contentbox1" style="display:none;">
                  <div id="meme_popup1" style="">                                           
                      <span  id="close_popup1" style=" float:right; padding:10px 20px 0px 0px;">
                        <img src="<?php echo Yii::app()->theme->baseUrl; ?>/img/close_button.png" />
                      </span>                                   
                 
                       <div style="margin:auto; width:487px;">
                         <div style="width:487px;  text-align:center;min-height:300px; margin:130px auto 130px auto; background:#FFFFFF; float:left;padding:0px 0px 15px 0px;">
                             
                             <h2><?php echo CHtml::encode($meme->title) ?></h2>    
                                          
                              <?php if($meme->meme_type!='videos')  { ?>                      
                              <img class="meme-img" src="<?php echo $meme->url ?>" alt="<?php echo CHtml::encode($meme->title) ?>" width="430"/>
                              <?php } else {                             
                                $meme_video  =  explode(',',$meme->file);               
                                if($meme_video[1]=='youtube'){
                                  echo '<iframe width="430" height="342" src="//www.youtube.com/embed/'.$meme_video[0].'?rel=0" frameborder="0" allowfullscreen></iframe>';
                                } else if($meme_video[1]=='viemo'){	                          
                                  echo '<iframe src="//player.vimeo.com/video/'.$meme_video[0].'" width="430" height="342" frameborder="0" allowfullscreen></iframe>';
                                } else {
                                   echo '<iframe frameborder="0" width="430" height="342" src="http://www.dailymotion.com/embed/video/'.$meme_video[0].'"></iframe>'; 
                                }
                               }
                               ?>    
                               <div style=" text-align:left; padding-left:27px; line-height:44px;">
                                  <?php
                                  if(!Yii::app()->user->isGuest){ 
                                    $user_type   =  User::model()->findByAttributes(array('user_id' => Yii::app()->user->id));
                                    if($user_type->token==''){
                                      echo '<input type="checkbox" name="facebook_share" id="facebook_share" value="1"/>&nbsp;<b>Partager sur Facebook</b>';
                                    }
                                  }
                                  ?>                              
                                  <input type="hidden" name="meme_url" id="meme_url" value="<?php //echo $meme_url; ?>"/>
                                  <img src="<?php echo Yii::app()->theme->baseUrl; ?>/img/favourite_share.png" style="float:right; margin-right:27px; margin-top:5px;" class="favourite_share" id="<?php echo $meme->meme_id; ?>"/>
                               </div>                                               
                          </div>
                       </div>                  
                    </div>
                 </div>
               
                </td>
                
                <td class="mymeme_type">
                  <?php         
				   echo '<img src="'.Yii::app()->theme->baseUrl.'/img/'.$meme->meme_type.'_b.png" />';         				  	  
				  ?>                                  
                </td>
                <td class="mymeme text-middle <?php if($meme->meme_type=='videos') echo 'video_ovniscont'; ?>" width="260">
                    
					
					<div id="favorite_meme_button">
						   <?php
                            $check_favorite   =  UserFavorite::model()->findByAttributes(array('user_fk' => Yii::app()->user->id, 'favorite_id' => $meme->meme_id));
                            if($check_favorite){                                  
                              if(!Yii::app()->user->isGuest)
                                echo '<div id="userfav_buttons"><span id="'.$meme->meme_id.'" class="unfavorite_button"><img src="'.Yii::app()->theme->baseUrl.'/img/mattre_dans_fav.png"/></span></div>';
                              else
                                echo '<a href="'.Yii::app()->createUrl('login').'"><img src="'.Yii::app()->theme->baseUrl.'/img/mattre_dans_fav.png"/></a>';
                           
                            } else {                              
                               if(!Yii::app()->user->isGuest)
                                echo '<div id="userfav_buttons"><span class="favorite_button_user"><img src="'.Yii::app()->theme->baseUrl.'/img/mattre_dans.png"/></span></div>';
                               else
                                echo '<a  href="'.Yii::app()->createUrl('login').'"><img src="'.Yii::app()->theme->baseUrl.'/img/mattre_dans.png"/></a>';
                            }                                     
                            ?>
                      </div>
					<?php if($meme->meme_type!='videos')  { ?>                      
                        <a href="<?php echo $meme_url; ?>" target="_blank" id="ovnis_cont"><img src="<?php echo $meme->url ?>" /></a>
                      <?php } else { 
                        
                        $meme_video  =  explode(',',$meme->file);
            
                        if($meme_video[1]=='youtube'){
                          echo '<iframe width="238" height="230" src="//www.youtube.com/embed/'.$meme_video[0].'?rel=0" frameborder="0" id="ovnis_cont" allowfullscreen></iframe>';
                        } else if($meme_video[1]=='viemo'){	                          
                          echo '<iframe src="//player.vimeo.com/video/'.$meme_video[0].'" width="238" height="230" frameborder="0" id="ovnis_cont" allowfullscreen></iframe>';
                        } else {
                           echo '<iframe frameborder="0" width="238" height="230" src="http://www.dailymotion.com/embed/video/'.$meme_video[0].'" id="ovnis_cont"></iframe>'; 
                        }
                       }
                      ?>    
                      <br />
                      <b><a href="<?php echo $meme_url; ?>" target="_blank" style=" float:left; width:239px;"><?php echo CHtml::encode($meme->title) ?></a></b><br /> 
                      <br />
                      <span style="float:left; margin-top:5px;">
                      <a href="<?php echo Yii::app()->createUrl('site/profile', array('profile' => $meme->user->username)) ?>" style="float:left; padding-right:5px;"><img src="<?php echo Yii::app()->user->getAvatar_url($meme->user_fk) ?>" alt="<?php echo CHtml::encode("{$meme->user->first_name} {$meme->user->last_name}") ?>" title="<?php echo CHtml::encode("{$meme->user->first_name} {$meme->user->last_name}") ?>" class="meta_image ttip" /></a> <span style="float:left; text-align:left;width:190px;"> par <br /> <b><a href="<?php echo Yii::app()->createUrl('site/profile', array('profile' => $meme->user->username)) ?>"><?php echo $meme->user->first_name.'&nbsp;'.$meme->user->last_name; ?></a></b></span>	
                     </span> 
                      
                                                         
                
                </td>
                <td class="text-center text-middle" width="375">					           
                        <div style="background:#FFFFFF; float:left; height:342px; float:right;width:364px;">
                            &nbsp;
                        </div>			
                </td>
                <td class="text-center text-middle" width="200">
					<?php                    
					$tot_count    = Meme::model()->findByAttributes(array('meme_id' => $meme->meme_id ))->view_count;
                    echo  $tot_count.'&nbsp;';					
					if($tot_count) echo "vues";
                    else echo "vue";                      
                    ?>
                </td>
  
                <td class="text-center text-middle" width="100">                   
                    
                    <?php
                    if($meme->meme_type!='videos')  { 
					?><a class="ttip" title="<?php echo Yii::t('yii', 'download') ?>" style="padding-top:7px;" href="<?php echo Yii::app()->createUrl('site/download', array('id' => $meme->meme_id)) ?>"><i class="icon-download"><img src="<?php echo Yii::app()->theme->baseUrl; ?>/img/download.png" /></i></a>
                    <?php } ?>
                    
                    &nbsp;&nbsp;&nbsp;
                    
                </td>
            </tr>
        
         <script type="text/javascript">
			   
			       $('.user_pro_row').each(function(){
				   
					   $(this).find("#close_popup1").click(function(event){
							 event.stopPropagation();	
							 $('#main_container,body').css('overflow','inherit');
							 $('#main_container').css('position','inherit');
							 $(this).parents('.user_pro_row').find('#meme_popup1').hide();
							 $(this).parents('.user_pro_row').find('#meme_contentbox1').hide();
		               });
				   
				   });
		   
			</script>
        <?php		
		  }
		   if(!empty($memes)) {
			 ?>
              <script type="text/javascript">			   
						  
		   
			   $(".favorite_button_user").click(function(event){					   
						   $('#main_container,body').css('overflow','hidden');
			               $(this).parents('.user_pro_row').find('#meme_contentbox1,#meme_popup1').show();
			               $(this).parents('.user_pro_row').find('#meme_popup1').css('height','100%');						   			   
			   });
			   
			   $(".unfavorite_button").click(function(){	
			    				  							   					      						   						  						                           $curr_id = $(this).attr('id');	
																																													                           var show_result = $(this).parent('#userfav_buttons');																																										   					   
						   $.ajax({
							url: '<?php echo Yii::app()->baseUrl; ?>/site/unfavoritelist/id/'+$curr_id,
							type: "post",
							success: function(response){								
								show_result.html(response);								
							},
							error:function(){	
								alert("failure");
							}
						  });														  
				});
				
				$(".favourite_share").click(function(event) {				                               
						  var  formData    = "fb_share="+$('#facebook_share').prop('checked')+"&meme_url="+$('#meme_url').val();						  
						  var show_result = $(this).parents('.user_pro_row').find('#userfav_buttons');	
						
						  $.ajax({
							url: '<?php echo Yii::app()->baseUrl; ?>/site/favoritelist/id/'+$(this).attr('id'),
							data : formData,
							type: "post",
							success: function(response){										
								 show_result.html(response);
								 $('#success_msg').html('Added to the favorite');
								 $('#main_container,body').css('overflow','inherit');
								 $('#meme_contentbox1,#meme_popup1').hide();											
							},
							error:function(){	
								alert("failure");
							}
						  });						  					  
			  });
		  		   
			   
			   
			</script>
             <?php
			   }
		}
		
    }
		
	
	public function get_vimeo_thumb($vimeo){
	
	  $video_url  = file_get_contents('http://vimeo.com/api/v2/video/9696328.xml');
	  $sxe        = new SimpleXMLElement($video_url);
      $output    = current($sxe->video->thumbnail_medium);      
	  return $output;      
	  
	}
	
	
	
	public function get_partages($url)
	{
	   $fb_count       = $this->getFacebooks($url);
	   //$gp_count       = $this->getPlus($url);
	   $gp_count       = "";
	   $tw_count       = $this->getTweets($url);
	   $partages       = $fb_count + $tw_count + $gp_count;
	   return $partages;
	}
	
	public function getTweets($url){
		$json = file_get_contents( "http://urls.api.twitter.com/1/urls/count.json?url=".$url );
		$ajsn = json_decode($json, true);
		$cont = $ajsn['count'];
		return $cont;
    }

	public function getPins($url){
		$json = file_get_contents( "http://api.pinterest.com/v1/urls/count.json?callback=receiveCount&url=".$url );
		$json = substr( $json, 13, -1);
		$ajsn = json_decode($json, true);
		$cont = $ajsn['count'];
		return $cont;
	}


	public function getFacebooks($url,$action='') { 
		$xml = file_get_contents("http://api.facebook.com/restserver.php?method=links.getStats&urls=".urlencode($url));
		$xml = simplexml_load_string($xml);
		$shares = current($xml->link_stat->share_count);
		$likes  = current($xml->link_stat->like_count);
		$comments = current($xml->link_stat->comment_count); 
	  
	
		if($action=='share'){     
		 return $shares;
		}
		if($action=='like'){
		 return $likes;
		}
		if($action=='comments') {
		 return $comments;
		}
		else {
		  return $likes + $shares + $comments;
		}
	}

	public function getPlus($url) {
	
		$html =  file_get_contents( "https://plusone.google.com/_/+1/fastbutton?url=".urlencode($url));
		$doc = new DOMDocument();
		libxml_use_internal_errors(true);
		$doc->loadHTML($html);
		libxml_clear_errors();
		$counter=$doc->getElementById('aggregateCount');
	
		return $counter->nodeValue;
	
	}
	
}