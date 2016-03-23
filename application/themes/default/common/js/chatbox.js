$(document).ready(function(){
	console.log('document is ready');           
       $(".msginput").keypress(function(e){
         room = $(this).attr('data-room')
         if(e.which == 13) {
         var msg = $(this).val().replace(/^\s+|\s+$/g, '');
         if(msg !== ''){
          if($(this).hasClass('msginput')){
	         console.log('sending ' , msg , ' to ' , room)
           socket.emit("send", msg, room);
          }else{
           msgid = $(this).attr('data-msgid')
	       console.log('updating ' , msgid , ' in ' , room)
           socket.emit("update", msg, room, msgid);                        
          }
           $(this).val('');
        }
         }
      });
      
      $(".commentForm").submit(function(event){
      	console.log('Prevented Default Action');
        event.preventDefault();
    });


socket.emit("connect_user", chat_user);
    		
socket.on('user_connected', function(user){
	console.log('Connected to chat server');
  	socket.emit("update_messages");
})

socket.on('user_join', function(who){
	console.log('user Join:' , who);
	$(".user_status_"+who.Member_id).removeClass('offline').addClass('online');
});
socket.on('notif_message', function(tot){
	console.log('New Notification Message Count' , tot)
	if(tot > 0){
		$(".notif_message").html('+' + tot);
	}else{
		$(".notif_message").html(' ');
	}
});
socket.on('update_count', function(){
	console.log('updating messages');
	socket.emit("update_messages");
});

socket.on('user_leave', function(who){
	console.log('user left ' , who); 
	$(".user_status_"+who.Member_id).removeClass('online').addClass('offline');
});
socket.on('delete', function(msg){
	console.log('deleting message ' , msg)
	$(".comment_"+msg).html('deleted');
});
socket.on('update', function(id,msg){
	console.log(id,msg, 'updated');
	$('.msg_desc_'+id).html(msg)
	elem = $('.msg_desc_'+id).parents('.chat_mod')
	  	.find('#chat_input')
	  	elem.val('')
	  	.addClass('msginput')
	  	.removeClass('msgEditor')
	  	.attr('data-msgid', '')
});
socket.on("chat", function(where, who, msg, status, editors,deleters){
	console.log('New Message ')
	console.log(where,who,msg,status,editors,deleters)
	if(who.name !== 'System'){
	  
	   var chat_time = moment(msg.created).fromNow();
	   edit = (  editors.indexOf(chat_user.Member_id) !== -1 ) ?   "<img src=\"/img/edit_icon.png\" data-msgid='"+msg._id+"' class=\"edit_action\"/>" : '';
	   deletehtml = ( deleters.indexOf(chat_user.Member_id) !== -1 ) ?   "<img src=\"/img/close_icon.png\" data-msgid='"+msg._id+"' class=\"delete_action\"/>" : '';
	   $(".chat_inner_"+where).append("\
	     <div class='chat_row comment_"+msg._id+"'>\
	     <div class=\"chat_content\" style='float: left; margin-bottom: 10px; width: 100%;'>\
	     <img src='"+who.image+"' class='image-boardered' style='float: left; width: 40px; margin: 0px 5px 5px 0px;' />\
	     <span class=\"user_name user_status_"+who.Member_id+" "+( status ? 'online' : 'offline' )+"\" style='font-size: 14px;'>" + who.name + "</span>\
	     <span class=\"chat_time msg_"+msg._id+"\" style='font-size: 10px;'>"+ chat_time +" </span>\
	     <span class=\"edit_chat\">\
	     "+edit+"\
	     "+deletehtml+"\
	     </span>\
	     <p class='msg_desc_"+msg._id+"'> " + msg.description + " </p>\
	     </div>\
	     <div style='clear: both;'></div>\
	     </div>\
	   ");	
	   var cont = $('.chat-inner-container');
    	var cont_height = cont[0].scrollHeight;
    	cont.scrollTop(cont_height);
	  
	  try{
	    
		

		
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
		//setInterval(updateTimer, 60000,msg);
			}else{
		$(".chat_inner_"+where).empty();
	    $(".chat_inner_"+where).append("\
	         <div class='chat_row'>\
	         <div class=\"chat_content\" style='float: left; margin-bottom: 10px; width: 100%;'>\
	         <img src='"+who.image+"' class='image-boardered' style='float: left; width: 40px; margin: 0px 5px 5px 0px;'/>\
	         <span class=\"user_name online\" style='font-size: 14px;'>" + who.name + "</span>\
	         <span class=\"chat_time  msg_"+msg._id+"\" style='font-size: 10px;'></span>\
	         <span class=\"edit_chat\">\
	         </span>\
	         <p> " + msg.description + " </p>\
	         </div>\
	         <div style='clear: both;'></div>\
	         </div>\
	    ");
	    var cont = $('.chat-inner-container');
    	var cont_height = cont[0].scrollHeight;
    	cont.scrollTop(cont_height);
	}
});
socket.on("init", function(where, who, msg){
	console.log('received init command [ empty ] ');
	$(".chat_inner_"+where).empty();
});



});