<?php
	Yii::app()->clientScript->scriptMap=array(
		'jquery.min.js'=>true,
	);
	
	$id          =   Yii::app()->request->getParam('id'); 
	$meme        =   Meme::model()->findByAttributes(array('slug' => $id));
    
	
	if(!empty($meme)){
	
		if($meme->meme_type!='videos')  { 
		  $img_path   =  $meme->url;
		} else { 
				
			  $meme_video  =  explode(',',$meme->file);
			  
			  if($meme_video[1]=='youtube'){
				 $img_path  = Yii::app()->baseUrl.'/timthumb.php?src=http://img.youtube.com/vi/'.$meme_video[0].'/0.jpg&w=300&h=300';
			  } else if($meme_video[1]=='viemo'){
				  $id       = $meme_video[0];
				  $data     = file_get_contents("http://vimeo.com/api/v2/video/$id.json");
				  $data     = json_decode($data);
				  $img_path = Yii::app()->baseUrl.'/timthumb.php?src=http://img.youtube.com/vi/'.$data[0]->thumbnail_large.'/0.jpg&w=300&h=300';
			   } else {
				  $img_path = 'http://www.dailymotion.com/thumbnail/video/'.$meme_video[0];
			   }
		
		}
	}
	
	 
	
	 $search          =   array('/ovnis/', '/');
     $replace         =   array('', '_');
	 
	 $req_url         =   str_replace($search,$replace,$_SERVER['REQUEST_URI']);
	 
	 if($req_url)
	 $metaData        =   MetaData::model()->findByAttributes(array('slug' => $req_url));
	
	 $current_action  = Yii::app()->controller->action->id;	
	 
	/* echo $req_url;
	exit;*/
		  
