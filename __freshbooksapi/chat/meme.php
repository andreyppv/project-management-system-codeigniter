  <?php 
    $memeUrl = $meme->post_url;
    $img_name = explode('/',$meme->url);
    $id = Yii::app()->request->getParam('id');
	$title = Yii::app()->request->getParam('title');
	
    if(!$id) {
   ?>
    <div class="item detourme">
          <div class="overlay_box more_view" id="<?php echo $meme->meme_id; ?>">
           <div class="meme_type">       
             <?php                   
               echo '<img src="'.Yii::app()->theme->baseUrl.'/img/'.$meme->meme_type.'.png" />';                  
             ?>       
           </div>       
           <div class="mid_cont">
           <!--<a href=""><img src="<?php echo Yii::app()->theme->baseUrl; ?>/img/fb_likebutt.png" /></a>-->
           <br /> 
           <input type="hidden" id="enable_loadmore" value="1"/>
           </div>
           <?php		   
		      $meme_path = Yii::app()->baseUrl.'/'.$meme->meme_type.'/'.$meme->slug;
		   ?>
           <div class="meme_title"><a href="<?php echo $meme_path; ?>"><?php echo CHtml::encode($meme->title) ?></a></div>
           
         </div>
         <?php if($meme->meme_type!='videos')  { ?>
          <img src="<?php echo Yii::app()->baseUrl;?>/timthumb.php?src=<?php echo $meme->url; ?>&w=239&h=auto" id="meme_img"/>
         <?php } else { 
			$meme_video  =  explode(',',$meme->file);
			if($meme_video[1]=='youtube'){
			  echo '<img src="http://img.youtube.com/vi/'.$meme_video[0].'/0.jpg" id="meme_img">';
			} else if($meme_video[1]=='viemo'){
			  $id    = $meme_video[0];
			  $data  = file_get_contents("http://vimeo.com/api/v2/video/$id.json");
              $data  = json_decode($data);
			  echo '<img src="'.$data[0]->thumbnail_large.'" id="meme_img"/>';
			} else {
			   echo '<img src="http://www.dailymotion.com/thumbnail/video/'.$meme_video[0].'" id="meme_img"/>'; 
			}
		 ?>    

		 <?php } ?>
         
    </div>
   <?php 
   } else { 
   
       $curr_id = $meme->meme_id;
		Meme::pagecount($meme);
		if($meme->meme_type!='videos')  {
			list($width, $height) = getimagesize($meme->url);
			$tot_width  = $width + 319;		
			$tot_height = $height + 450;
			if($width>760)			
			 $set_margin = $width - 760;
			else
			 $set_margin = 0;
		 }else{
		     $tot_width = 919;
			 $width = 600;
			 $tot_height = 500;
		     $set_margin = 0;
		 }
		// $meme_url   = 'http://'.$_SERVER['SERVER_NAME'].Yii::app()->baseUrl.'/meme/'.$meme->meme_id;
		 $meme_url     = 'http://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];

    ?>
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
								  echo '<input type="checkbox" name="facebook_share" id="facebook_share_check" value="1"/>&nbsp;<b>Partager sur Facebook</b>';
								}
							  }
							  ?>                              
                              <input type="hidden" name="meme_url" id="meme_url" value="<?php echo $meme_url; ?>"/>
                              <img src="<?php echo Yii::app()->theme->baseUrl; ?>/img/favourite_share.png" style="float:right; margin-right:27px; margin-top:5px;" id="favourite_share"/>
                           </div>    
                                       
                   </div>
              </div>
              
            </div>
     </div>

    <div id="meme_content_layer">     
    <div class="corgi_feed_well" style=" width:988px; margin:auto;padding-top:53px;padding-bottom:60px;">
                    <div class="feed_stacked">
                        
                        <div class="feed_item meme">
                        
                            <div class="feed_body" style="float:left;padding:0px; margin-right:15px; width:669px;">
                                <div class="row" style="margin-left:0px;">
                                    
                                    <!--<div class="feed_profile_pic">
                                        <a href="<?php echo Yii::app()->createUrl('site/profile', array('profile' => $meme->user->username)) ?>"><img src="<?php echo Yii::app()->user->getAvatar_url($meme->user_fk) ?>" alt="<?php echo CHtml::encode("{$meme->user->first_name} {$meme->user->last_name}") ?>" title="<?php echo CHtml::encode("{$meme->user->first_name} {$meme->user->last_name}") ?>" class="meta_image ttip" /></a>
                                    </div>
                                    <span class="timesago"><?php echo Yii::app()->format->formatTimeago($meme->created_at) ?></span>-->
                                    
                                   <div class="feed_text text-center" style="padding:2px; height:502px; float:none; background:#000000;vertical-align:middle; display:table-cell; width:665px; text-align:center;">                                
                                       
								   <div id="favorite_meme_button">
								   <?php
                                    $check_favorite   =  UserFavorite::model()->findByAttributes(array('user_fk' => Yii::app()->user->id, 'favorite_id' => $meme->meme_id));
									if(Yii::app()->user->id!=$meme->user_fk) {
										if($check_favorite){
									  
										 if(!Yii::app()->user->isGuest)
										  echo '<div id="userfav_buttons"><span id="unfavorite_button"><img src="'.Yii::app()->theme->baseUrl.'/img/mattre_dans_fav.png"/></span></div>';
										 else
										  echo '<a href="'.Yii::app()->createUrl('login').'"><img src="'.Yii::app()->theme->baseUrl.'/img/mattre_dans_fav.png"/></a>';
									   
										} else { 
										 
										   if(!Yii::app()->user->isGuest)
											echo '<div id="userfav_buttons"><span id="favorite_button"><img src="'.Yii::app()->theme->baseUrl.'/img/mattre_dans.png"/></span></div>';
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
                                    
                                    
                                    <div style="width:100%; padding-top:2px; text-align:center;line-height:18px; padding:17px 0px 17px 0px;font-size:15px;" id="meme_title">
                                       <b><?php echo CHtml::encode($meme->title) ?></b>
                                    </div>                                                                       
                                    
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
											   <form action="" style="float:left; width:93%; margin-bottom:0px;" class='commentForm'>
												  <input type='text' name="chat_input" id="chat_input"  data-room='<?= $meme->meme_id ?>' class='msginput' value='Commentez, Réagissez ici ..' />
											   </form>
											   <img src="<?php echo Yii::app()->theme->baseUrl; ?>/img/chat_expand.png" data-original-title="Ouvrir cette conversation dans une fenêtre plus grande" class="ttip chat_expand"/>
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
                                               <div class="chat_inner  chat_inner_<?= $meme->meme_id ?>">

                                                </div>
                                               </div>
                                                <div class="chat_input">
                                                       <form action="" style="float:left; width:93%; margin-bottom:0px;" class='commentForm'>
                                                          <input type='text' name="chat_input" id="chat_input"  data-room='<?= $meme->meme_id ?>' class='msginput' value='Commentez, Réagissez ici ..' />
                                                       </form>
                                                </div>
                                        </div>
                                                                                                                              
                                    </div>
                                    <!-- Chat Box End -->
                                      <script>        
                                      <?php 
                                            $user              = User::model()->findByPk(Yii::app()->user->id);
                                            ?>
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

                                        jQuery(document).ready(function($){

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
											  
                                              
                                              
                                               $(".commentForm").submit(function(event){
                                                  event.preventDefault();
                                              });
                                      
                                      /*
                                              $('input#chat_input').click(function() {
                                                if( $(this).val() == "Commentez, Réagissez ici .." ) {
                                                 $(this).val("");
                                                }
                                              });
                                              
                                              $("input#chat_input").blur(function() {
                                                if( $(this).val() == "" ) {
                                                  $(this).val("Commentez, Réagissez ici ..");
                                                }
                                              });*/
                        

                                      </script> 
                                                   
                                    <!--<fb:comments href="<?php echo $meme_url; ?>" width="665px" numposts="5" colorscheme="light"></fb:comments>-->
                                    
                                    </div>
                                    
                                    <div style="width:100%; padding-top:10px;">
                                    
                                    
                                    <div style="width:auto; float:left;">                                                                            
                                       <?php if(!Meme::hasFlagged($meme->meme_id)): ?>
                                        
                                        <?php if(!Yii::app()->user->isGuest) { ?>
                                        <span class="ttip flag_meme no-underline" title="<?php echo Yii::t('yii', 'Contenu inapproprié') ?>" href="<?php echo Yii::app()->createUrl('site/flag', array('id' => $meme->meme_id)) ?>">
                                            <i class="icon-flag"></i> Signaler un abus
                                        </span>
                                        <?php } else { ?>
                                         <a class="ttip flag_meme1 no-underline" title="<?php echo Yii::t('yii', 'Contenu inapproprié') ?>" href="<?php echo Yii::app()->createUrl('login') ?>">
                                            <i class="icon-flag"></i> Signaler un abus
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
                                <div style="width:100%; float:left;">                                   
                                </div>                                
                             </div>
                             
                             <?php         
				                 echo '<img src="'.Yii::app()->theme->baseUrl.'/img/'.$meme->meme_type.'_content.png" style="float:right; position:absolute; margin-top:-44px; right:0px;"/>';         				  	  
				             ?> 
                             
                            <div style="width:303px; float:right;">  
                             <div class="meme-menu">
                               <div class="alert alert-success" style="display:none;"><button class="close1">x</button>&nbsp;<span></span></div>
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
                               <a href="https://twitter.com/share" class="twitter-share-button"  data-url="<?php echo $meme_url; ?>" data-via="Lovelybuzz-Ovnis" data-lang="fr" data-related="anywhereTheJavascriptAPI" data-count="vertical">Tweet</a>
    <script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0];if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src="https://platform.twitter.com/widgets.js";fjs.parentNode.insertBefore(js,fjs);}}(document,"script","twitter-wjs");</script>
                               </div>
                               
                               <div class="gp_share">
                                   <div class="g-plusone" data-size="tall"></div>
                                    <script type="text/javascript">
                                      window.___gcfg = {lang: 'fr'};
                                      (function() {
                                        var po = document.createElement('script'); po.type = 'text/javascript'; po.async = true;
                                        po.src = 'https://apis.google.com/js/platform.js';
                                        var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(po, s);
                                      })();
                                    </script>
                               </div>
                               <div style="clear:both;">&nbsp;</div>
                               <div id="user_link">
                               
                               <a href="<?php echo Yii::app()->createUrl('site/profile', array('profile' => $meme->user->username)) ?>" style="float:left;">
                                
                                <img src="<?php echo Yii::app()->baseUrl;?>/timthumb.php?src=<?php echo Yii::app()->user->getAvatar_url($meme->user_fk); ?>&w=68&h=auto" alt="<?php echo CHtml::encode("{$meme->user->first_name} {$meme->user->last_name}") ?>" title="<?php echo CHtml::encode("{$meme->user->first_name} {$meme->user->last_name}") ?>" class="meta_image ttip" />              
                                </a>
                               <div style="float:left; width:216px; padding-left:10px; height:55px; clear:right;">
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
						            echo "Autres Ovni's de ";
									echo $meme->user->first_name.'&nbsp;'.$meme->user->last_name;
									echo " :";
						           ?>                      
                                   <div id="image-preset">                                                                        
                                              <?php 
                                                foreach($memes as $meme):
												if($curr_id!=$meme->meme_id){
                                                $img_url = preg_replace('/\.png$/','_thumb.png',$meme->url);
                                              ?>                                            
                                                <?php
                                                
                                                
                                                 $meme_path = Yii::app()->baseUrl.'/'.$meme->meme_type.'/'.$meme->slug;
                                                
                                                
                                                if($meme->meme_type!='videos')  { 
                                                                                                                                               
                                                   if($meme->meme_type!='gif-animes' && $meme->meme_type!='images')												 
                                                      echo '<a class="add-image" href="'.$meme_path.'"><img src="'.$img_url.'" id="meme_img"/></a>';
                                                   else
                                                      echo '<a class="add-image" href="'.$meme_path.'"><img src="'.Yii::app()->baseUrl.'/timthumb.php?src='.$meme->url.'&w=120&h=120" id="meme_img"></a>';												                                             											
                                                 } else { 
                                                     $meme_video  =  explode(',',$meme->file);
                                                     if($meme_video[1]=='youtube'){
                                                      echo '<a class="add-image" href="'.$meme_path.'">
                                                      <img src="'.Yii::app()->baseUrl.'/timthumb.php?src=http://img.youtube.com/vi/'.$meme_video[0].'/0.jpg&w=120&h=120" id="meme_img">                                                  </a>';
                                                    } else if($meme_video[1]=='viemo'){
                                                      $id    = $meme_video[0];
                                                      $data  = file_get_contents("http://vimeo.com/api/v2/video/$id.json");
                                                      $data  = json_decode($data);
                                                      echo '<a class="add-image" href="'.$meme_path.'">
                                                        <img src="'.Yii::app()->baseUrl.'/timthumb.php?src='.$data[0]->thumbnail_large.'&w=120&h=120" id="meme_img"/></a>';
                                                    } else {
                                                       echo '<a class="add-image" href="'.$meme_path.'"><img src="'.Yii::app()->baseUrl.'/timthumb.php?src=http://www.dailymotion.com/thumbnail/video/'.$meme_video[0].'&w=120&h=120" id="meme_img"/></a>'; 
                                                    }
                                                  }
                                                  $get_memeid = $meme->meme_id;
                                                 ?>    
                                            <?php
											 }
											 endforeach; ?>
                                    </div>
                                    <?php } ?>
                                    <br />
                                    <?php 
									  foreach($fav_users as $key1 => $fav_user){
									     if($fav_user->user_fk && Yii::app()->user->id != $fav_user->user_fk) {
										 $user_fav             = User::model()->findByPk($fav_user->user_fk);
										    if(!empty($user_fav)){
										      echo "Cet Ovni's a été partagé par :";
											  break;	                                        	
										    }
										 }
									  }
									?>
                                    <div id="image-preset-fav">                              
                                     <?php									   
									   foreach($fav_users as $key => $fav_user){
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
                                  
                                  
								  $("#favourite_share").click(function(event) {
                            
                                      var  formData = "fb_share="+$('#facebook_share_check').prop('checked')+"&meme_url="+$('#meme_url').val();

								      $.ajax({
										url: '<?php echo Yii::app()->baseUrl; ?>/site/favorite/id/<?php echo $curr_id; ?>',
										data : formData,
										type: "post",
										success: function(response){										
											 $('#userfav_buttons').html(response);
											 $('.alert-success').show();
											 $('.alert-success span').html('Partagé avec succès ^^');
											 $('#main_container,body').css('overflow','inherit');
			                                 $('#meme_contentbox1,#meme_popup1').hide();											
										},
										error:function(){	
											alert("failure");
										}
								      });
									  
								  
								  });
								  
								  $("#close_popup1").click(function(event){
									 event.stopPropagation();	
									 $('#meme_popup1').hide();
									 $('#meme_contentbox1').hide();
		                        });
								  
								  $("body").on("click", "#unfavorite_button", function(event){
								  
                                       $.ajax({
										url: '<?php echo Yii::app()->baseUrl; ?>/site/unfavorite/id/<?php echo $curr_id; ?>',
										type: "post",
										success: function(response){		
										    $('.alert-success').show();								     										
											$('.alert-success span').html('Vous ne partagez plus ce contenu');
											$('#userfav_buttons').html(response);								            
										},
										error:function(){	
											alert("failure");
										}
								      });
									  
									   
									   								  
								  });
								  
								  $('.close1').click(function(){
								    $('.alert-success').hide();							  
								  }); 
								
								  $(".flag_meme").click(function(event) {								 
								    $.ajax({
										url: '<?php echo Yii::app()->baseUrl; ?>/site/flag/id/<?php echo $curr_id; ?>',
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
										     $('.alert-success span').html('Vous suivez dorénavant cet utilisateur');										
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
											     $('.alert-success span').html('Vous ne suivez plus cet utilisateur');											    											
												 $('#follow_button').html(response);
											},
											error:function(){
												alert("failure");
											}
									  });
									
									});
																		
								   
								   
								   $(window).load(function(){					
								   						   
									  var $image_container = $('#image-preset');									   
									  $('.chat_mod').each(function(){
									      $(this).find(".chat_expand").click(function(event){							
											 var item = $(this).attr('data-room');

                                             $('.chat_popup').show();
											 $('.chat_box_popup').mCustomScrollbar("destroy");
											 $(".chat_box_popup").mCustomScrollbar({
													scrollButtons: {
														enable: true,
														scrollSpeed: 90
													},
													theme: "dark",
										//	  scrollTo: 'last',
											  live: true
																});
										     // $('.chat_box_popup').mCustomScrollbar("update");
										   //   $('.chat_box_popup').mCustomScrollbar("scrollTo","last");
									       });					   
								      });
									   
									   
									                                      
									   $wind_height         =  $(document).height();                                       
                                       $('#meme_popup').css('height',$wind_height);
									   $image_container.imagesLoaded( function() {
											   $("#image-preset,#image-preset-fav").mCustomScrollbar({
													scrollButtons: {
														enable: true,
														scrollSpeed: 90
													},
													theme: "dark"
											   });
                                        });
                                   });
                                </script>
                                                         
                               
                                                                                                                         
                            </div>
                            <div style="width:303px; float:right;">
                               <!-- <div id="fan_like">
                                 <fb:like href="https://www.facebook.com/lovelybuzzfr" layout="button_count" action="like" show_faces="true" share="false"></fb:like>
                               </div> -->
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
       <?php }  ?>
       
       <!--<div id="login-modal" class="modal hide fade">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
            </div>
            <div class="modal-body" id="login-modal-content" style="margin-left:45px;">
        
            </div>
            <div class="modal-footer">
                <a href="#" data-dismiss="modal" class="btn">Close</a>
            </div>
        </div>-->

