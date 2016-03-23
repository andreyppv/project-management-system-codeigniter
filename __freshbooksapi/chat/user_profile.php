<link rel="stylesheet" type="text/css" href="<?php echo Yii::app()->theme->baseUrl; ?>/js/content-scroller/jquery.mCustomScrollbar.css" />
<script type="text/javascript" src="<?php echo Yii::app()->theme->baseUrl; ?>/js/content-scroller/jquery.mCustomScrollbar.concat.min.js"></script>
<script src="http://ressio.github.io/lazy-load-xt/dist/jquery.lazyloadxt.extra.js"></script>
<?php 
   $type       =   Yii::app()->request->getQuery('type');  
      
   if($type == 'favorite')
   $add_url = '/favorite';
   else
   $add_url = '';
 ?>
 <div class="row">
    <div class="span8">
        <div class="corgi_feed_well content_bg_box">
            <div class="feed_stacked">
                <div class="feed_item meme">
                    <div class="feed_body">                             
                            <table class="table table-striped profile_header">
      
                                <?php if($user->user_description) { ?>
                                <tr>
                                    <th  width="150"><?php echo Yii::t("yii", "Un peu plus sur "); ?><br><?php echo CHtml::encode($user->first_name . ' ' . $user->last_name) ?></th>
                                    <td><?php echo $user->user_description; ?></td>
                                </tr>
                                <?php } ?>
                            </table>
                    </div>
                </div>
            </div>
        </div>
        <a href="<?php echo Yii::app()->createUrl('site/profile', array('profile' => $user->username)) ?>" id="profile_link">            
           <b>
            <?php
		    if(Yii::app()->user->id!= $user->user_id)
			echo 'Ses';
			else
			echo 'Mes';
		    ?>
            Ovni's du Net</b>
        </a>
        <a href="<?php echo Yii::app()->createUrl('site/profile', array('profile' => $user->username)) ?>/favorite" id="profile_link">
          <b><?php
		    if(Yii::app()->user->id!= $user->user_id)
			echo 'Ses';
			else
			echo 'Mes';
		    ?> coups de cœur</b>
        </a>
        <div style="clear:both; height:1px;">&nbsp;</div>
        <?php if ($memes): 		    
		?>
        
         <table class="table table-striped mymeme_cont">
              <?php 
			   foreach ($memes as $i =>$meme): 
			   $meme_url   = 'http://'.$_SERVER['SERVER_NAME'].Yii::app()->baseUrl.'/'.$meme->meme_type.'/'.$meme->slug;
			  ?>
              
             
               <tr style="width:650px;" class="user_pro_row test<?php echo $i; ?>">
              
                <td>
                 <!--<div  id="message_count">+1</div>-->
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
                                  echo '<iframe width="430" height="342" data-src="//www.youtube.com/embed/'.$meme_video[0].'?rel=0" frameborder="0" allowfullscreen></iframe>';
                                } else if($meme_video[1]=='viemo'){	                          
                                  echo '<iframe data-src="//player.vimeo.com/video/'.$meme_video[0].'" width="430" height="342" frameborder="0" allowfullscreen></iframe>';
                                } else {
                                   echo '<iframe frameborder="0" width="430" height="342" data-src="http://www.dailymotion.com/embed/video/'.$meme_video[0].'"></iframe>'; 
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
							 if(Yii::app()->user->id!=$user->user_id) {
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
					?><a class="ttip ovnis_download" title="<?php echo Yii::t('yii', 'T&eacute;l&eacute;charger cet Ovnis du Net') ?>" href="<?php echo Yii::app()->createUrl('site/download', array('id' => $meme->meme_id)) ?>"><i class="icon-download"><img src="<?php echo Yii::app()->theme->baseUrl; ?>/img/download.png" /></i></a>
                    <?php } ?>
                  
                    <?php if($meme->meme_type!='videos')  { ?>                      
                        <a href="<?php echo $meme_url; ?>" target="_blank" id="ovnis_cont"><img data-src="<?php echo $meme->url ?>" /></a>
                      <?php } else { 
                        
                        $meme_video  =  explode(',',$meme->file);
            
                        if($meme_video[1]=='youtube'){
                          echo '<iframe width="238" height="230" data-src="//www.youtube.com/embed/'.$meme_video[0].'?rel=0" frameborder="0" allowfullscreen id="ovnis_cont"></iframe>';
                        } else if($meme_video[1]=='viemo'){	                          
                          echo '<iframe data-src="//player.vimeo.com/video/'.$meme_video[0].'" width="238" height="230" frameborder="0" allowfullscreen id="ovnis_cont"></iframe>';
                        } else {
                           echo '<iframe frameborder="0" width="238" height="230" data-src="http://www.dailymotion.com/embed/video/'.$meme_video[0].'" id="ovnis_cont"></iframe>'; 
                        }
                       }
                      ?>    
                   
                   
                    <br style="clear:both;"/>
                    <a href="<?php echo $meme_url; ?>" target="_blank"><b><?php echo CHtml::encode($meme->title) ?></b></a>
                    
                   <div class="userProf_feed">
                   
				   <?php 
				   
				   $type      =   Yii::app()->request->getQuery('type');
				   				   
				   if(Yii::app()->user->id != $meme->user->user_id && $type == 'favorite') { ?>
                        <span style="float:left; margin-top:5px;">
                      <a href="<?php echo Yii::app()->createUrl('site/profile', array('profile' => $meme->user->username)) ?>" style="float:left; padding-right:5px;"><img src="<?php echo Yii::app()->user->getAvatar_url($meme->user_fk) ?>" alt="<?php echo CHtml::encode("{$meme->user->first_name} {$meme->user->last_name}") ?>" title="<?php echo CHtml::encode("{$meme->user->first_name} {$meme->user->last_name}") ?>" class="meta_image ttip" /></a> <span style="float:left; text-align:left; width:165px;"> par <br /> <b><a href="<?php echo Yii::app()->createUrl('site/profile', array('profile' => $meme->user->username)) ?>"><?php echo $meme->user->first_name.'&nbsp;'.$meme->user->last_name; ?></a></b></span>
                     </span> 
                    <?php } ?>   
                    
                   </div> 
                        
                </td>
                <td class="text-center profile_page chat_mod text-middle" width="480">					           
                    
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
                    
                    
               <?php
			    $curr_id = $meme->meme_id;
			  ?>
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
                                      
             
            </tr>
            
            
          
             
              
            <?php endforeach; ?>
              <script type="text/javascript">
         
             $('.user_pro_row').each(function(){
           
             $(this).find("#close_popup1").click(function(event){
               event.stopPropagation(); 
               $(this).parents('.user_pro_row').find('#meme_popup1').hide();
               $(this).parents('.user_pro_row').find('#meme_contentbox1').hide();
                   });
              });
       
              jQuery(document).ready(function($){
                 $(".commentForm").submit(function(event){
                      event.preventDefault();
                  }); 
                
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
            </table>
            <div id="loadmore">&nbsp;</div>   
             <?php
			      $username  =   Yii::app()->request->getParam('profile');
				  if ($user = User::model()->findByAttributes(array('username' => $username))) {				  
				  
				    $q   =  new CDbCriteria(array(
						 'condition' => 't.is_active = 1 AND t.is_published = 1 AND t.user_fk = :user_id',
						 'params' => array(':user_id' => $user->user_id),
						 'order'  => 't.meme_id DESC',
				  ));
				  				 
				  if(isset($type) && $type=='favorite') {
					
					 $q          =   new CDbCriteria(array(
									 'condition' => 'user_favorite.user_fk = :user_fk',
									 'join' => 'INNER JOIN user_favorite ON t.meme_id = user_favorite.favorite_id',
									 'params' => array(':user_fk' => $user->user_id),
									 'order' => 'user_favorite.created_at DESC',						 
									 ));
			      }
		 
		          $check_meme = Meme::model()->findAll($q);

				  }
				  
		    ?>
            <?php if(count($check_meme)>20) { ?>
              <div id="loadmore_fav">Voir plus...</div>
           <?php } ?>
           
		   <script type="text/javascript">
				var page_count        = 2;
				isDataAvailable       = true;
				var flag = true;
				
				$('#loadmore_fav').click(function(){					  			  
															
								$('#loading_image').show();
																		
								if(flag == true){
									
									$.ajax({
										url: '<?php echo Yii::app()->createUrl('site/profile', array('profile' => $user->username)) ?>/'+page_count+'<?php echo $add_url; ?>',
										type: "post",
										success: function(response){
                                            
											  if(response!=''){
												 page_count++; 
												 $('#loading_image').hide();
												 $('.mymeme_cont tbody').append(response);						 									 
											  } else {
												 isDataAvailable       = false;
												 flag = false; 
												 $('#loadmore_fav').hide();
												 $('#loading_image').hide();
											  } 
											  
											  var tot_elem  = $('.mymeme_cont').children().children('tr').length;								 

											  if(tot_elem==<?php echo count($check_meme); ?>){
												 $('#loadmore_fav').hide();
												 $('#loading_image').hide();
											  }
								   
											   
										},
											 
										error:function(){
											alert("failure");
										}
									});									
							   }
				});
		</script>
        
        
        <?php else: ?>
            <h4><?php echo Yii::t("yii", "Pas d'Ovni's pour le moment") ?></h4>
        <?php endif; ?>
    </div>
    <div class="span4" id="fixed_layer">
       
         <div class="corgi_feed_well">                               
                <div class="content_bg_box profile_meme">
                                      
                        <div class="clearfix" id="profile-header">
                            <div class="pull-left" style="padding-right:10px;">
                                <img src="<?php echo Yii::app()->user->getAvatar_url($user->user_id) ?>" alt="<?php echo CHtml::encode("{$user->first_name} {$user->last_name}") ?>" class="meta_image" />
                            </div>
                            
                            <h1><?php echo CHtml::encode($user->first_name . ' ' . $user->last_name) ?></h1>
                            
                            <div class="pull-right" style="margin-top:-5px;">
                            <?php								
                             $check_follow     = UserFollow::model()->findByAttributes(array('user_fk' => Yii::app()->user->id, 'following_id' => $user->user_id));												
                            ?>
                            <?php
                            
                            if(!Yii::app()->user->isGuest){
                            $set_followlink   =  Yii::app()->baseUrl.'/site/followu/'.$user->user_id;
                            $set_unfollowlink =  Yii::app()->baseUrl.'/site/unfollowu/'.$user->user_id;								
                            }
                            else {
                             $set_followlink   =  Yii::app()->createUrl('login');
                             $set_unfollowlink =  Yii::app()->createUrl('login');
                            }
                            
                            
                            
                            if(Yii::app()->user->id!= $user->user_id) {
                                if(!$check_follow || Yii::app()->user->isGuest){
                                ?>
                                <a href="<?php echo $set_followlink; ?>"><img src="<?php echo Yii::app()->theme->baseUrl; ?>/img/unfollow.png" style="position:absolute; right:-30px; float:right; bottom:4px;"/></a>
                                <?php
                                } else {
                                ?>
                                <a href="<?php echo $set_unfollowlink; ?>"><img src="<?php echo Yii::app()->theme->baseUrl; ?>/img/follow.png" style="position:absolute;right:-30px;float:right;bottom:4px;"/></a>
                                <?php
                                }														
                                
                                
                             }
                            ?>
                            </div>
                        </div>
                   
                </div>
            </div>
            
        <?php Yii::app()->plugin->beginBlock('sidebar') ?>
            <div class="corgi_feed_well">
                
                    <div class="sidebar_title">
                        <h5><?php echo Yii::t('yii', 'Suivi par :') ?></h5>
                    </div>
             
                
                <div class="content_bg_box profile_meme prest_pad">
                    <?php if($followers = $user->followers(array('order' => 'followers.created_at DESC', 'with' => 'follower', 'scopes' => 'follower_visible'))): ?>
                       <div id="image-preset"> 
                            <?php foreach($followers as $follower): ?>
                                    <a class="ttip follow-user-avatar" title="<?php echo CHtml::encode($follower->follower->first_name . ' ' . $follower->follower->last_name) ?>"  href="<?php echo Yii::app()->createUrl('site/profile', array('profile' => $follower->follower->username)) ?>"><img src="<?php echo Yii::app()->baseUrl.'/timthumb.php?src='.Yii::app()->user->getAvatar_url($follower->follower->user_id).'&w=51&h=52'; ?>" alt="<?php echo CHtml::encode($follower->follower->first_name . ' ' . $follower->follower->last_name) ?>" /></a> 
                            <?php endforeach ?>                                
                             
                        </div>
                    <?php else: ?>
                        <small><em><?php echo Yii::t('yii', 'No followers') ?></em></small>
                    <?php endif ?>
                </div>
            </div>
        <?php Yii::app()->plugin->endBlock() ?>
        
        <?php Yii::app()->plugin->beginBlock('sidebar') ?>
            <div class="corgi_feed_well">
               
                    <div class="sidebar_title">
                        <h5><?php echo CHtml::encode($user->first_name . ' ' . $user->last_name) ?><?php echo Yii::t('yii', ' suit :') ?></h5>
                    </div>
               
                
                <div class="content_bg_box profile_meme">
                    <?php if($followings = $user->followings(array('order' => 'followings.created_at DESC', 'with' => 'following', 'scopes' => 'following_visible'))): ?>
                            <div id="image-preset-fav"> 
                            <?php foreach($followings as $following): ?>
                                    <a class="ttip follow-user-avatar" title="<?php echo CHtml::encode($following->following->first_name . ' ' . $following->following->last_name) ?>" href="<?php echo Yii::app()->createUrl('site/profile', array('profile' => $following->following->username)) ?>"><img src="<?php echo Yii::app()->baseUrl.'/timthumb.php?src='.Yii::app()->user->getAvatar_url($following->following->user_id).'&w=51&h=52'; ?>" alt="<?php echo CHtml::encode($following->following->first_name . ' ' . $following->following->last_name) ?>" /></a> 
                            <?php endforeach ?>
                            
                            </div> 
                    <?php else: ?>
                        <small><em><?php echo Yii::t('yii', 'No followings') ?></em></small>
                    <?php endif ?>
                </div>
            </div>
        <?php Yii::app()->plugin->endBlock() ?>
        
        <?php Yii::app()->plugin->beginBlock('sidebar') ?>
            
        <?php echo Settings::value('ad1') ?>
        <?php Yii::app()->plugin->endBlock() ?>
        
        <?php Yii::app()->plugin->renderRegion('sidebar') ?>
    </div>
</div>
<script type="text/javascript">
	$("#image-preset,#image-preset-fav").mCustomScrollbar({
		scrollButtons: {
			enable: true,
			scrollSpeed: 90
		},
		theme: "dark"
	});

	//Stop the scroll for user B went the user A send one message in the chat_box layer
	function Stop_Scroll() { $(".chat_box").mCustomScrollbar("stop"); }

</script>				