?><!DOCTYPE html>
<html lang="en" prefix="og: http://ogp.me/ns# fb: http://ogp.me/ns/fb#"  xmlns:og="http://ogp.me/ns#" xmlns:fb="http://www.facebook.com/2008/fbml">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
     <!--   <meta name="author" content="Muhammad Mahad Azad">-->
        
		<meta name="viewport" content="width=device-width; initial-scale=1.0; maximum-scale=1.0; user-scalable=0;" />  
		<?php if(!empty($meme)) { ?>
        <metaproperty="og:locale" content="fr_FR" />
        <meta property="og:title" content="<?php echo  $meme->title; ?>" />
        <meta name="twitter:title" content="<?php echo $meme->title; ?>" />
        <meta property="og:url" content="<?php echo 'http://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI']; ?>" />
        <meta property="og:site_name" content="Lovely Buzz" />
        <meta property="fb:app_id" content="1507112422763131" />
        <meta property="og:type" content="article" />
        <meta property="og:image" content="<?php echo $img_path; ?>" />
        <meta name="twitter:image" content="<?php echo $img_path; ?>" />
        <?php } ?>
        <?php echo is_array($this->extra) ? implode(PHP_EOL, $this->extra) : $this->extra; echo PHP_EOL;?>
        <!-- Le styles -->
        <link href="<?php echo Yii::app()->theme->baseUrl; ?>/css/bootstrap.css" rel="stylesheet">
        <link href="<?php echo Yii::app()->theme->baseUrl; ?>/css/bootstrap-responsive.css" rel="stylesheet">     
        <link href='<?php echo Yii::app()->theme->baseUrl; ?>/css/corgi.css' rel='stylesheet' type='text/css'>
        <link href='<?php echo Yii::app()->theme->baseUrl; ?>/css/font-awesome.min.css' rel='stylesheet' type='text/css'>
        <script src="<?php echo Yii::app()->theme->baseUrl; ?>/js/jquery-1.10.0.min.js" type="text/javascript"></script>        
        <script src="<?php echo Yii::app()->theme->baseUrl; ?>/js/underscore-min.js" type="text/javascript"></script>
        <script src="<?php echo Yii::app()->theme->baseUrl; ?>/js/corgi.js" type="text/javascript"></script>
        <script src="<?php echo Yii::app()->theme->baseUrl; ?>/js/imagesloaded.pkgd.js"></script>
        <script src="<?php echo Yii::app()->theme->baseUrl; ?>/js/packery.pkgd.js" type="text/javascript"></script>        
        <script src="<?php echo Yii::app()->theme->baseUrl; ?>/js/moment-with-langs.min.js" type="text/javascript"></script>        
        <script src="http://code.jquery.com/ui/1.11.0/jquery-ui.js"></script>
        <script src="<?php echo Yii::app()->theme->baseUrl; ?>/js/socket.io.min.js"></script>
        
        <script>
        	moment.lang('fr');
            var socket = io.connect('http://www.lovely-buzz.fr:3000');
            function updateTimer(msg){
            	 $('.msg_'+msg._id).html( moment(msg.created).fromNow() );
            }
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
            jQuery(document).ready(function($){
				socket.emit("connect_user", chat_user);
				socket.on('user_connected', function(user){
		              	socket.emit("update_messages");
				})
            	socket.on('user_join', function(who){
            		$(".user_status_"+who.Member_id).removeClass('offline').addClass('online');
            	});
            	socket.on('notif_message', function(tot){
            		if(tot > 0){
            			$(".notif_message").html('+' + tot);
            		}else{
            			$(".notif_message").html(' ');
            		}
            	});
            	socket.on('update_count', function(){
            		socket.emit("update_messages");
            	});

            	socket.on('user_leave', function(who){
            		$(".user_status_"+who.Member_id).removeClass('online').addClass('offline');
            	});
                socket.on('delete', function(msg){
            		$(".comment_"+msg).html('deleted');
            		
            	});
            	socket.on('update', function(id,msg){
            		$('.msg_desc_'+id).html(msg)
            		elem = $('.msg_desc_'+id).parents('.chat_mod')
									  	.find('#chat_input')
									  	
									  	elem.val('')
									  	.addClass('msginput')
									  	.removeClass('msgEditor')
									  	.attr('data-msgid', '')
            	})
                socket.on("chat", function(where, who, msg, status, editors,deleters){
                	if(who.name !== 'System'){
					  
					   var chat_time = moment(msg.created).fromNow();
                       edit = (  editors.indexOf(chat_user.Member_id) !== -1 ) ?   "<img src=\"<?php echo Yii::app()->theme->baseUrl; ?>/img/edit_icon.png\" data-msgid='"+msg._id+"' class=\"edit_action\"/>" : '';
                       deletehtml = ( deleters.indexOf(chat_user.Member_id) !== -1 ) ?   "<img src=\"<?php echo Yii::app()->theme->baseUrl; ?>/img/close_icon.png\" data-msgid='"+msg._id+"' class=\"delete_action\"/>" : '';
					   $(".chat_inner_"+where).append("\
                         <div class='chat_row comment_"+msg._id+"'>\
                         <img src='"+who.image+"&w=34&h=33'/>\
                         <div class=\"chat_content\">\
                         <span class=\"user_name user_status_"+who.Member_id+" "+( status ? 'online' : 'offline' )+"\">" + who.name + "</span>\
                         <span class=\"chat_time msg_"+msg._id+"\">"+ chat_time +" </span>\
                         <span class=\"edit_chat\">\
                         "+edit+"\
                         "+deletehtml+"\
                         </span>\
                         <p class='msg_desc_"+msg._id+"'> " + msg.description + " </p>\
                         </div>\
                         </div>\
                       ");	
					   
					  
                      try{
					    
						
					    setTimeout(function(){
							$(".chat_box").mCustomScrollbar("scrollTo","bottom");
							$('.chat_box_popup').mCustomScrollbar("scrollTo","last");
						},2000);
						
						}catch(e){

						}

						if( (editors.indexOf(chat_user.Member_id) !== -1  &&  deleters.indexOf(chat_user.Member_id) !== -1) )
						{
						    $('.msg_'+msg._id).parents('.chat_content').hover(function(event) {
										 $(this).find('.chat_time').hide();
										 $(this).find('.edit_chat').show();
										 event.stopPropagation();
									}, function(event) {
										 $(this).find('.edit_chat').hide();
										 $(this).find('.chat_time').show();
										 event.stopPropagation();
							});

	                         $('.edit_action').click(function(){
									  var edit_content = $(this).parents('.chat_content').find('p').html();
									  elem = $(this).parents('.chat_mod')
									  	.find('#chat_input')
									  	
									  	elem.val(edit_content)
									  	.removeClass('msginput')
									  	.addClass('msgEditor')
									  	.attr('data-msgid', $(this).attr('data-msgid'))
									  	

				             });
				             $('.delete_action').click(function(){
            					console.log('deleting ... ')

				             	socket.emit("delete", where, $(this).attr('data-msgid'));
				             });
				   
                     	}
					    //updateTimer(msg);									
						setInterval(updateTimer, 60000,msg);
							}else{
						$(".chat_inner_"+where).empty();
                        $(".chat_inner_"+where).append("\
                             <div class='chat_row'>\
                             <img src='"+who.image+"&w=34&h=33'/>\
                             <div class=\"chat_content\">\
                             <span class=\"user_name online\">" + who.name + "</span>\
	                         <span class=\"chat_time  msg_"+msg._id+"\"></span>\
                             <span class=\"edit_chat\">\
                             </span>\
	                         <p> " + msg.description + " </p>\
                             </div>\
                             </div>\
                        ");
					}
                });
                socket.on("init", function(where, who, msg){
                    	$(".chat_inner_"+where).empty();
              	});

          });
        </script>
        <!-- HTML5 shim, for IE6-8 support of HTML5 elements -->
        <!--[if lt IE 9]>
          <script src="<?php echo Yii::app()->theme->baseUrl; ?>/js/html5shiv.js"></script>
        <![endif]-->
        <?php
		  $username  =   Yii::app()->request->getParam('profile');
		  
		  if($id && !is_numeric($id)){
		   $getTitle  =   str_replace('-',' ',$id);
		   $pageTitle =   CHtml::encode($meme->title);
		  }
		  else if(!empty($metaData)&& $metaData->meta_title!=''){
		   $pageTitle  = $metaData->meta_title;
		  }
		  else if($username!=''){
		    $user = User::model()->findByAttributes(array('username' => $username));
		    $pageTitle  = $user->first_name.' '.$user->last_name;
		  }
		  else{
		   $pageTitle = CHtml::encode($this->pageTitle);
		  }
		  		  
		  if(!empty($metaData) && $metaData->meta_desc!=''){
		    $pageDescription = $metaData->meta_desc;
		  } 
		  else if($username!=''){
		    $pageDescription = "Lovely Buzz : Pour Découvrir, Créer et Partager les Ovni's du Net";
		  }
		  else {
		    $pageDescription = $this->pageDescription;
		  }
		  
		  
		  
		?>
        <title><?php echo $pageTitle; ?></title>
        <?php if($pageDescription): ?>
             <meta name="description" content="<?php echo $pageDescription ?>">
        <?php endif ?>
        
        <?php if($this->pageKeywords): ?>
             <meta name="keywords" content="<?php echo $this->pageKeywords ?>">
        <?php endif ?>
        <script type="text/javascript">					  				
		
		
		var MyRequestsCompleted = (function() {
    var numRequestToComplete, 
        requestsCompleted, 
        callBacks, 
        singleCallBack; 

    return function(options) {
        if (!options) options = {};

        numRequestToComplete = options.numRequest || 0;
        requestsCompleted = options.requestsCompleted || 0;
        callBacks = [];
        var fireCallbacks = function () {
            for (var i = 0; i < callBacks.length; i++) callBacks[i]();
        };
        if (options.singleCallback) callBacks.push(options.singleCallback);

        

        this.addCallbackToQueue = function(isComplete, callback) {
            if (isComplete) requestsCompleted++;
            if (callback) callBacks.push(callback);
            if (requestsCompleted == numRequestToComplete) fireCallbacks();
        };
        this.requestComplete = function(isComplete) {
            if (isComplete) requestsCompleted++;
            if (requestsCompleted == numRequestToComplete) fireCallbacks();
        };
        this.setCallback = function(callback) {
            callBacks.push(callBack);
        };
    };
    })();  
		  
		  $(window).load(function(){
		     try{
			 $('.chat_box').mCustomScrollbar("destroy");
		   
		     $(".chat_box").mCustomScrollbar({
					scrollButtons: {
						enable: true,
						scrollSpeed: 90
					},
					theme: "dark"
			  });	
			  
			  	} catch(e){
			  		
			  	}	
			  $('.user_pro_row').each(function(){
			   $(this).find(".chat_box").mCustomScrollbar("scrollTo","bottom");	
			  });
								
			  
			
			 
			 
			 $('.singlepage.chat_mod').each(function(){
			           
			          $(".chat_box").mCustomScrollbar("scrollTo","bottom");				          
					   
					  $(this).find(".chat_expand").click(function(event){	
							  $('.chat_popup').show();
							  $('.chat_box_popup').mCustomScrollbar("destroy");
							  $(".chat_box_popup").mCustomScrollbar({
									scrollButtons: {
										enable: true,
										scrollSpeed: 90
									},
									theme: "dark"
							});
							
			                $(".chat_box_popup").mCustomScrollbar("scrollTo","bottom"); 
		              });					   
			 });				 
			
			/* $('.chat_row').each(function(){			
				  $(this).hover(
					  function(){
						$(this).find('.edit_chat').show();
						$(this).find('.chat_time').hide();
					  },
					  function(){
						$(this).find('.edit_chat').hide();
						$(this).find('.chat_time').show();
					  }				  
				  );
			   });	*/
						 
		  });	
		 
		  $(document).ready(function(){
		  			 
			 	 $('.profile_page').each(function(){
					  $(this).find(".chat_expand").click(function(event){	
							 $(this).parents('.chat_mod').find('.chat_popup').show();
							 $(this).parents('.chat_mod').find('.chat_box_popup').mCustomScrollbar("destroy");
							 $(this).parents('.chat_mod').find(".chat_box_popup").mCustomScrollbar({
									scrollButtons: {
										enable: true,
										scrollSpeed: 90
									},
									theme: "dark"
							});
							$(this).parents('.chat_mod').find(".chat_box_popup").mCustomScrollbar("scrollTo","bottom");
		              });					   
			   });
						
			   	
			 
			 /*$('.commentForm').submit(function(){
			   
					$('.chat_box').mCustomScrollbar("update");
					$('.chat_box_popup').mCustomScrollbar("update");
					$('.chat_box').mCustomScrollbar("scrollTo","last");	                       
					$('.chat_box_popup').mCustomScrollbar("scrollTo","last");
			 
			 });*/
			 
			 $('.commentForm').on('submit', function(e) { 
				 //$(this).parents('.chat_mod').children(".chat_box").mCustomScrollbar("update");				 
				 //$(this).parents('.chat_mod').children(".chat_box").mCustomScrollbar("scrollTo","bottom");				 						 
    		 });
		   						
			 $('body').on("click", ".meme_title", function(event) {								
				 event.stopPropagation();			
			 });
			
		     var $container = $('#container_packery');
				
			 // initialize Packery after all images have loaded
			 $container.imagesLoaded( function() {
			    $('#loading_image').hide();
			    $('#appended-demo').show();
			    $container.packery({
				  itemSelector: '.item',
			    });			 
			});	 
			
			
			$('.meme_chat').each(function(){			    
			  $(this).click(function(){
			     $('.meme_chat').removeClass('selected');
				 $(this).addClass('selected');  
			  });
			});	
			
			
			$(".msginput")
			 .focus(function() {
				  if (this.value === this.defaultValue) {
					this.value = '';
					  room= {
					 	itemID: $(this).attr('data-room')
					  }
					  console.log('reading messages');
					  socket.emit('readMessages', room, chat_user);
				 }
			  })
			 .blur(function() {
				  if (this.value === '') {
					this.value = this.defaultValue;
				  }
			});		
													
			
			   
			   
			   $('.chat_close').click(function(){
			       $('.chat_popup').hide();
			   });			   
			   
			   $( ".chat_popup" ).draggable({ cancel: ".chat_box_popup,.chat_input" });
			
		   /*var $container = $('#container_packery');
		   // initialize
		   $container.packery({
			  itemSelector: '.item',
           });	*/
		  		 
		   $wind_height  =  $( window ).height();
		   
		   $('#video_upload').click(function(){
				$('#main_container').css('position','fixed');
				$('#main_container').css('overflow','hidden');
				$('#meme_popup').show();		   
		   });
		   
		   $('body').on("click", ".more_view", function() {
		   		      
		    $.ajax({
					url: '/ovnis/site/Popup/id/'+$(this).attr('id'),
					type: "post",
					success: function(response){					    
						//$('#main_container').css('position','fixed');
						$('#main_container,body').css('overflow','hidden');
						$('#meme_contentbox').show();
						$('#meme_contentbox').html(response);
						FB.XFBML.parse(document.getElementById('foo'));
					},
					error:function(){
						alert("failure");
					}
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
		   
		   
		   $("body").on("click", "#close_popup", function(event){
		         $('#meme_popup').hide();
				 $('#meme_contentbox').html(" ");
				 $('#meme_contentbox').hide();
				 
				 $('#main_container').css('position','inherit');
				 $('#main_container,body').css('overflow','inherit');
		   });
		   
		   
		   
		   $('#fb_gallery').click(function(){
	     	  			  
			  $.ajax({
				url: '/ovnis/generate/Fb_gallery/',
				type: "post",
				success: function(response){
					$('#meme-templates').html(response);
				},
				error:function(){
					alert("failure");
			 	}
			});
			  
			  
	       });
		   
		   $('#image_gallery').click(function(){
	     	  			  
			  $.ajax({
				url: '/ovnis/generate/Image_gallery/',
				type: "post",
				success: function(response){
					$('#meme-templates').html(response);
				},
				error:function(){
					alert("failure");
			 	}
			});
			  
			  
	       });
		  
		    
			
			
		    $('#save_meme').click(function(){
			  $('#save-btn').click();
			});			
			
		    var ini_canwidth   = '558px';
			var ini_canheight  = '470px';
		  
		    $('#c,.upper-canvas ').attr('width',ini_canwidth);
			$('#c,.upper-canvas ').attr('height',ini_canheight);
				
		    $('#c,.upper-canvas,.canvas-container').css('width',ini_canwidth);
		    $('#c,.upper-canvas,.canvas-container').css('height',ini_canheight);
		    
			$("body").on("click", "#favorite_button", function(event){
			  $('#main_container,body').css('overflow','hidden');
			  $('#meme_contentbox1,#meme_popup1').show();
			  $('#meme_popup1').css('height','100%');
			});			
				
															
		  });
		  

		</script>
   
    </head>
    <body>

    <div id="main_container">  
    
     <?php   
	
	   /*require '/var/www/lovely-buzz.fr/wordpress/ovnis/fb_sdk/facebook.php';
	  
	   $facebook = new Facebook(array(
         'appId'  => '1507112422763131',
         'secret' => '20db3d01abc83b38d01d6a163a331367',
       ));

       // Get User ID
       $user = $facebook->getUser();	    	  
	   
	   if ($user) {	   
	     try { 
          $facebook->api('/me/feed', 'post', array('link' =>'http://www.lovely-buzz.fr/ovnis/images/difference-entre-une-choco-et-un-pain-au-chocolat',
		  'description' => "Lovely Buzz : Pour Découvrir, Créer et Partager les Ovni's du Net"));
          echo 'Successfully posted to Facebook';
		  
         } catch(Exception $e) {
          echo $e->getMessage();
         }
	   
		 
	   }
	    
		exit;*/
	 ?>
     <div id="meme_contentbox">
      &nbsp;
     </div>
        <div id="fb-root"></div>
        <script>
        window.fbAsyncInit = function()
        {
            FB.init( {
                appId  : '<?php echo Yii::app()->params['hauth']['config']['providers']['Facebook']['keys']['id'] ?>',
                status : true,
                cookie : true,
                xfbml  : true
            } );
        };
    
        ( function( d )
        {
            var js, id = 'facebook-jssdk', ref = d.getElementsByTagName('script')[0];
            if (d.getElementById(id)) {return;}
            js = d.createElement('script'); js.id = id; js.async = true;
            js.src = "//connect.facebook.net/fr_FR/all.js";
            ref.parentNode.insertBefore(js, ref);
        }( document ) );
        </script>
             
		<?php echo Yii::app()->plugin->onBodyStart(new CEvent($this)) ?>
        
        <div class="navbar navbar-fixed-top">
            <div class="navbar-inner">
                <div class="container">
                    <button type="button" class="btn btn-navbar" data-toggle="collapse" data-target=".nav-collapse">
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                    </button>
                    <a class="brand" href="http://lovely-buzz.fr/"><img src="<?php echo Yii::app()->theme->baseUrl; ?>/img/logo.png"></a>
                    
                    <div class="nav-collapse collapse">
                        
						<div id="navigation">
                            <ul id="menu-navigation" class="menu">
                                <li  class="menu-item">
                                  <a href="http://www.lovely-buzz.fr/videos-droles-buzz/">Les événements à l'affiche</a>
                                </li>
                                <li  class="menu-item">
                                  <a href="http://www.lovely-buzz.fr/actu-histoires-insolites/">Coup de projecteur</a>
                                </li>
                                <li  class="menu-item">
                                  <a href="http://www.lovely-buzz.fr/buzz-marketing/">Nos idées et pensées</a>
                                </li>             
                            </ul>
                        </div>

                        <div id="social_links"><a href="https://www.facebook.com/lovelybuzzfr" target="_blank" class="fb_link">Facebook</a><a href="https://twitter.com/LovelyBuzzFR" target="_blank" class="tw_link">Twitter</a><a href="https://plus.google.com/112846664779938358896/" target="_blank" class="gp_link">Google plus</a><a href="mailto:lovely_buzz@yahoo.fr" class="rss_link">Contact</a></div>
                        
                        <ul class="nav pull-right" id="yw0">
                        
                          <li><a  href="<?php echo Yii::app()->homeUrl ?>mis-en-avant"><img src="<?php echo Yii::app()->theme->baseUrl; ?>/img/les_ovni.png"></a></li>
                          <?php
						   if(!Yii::app()->user->isGuest && $current_action!='Followuser'){ ?>                          
                      								   <li>
                                    <a href="<?php echo Yii::app()->homeUrl ?>generate">Créer un Ovni's</a>
                                    <a href="<?php echo Yii::app()->homeUrl ?>my-favoris">Mes coups de cœur</a>
                                 </li>
                                 <li>
                                   <a href="<?php echo Yii::app()->homeUrl ?>my-memes">Mes Ovni's</a>
                                   <a href="<?php echo Yii::app()->homeUrl ?>favoris-user">Mes utilisateurs favoris</a>
                                 </li>
                                 <li><a href="<?php echo Yii::app()->homeUrl ?>update_profile">Mon profil</a>
								 <a href="<?php echo Yii::app()->homeUrl ?>messages">Mes messages<sup class = "notif_message">0</sup></a></li>
                                 <li><a href="<?php echo Yii::app()->homeUrl ?>logout">X</a></li>


                           <?php 
						   }else if($current_action=='Followuser') {
						   
						   } else { ?>
                                 <li id="create_link"><a href="<?php echo Yii::app()->homeUrl ?>generate">Créer un Ovni's</a></li>
                                 <li><a href="<?php echo Yii::app()->homeUrl ?>login/">Se connecter</a></li>
                           <?php } ?>
                           
                        </ul>
                        
						<?php
                        /*Yii::app()->plugin->addMenuItem('top-menu', array('label' => Yii::t('yii', 'Home'), 'url' => Yii::app()->homeUrl));
                        Yii::app()->plugin->addMenuItem('top-menu', array('label' => Yii::t('yii', 'Create Meme'), 'url' => array('generate/index')));
                        Yii::app()->plugin->addMenuItem('top-menu', array('label' => Yii::t('yii', 'My Memes'), 'url' => array('site/mymemes'), 'visible' => !Yii::app()->user->isGuest));
                        
                        $headerPages = Page::model()->position('header')->active()->findAll();
                        if($headerPages) {
                            foreach($headerPages as $page) {
                                Yii::app()->plugin->addMenuItem('top-menu', array(
                                    'label' => Yii::t('yii', $page->title),
                                    'url' => array(Yii::app()->createUrl('site/cms', array('slug' => $page->slug))),
                                ));
                            }
                        }
                        
                        Yii::app()->plugin->addMenuItem('top-menu', array('label' => Yii::t('yii', 'Contact'), 'url' => array('/site/contact')));
                        Yii::app()->plugin->addMenuItem('top-menu', array('label' => Yii::t('yii', 'Login'), 'url' => array('/site/login'), 'visible' => Yii::app()->user->isGuest));
                        Yii::app()->plugin->addMenuItem('top-menu', array('label' => Yii::t('yii', 'Register'), 'url' => array('/site/register'), 'visible' => Yii::app()->user->isGuest));
                        Yii::app()->plugin->addMenuItem('top-menu', array(
                                    'label' => '<i class="icon-cog icon-white"></i> ' . Yii::t('dict', 'My Account'),
                                    'url' => '#',
                                    'visible' => !Yii::app()->user->isGuest,
                                    'itemOptions' => array('class' => 'dropdown', 'id' => 'my-account'),
                                    'linkOptions' => array('class' => 'dropdown-toggle', 'data-toggle' => 'dropdown'),
                                    'submenuOptions' => array('class' => 'dropdown-menu'),
                                    'items' => array(
                                        array('label' => '<i class="icon-user"></i> ' . Yii::t('dict', 'My Profile'), 'url' => array('/site/update_profile')),
                                        array('label' => '<i class="icon-key"></i> ' . Yii::t('dict', 'Change Password'), 'url' => array('/site/change_password')),
                                        array('label' => '<i class="icon-off"></i> ' . Yii::t('dict', 'Logout'), 'url' => array('/site/logout')),
                                    )
                        ));
                        
                        Yii::app()->plugin->renderMenu('top-menu', array(
                            'encodeLabel' => false,
                            'htmlOptions' => array(
                                'class' => 'nav pull-right',
                            ),
                        ));*/
                        ?>
                    </div><!--/.nav-collapse -->
                </div>
            </div>
        </div>

        <div class="container">
            