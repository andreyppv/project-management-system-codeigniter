$(document).ready(function(){
						   
            $(".chat_box").mCustomScrollbar({
				scrollButtons: {
					enable: true,
					scrollSpeed: 90
				},
				theme: "dark",
			});
			
			
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
			
			$('.singlepage.chat_mod').each(function(){
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
							  scrollTo: 'last',
							  live: true
												});
							  $('.chat_box_popup').mCustomScrollbar("scrollTo","last");
				});					  
				
				
				});
				
				
				
				$(".msginput")
					 .focus(function() {
						  if (this.value === this.defaultValue) {
							this.value = '';
						  }
						  room= {
				 	      itemID: $(this).attr('data-room')
				          }
				          console.log('reading messages',room,chat_user)
				          socket.emit('readMessages', room, chat_user);
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
				  
									   

});