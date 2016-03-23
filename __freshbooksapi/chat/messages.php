<link rel="stylesheet" type="text/css" href="<?php echo Yii::app()->theme->baseUrl; ?>/js/content-scroller/jquery.mCustomScrollbar.css" />
<script type="text/javascript" src="<?php echo Yii::app()->theme->baseUrl; ?>/js/content-scroller/jquery.mCustomScrollbar.concat.min.js"></script>
<script>
    var current_room = null;
    function update_rooms(nroom){

              socket.emit('readMessages', nroom, chat_user);
          
    }
      jQuery(document).ready(function($){
    console.log(chat_user)
    socket.emit('listuserchannels', chat_user)
    socket.on('update_rooms', function(r){
      socket.emit('listuserchannels', chat_user, r)
    });

    socket.on('listuserchannel', function(meme, r, stts){
      
      $('.removeme').remove();
      if(meme && $('#'+meme.itemID).html() === void 0){
        $('#'+meme.itemID).remove();
        var roomhtml = '<div class="meme_chat" id="'+meme.itemID+'" onclick="update_rooms({itemID:'+meme.itemID+'})">';
        if(meme.meme_type !== 'videos'){
          roomhtml += '<img src="<?php echo Yii::app()->baseUrl;?>/timthumb.php?src='+meme.url+'&w=53&h=53"/>';
        } else {
          var meme_video = meme.file.split(',');
          //console.log(meme_video)
        if(meme_video[1] === 'youtube'){
        roomhtml += '<img src="<?php echo Yii::app()->baseUrl;?>/timthumb.php?src=http://img.youtube.com/vi/'+meme_video[0]+'/default.jpg&w=53&h=53"/> '; 
        } else if(meme_video[1]==='viemo'){
        vimeo_thumb  = '';//SiteController::get_vimeo_thumb(meme_video[0]);                           
        roomhtml +=  '';  
        } else {
        // echo '<iframe frameborder="0" width="660" height="450" src="http://www.dailymotion.com/embed/video/'.$meme_video[0].'"></iframe>'; 
        roomhtml +=  '<img src="<?php echo Yii::app()->baseUrl; ?>/timthumb.php?src=http://www.dailymotion.com/thumbnail/video/'+meme_video[0]+'&w=53&h=53"/> ';
        }
      }
      roomhtml +=  '<div class="meme_chatcontent"> \
                       <span class="user_name '+stts+'">\
                       '+meme.meme_type.replace('gif-animes', 'gif').replace('videos', 'Vidéo').replace('detournement-images','Détournement')
                       + '</span><span class="chat_time">'+( meme.by.Member_id === chat_user.Member_id ?  'Un de mes Ovnis' :  'J\'ai participé' )+'</span>\
                       <span class="edit_chat">\
                          <img src="<?php echo Yii::app()->theme->baseUrl; ?>/img/edit_icon.png"  class="edit_action"/>\
                          <img src="<?php echo Yii::app()->theme->baseUrl; ?>/img/close_icon.png"  class="delete_action"/>\
                       </span>\
                       <p>'+meme.title+'</p>\
                   </div>\
          </div>'
      if(stts === 'viewed'){
        $(".messages_mid_inner").append(roomhtml);
      }else{
        $(".messages_mid_inner").prepend(roomhtml);        
      }
      if(current_room && meme.itemID === String(current_room.itemID)){
         $('#'+meme.itemID).addClass('selected');  
      }
      $('.meme_chat').each(function(){          
        $(this).click(function(){
           $('.meme_chat').removeClass('selected');
         $(this).addClass('selected');  
        });
      });     
      $('.meme_chat').each(function(){
                  
          $(this).click(function(){
          var requestCallback = new MyRequestsCompleted({
          numRequest: 2
        });
          $.ajax({
              url: '/ovnis/site/Chatmeme/id/'+$(this).attr('id'),
              type: "post",
              success: function(response){    
              requestCallback.addCallbackToQueue(true, function() {
                $('.meme_contt').html(response);
              });         
                          
              },
              error:function(){         
              }
           });
           $.ajax({
              url: '/ovnis/site/Dynamicchatbox/id/'+$(this).attr('id'),
              type: "post",
              success: function(response){  
                  requestCallback.addCallbackToQueue(true, function() {
                 $('#right_chat').html(response);   
                  });                                     
              },
              error:function(){         
              }
           });
           
           
         }); 
         
        });    
      }else if(meme && $('#'+meme.itemID).html() != void 0){
        tis = $('#'+meme.itemID);
        $(tis).find('.user_name').removeClass('viewed').removeClass('notviewed').addClass(stts);
      }
    });
  });