<?php 
    $js = <<<JS
    $('.fb-comment-btn').click(function(){
        if(!$(this).hasClass('fb-comments-init')) {
            var href = $(this).attr('href');
            var uniqid = 'fb-comments-' + _.uniqueId();
            $(this).parent().parent().append('<div id="'+ uniqid +'"><div class="fb-comments" data-href="' + href + '" data-width="620" data-num-posts="10"></div></div>');
            FB.XFBML.parse(document.getElementById(uniqid));
            $(this).addClass('fb-comments-init');
        }
        else {
            $(this).parent().parent().find('.fb-comments').toggle();
        }
        var comments = $(this).parent().parent().find('.fb-comments');   
        $("html, body").animate({ scrollTop: comments.offset().top - 100}, 400);
            
        return false;
    });
JS;
    Yii::app()->clientScript->registerScript('fb-comment-btn', $js, CClientScript::POS_END);
    
    if($single) {
    
        /*$this->extra[] = '<link rel="image_src" href="' . $meme->url . '" />';
    	$this->og_image = $meme->url;
    	$this->og_url = $memeUrl;
    	$this->og_title = CHtml::encode($meme->title);*/
    
        Yii::app()->clientScript->registerScript('fb-comment-btn-click', <<<JS
                $('.fb-comment-btn').parent().parent().append('<div id="fb-comments-1"><div class="fb-comments" data-href="' + $('.fb-comment-btn').attr('href') + '" data-width="620" data-num-posts="10"></div></div>');
                $('.fb-comment-btn').addClass('fb-comments-init');
JS
    , CClientScript::POS_END);
    }
    
    $url = Yii::app()->createUrl('/site');
    
    if(!Yii::app()->user->isGuest) {
        $js = <<<JS
        $('.like-btn').click(function(){
            if($(this).hasClass('liking')) {
                return false;
            }

            $(this).addClass('liking');
            var self = this;
            var isLiked = $(this).hasClass('liked');
            var meme_id = $(this).data('meme-id');
            var url =  '$url' + (isLiked ? '/memeunlike' : '/memelike');

            $.get(url, {id: meme_id}, function() {
                $(self).removeClass('liking');
            }, 'json');

            if(isLiked) {
                $(this).removeClass('liked');
            }
            else {
                $(this).addClass('liked');
            }
        });
JS;
        Yii::app()->clientScript->registerScript('like-btn', $js, CClientScript::POS_END);
    }
    else {
        Yii::app()->clientScript->registerScript('login-modal', <<<JS
                $.get('$url/login', function(content){
                    $('#login-modal-content').html(content);
                });
JS
        , CClientScript::POS_END);
    }
    
    
    Yii::app()->clientScript->registerScript('mark-flag', <<<JS
        $('.mark-flag').click(function(){
            var href = $(this).attr('href');
            var self = this;
            $.get(href,function(){
                $(self)
                    .fadeOut();
            });
            return false;
        });
JS
    , CClientScript::POS_END);
?>