var mongoose = require('mongoose');
var User = mongoose.model('User');
var Conversation = mongoose.model('Conversation');
var Message = mongoose.model('Message');
var Chatroom = mongoose.model('Chatroom');

module.exports = function(io)
{

	var users = {},
	onlineUsers = {},
	clients = {},
  actions = ['test'];
  io.on("connection", function (client) {
  	clients[client.id] = {client:client};
  	console.log('new client connected to socket.io api : ', client.id)
  	client.emit('identify', 'please login');
    client.on('login', function(data){
    	console.log(data,  client.id , 'logging for lgin data')
		var key = data;
		User.findOne({accessToken:key}).exec(function(err,u){
			if(err) return client.emit('errors', err);
			if(!u) return client.emit('errors', 'wrong key, login again please');
			if(u.expires < new Date()) return client.emit('errors', 'session expired, login again');
			client.Logged = true;
			if(users[u._id] && users[u._id].clients){
	    	users[u._id].clients.push(client.id);
		    }else{
	    		users[u._id] = u;
	    		users[u._id].clients = [client.id];
	    	}
	    	
	    	clients[client.id].user = u._id;
  			client.emit('connected', 'connection open');
  			Chatroom.find({userId: u._id}).exec(function(err,rooms){
  				rooms.forEach(function(room){
	  				console.log('client ' , u.name, ' joining : ' , room.roomId)
	  				client.join(room.roomId);
  				})
  			})
	  		console.log(' client: ', client.id , ' was identified as user : ', u.name)
		    onlineUsers[u._id] = true;
		})
    })

// LIST ONLINE FRIENDS	  
	client.on('onlinefriends', function(){    	
    	if(client.Logged){
		    User.findById(clients[client.id].user).populate("friends").exec(function(err,user){
			    console.log(err,user)
			    if(err)
			    	client.emit('errors', err);
			    else 
			    	client.emit('friends', user.friends)
		  	})
		}
		else
			client.emit('errors', 'not logged in')
	})
	client.on('stream', function(){
    	if(client.Logged){
    		client.emit('errors', 'coming soon')
		}else
    		client.emit('errors', 'not logged in')
	})
	  client.on('conversation', function(to){
	  	startBasicConversation(clients[client.id].user,to);

	  	function startBasicConversation(from,to){
				if(client.Logged){
					console.log(from,to)
					Conversation.findOne({
				    $or: [
	            { to : from, by: to },
	            { by: from, to: to }
	          ]
				  }).exec(function(err,conversation){
				   	if(err)
				   		return client.emit('errors', err)
				   	if(!conversation){
				   		conversation = new Conversation({
				   			by: from,
				   			to : to
				   		});
				   		conversation.save();
  						
  						Chatroom.updateUser({
					   		userId: conversation.by,
					   		roomId: conversation._id
					   	});

					   	Chatroom.updateUser({
					   		userId: conversation.to,
					   		roomId: conversation._id
					   	});
				   	}

				   	client.emit('conversation', conversation);

						if(onlineUsers[conversation.by]){
							if(users[conversation.by].clients){
								users[conversation.by].clients.forEach(function(cl,i){
									clients[cl].client.join(String(conversation._id))
								})
							}
						}
						if(onlineUsers[conversation.to]){
							if(users[conversation.to].clients){
								users[conversation.to].clients.forEach(function(cl,i){
									
									clients[cl].client.join(String(conversation._id))
								})
							}
						}
				   })
				}else{
						client.emit('errors', 'not logged in')
				}
			}
	  })
	  client.on('message', function(conversation, message,attachments){
		//	  	console.log(conversation,message)
			Conversation.findById(conversation).exec(function(err,conversation){
				//	console.log(clients[client.id].user , conversation.by ,  conversation.to )
				if(conversation && !err){
					if(String(conversation.by) === String(clients[client.id].user) || String(conversation.to) === String(clients[client.id].user)){
						var msg = new Message({
							by  : clients[client.id].user,
							conversation: conversation,
							message: message,
							attachments: attachments
						})
						msg.save();
						io.to(conversation._id).emit('message', conversation._id, message, attachments)
					}else{
						client.emit('errors', 'can\'t commit to conversation')
					}
				}
			})
		})

	  client.on("disconnect", function(){
    	
    	if(client.Logged){
	      var disconnected = client.id;
		    var disconnectedUser = users[clients[disconnected].user];
	  		console.log('a client disconnected from socket.io api : ', client.id , ' ', disconnectedUser.name)

		    if(disconnectedUser){
		    	
		    	if(disconnectedUser.clients && disconnectedUser.clients.length > 1){
		    		for(i in disconnectedUser.clients){
		    			if(disconnectedUser.clients[i].id === disconnected)
		    				delete users[disconnectedUser].clients[i];
		    		}
		    	}else{
		    		delete onlineUsers[disconnectedUser._id];
		    		disconnectedUser.clients = [];
		    		delete clients[disconnected]
		    	}
		    }
		  }
	  });
  });
}