</script>
<div class="well">    
   <table cellpadding="0" cellspacing="0">
	<tr id='channels_container'>
      <?php 	  
	 
	  if(!empty($memes)) {
	  foreach ($memes as $i => $meme): 
    $meme_url   = 'http://'.$_SERVER['SERVER_NAME'].Yii::app()->baseUrl.'/'.$meme->meme_type.'/'.$meme->slug;
    ?>
      <td width="250" valign="top" class="meme_contt">       
        <?php if($meme->meme_type!='videos')  { ?>                      
                <a href="<?php echo $meme_url; ?>" target="_blank" id="ovnis_cont">
                 <img src="<?php echo Yii::app()->baseUrl.'/timthumb.php?src='.$meme->url.'&w=238'; ?>" />
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
              <div class="convers_title" sherif>
                <a href="<?php echo $meme_url; ?>" target="_blank"><b><?php echo CHtml::encode($meme->title) ?> - </b></a>
              </div>
              <div class="userProf_feed">                   
               <?php if(Yii::app()->user->id != $meme->user->user_id) { ?>
                     <a href="<?php echo Yii::app()->createUrl('site/profile', array('profile' => $meme->user->username)) ?>"><img src="<?php echo Yii::app()->user->getAvatar_url($meme->user_fk) ?>" alt="<?php echo CHtml::encode("{$meme->user->first_name} {$meme->user->last_name}") ?>" title="<?php echo CHtml::encode("{$meme->user->first_name} {$meme->user->last_name}") ?>" class="meta_image ttip" style="float:left; padding-right:5px;"/></a> 
                     Par <br>                             
                     <b><?php echo $meme->user->first_name.'&nbsp;'.$meme->user->last_name; ?></b>
                <?php  } ?>                       
               </div> 
     </td>
     <?php 
    endforeach;
	 } else { ?>
     <td width="250" valign="top" class="meme_contt" style="padding:46px 10px 0px 0px;">
          <div id="no_conversation">
                Ici aperçu <br />
                d’un Ovni’s du Net<br />
                d’une conversation<br />
                sélectionnée.
          </div>
     </td>
     <?php } ?>
     <td>    
     
     <!-- MEME Converstation Box Start -->
     <div class="messages_mid">
       <small><span><a href='javascript:void()' id='markread'>Tout marquer comme lu</a></span></small><br />
       <script>
        $("#markread").click(function(){
          socket.emit('clear_all', chat_user);
        });
       </script>
       <div class="messages_mid_inner">
            <h4 class='removeme'>                 
                Vous verrez ici la liste <br /> de vos conversations.
            </h4>
       </div>
     </div>
     <!-- MEME Converstation Box End -->
     
     <!-- Chat Box Start -->
     <div class="singlepage chat_mod messages">
      <div id="right_chat">
      <?php if(!empty($memes)) { 
	  	  
	  ?>
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
            
            
           <?php } else { ?> 
            <div align="center" style="line-height:13px;">
               <h4>Aucune conversation sélectionnée</h4>
               <p>Sélectionner une conversation à gauche pour voir les messages.</p>
            </div>
           <?php } ?>
           
           <div class="chat_popup">                        
                                <div class="chatpopup_inner chat_mod">
                                       <div class="chat_header">
                                             <?php

											 if (isset($_REQUEST['meme'])){

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
     </div> 
       </div>
<script>                        
              jQuery(document).ready(function($){
               
                   var chatroom = {
                        title      : '<?php echo CHtml::encode($meme->title) ?>',
                        itemID     : '<?php echo $meme->meme_id ?>',
                         meme_type   :'<?php echo $meme->meme_type ?>',
                        slug      :'<?php echo $meme->slug ?>',
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

            </script>      <!-- Chat Box End -->
                         <?php }?>
     </td>
     </tr>              
   </table>
</div>