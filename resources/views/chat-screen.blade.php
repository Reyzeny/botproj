<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>SimbiLearn - Your intelligent lesson partner</title>
	<link rel="stylesheet" type="text/css" href="{{ asset('css/chat-dialog.css') }}">
	<link rel="stylesheet" type="text/css" href="{{ asset('css/app.css') }}">
	<link href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet" integrity="sha384-wvfXpqpZZVQGK6TAh5PVlGOfQNHSoD2xbE+QkPxCAFlNEevoEH3Sl0sibVcOQVnN" crossorigin="anonymous">
</head>
<body>

	
	<div class="chat_window" id="application">
		<div class="top_menu">
			<div class="buttons">
				<img src="{{ asset('images/simbibot-icon.png') }}" width="60">
				{{-- <div class="button close"></div>
				<div class="button minimize"></div>
				<div class="button maximize"></div> --}}
			</div>
			<div class="title"><img src="{{ asset('images/simbibot-favicon.png') }}" style="width:20px; height: 20px">SimbiLearn</div>
		</div>
		<ul class="messages">
			
			<template v-for="message in messages">
				
				<li class="message appeared" :class="{ left : message.bysimbi, right : !message.bysimbi }">
					{{-- <div class="avatar"></div> --}}
					<div class="text_wrapper">
						<div class="text" v-html="message.text"></div>
					</div>
				</li>
			
			</template>
			
			<div v-if="getLastMessage().type == 'actions'">
				<center>
					<span v-for="action in getLastMessage().actions"><button class="btn btn-default reply-button" @click="sendMessageWithButton(action.value); vm.additionalParameters.push({'interactive' : true});" style="margin-right: 15px" v-html="action.text"><!-- @{{action.text}} --></button></span>	
				</center>
				
			</div>	
			
			
		</ul>
		<div class="bottom_wrapper clearfix">
			<div class="message_input_wrapper">
				<input class="message_input" placeholder="Type your message here..." v-model="newMessage" @keyup.enter="sendMessage"/></div>
			<div class="send_message" @click="sendMessage">
				<div class="icon"></div>
				<div class="text"><i class="fa fa-send"></i></div>
			</div>
		</div>
	</div>
<script src="{{ asset('js/app.js') }}"></script>
{{-- <script src="{{ asset('js/chat-dialog.js') }}"></script> --}}
<script src="{{ asset('js/axios.min.js') }}"></script>
<script src="{{ asset('js/vue.min.js') }}"></script>
<script
  src="http://code.jquery.com/jquery-3.3.1.min.js"
  integrity="sha256-FgpCb/KJQlLNfOu91ta32o/NMZxltwRo8QtmkMRdAu8="
  crossorigin="anonymous"></script>
<script>
	$(document).ready(function(e){
		
		$('.messages').scrollTop($('.messages')[0].scrollHeight);

		// $(".messages").animate({ scrollTop: $('.messages').prop("scrollHeight")}, 1000);
		
	});
	
	function getNext(e){
		
		let element = e.target;
		let endpoint = element.dataset.url;
		let parent = $(element).parent();

		axios.get(endpoint).then(function(response){
			
			$(parent).html(response.data);
		}).catch(function(error){

		});
		
	}

	function markTopicDone(message, course_id){
		vm.additionalParameters.push({'course_id' : course_id});
		sendMessageWithButton(message);
	}

	function sendMessageWithButton(message){
		
		vm.newMessage = message;
		vm.sendMessage();
		
	}

	let endpoint = "{{ url('backend') }}";
	let isNew = "{{ $isNewUser ?? false }}";
	
	let vm = new Vue({
		el: "#application",
		data: {
			messages: [],
			newMessage: '',
			isNew,
			additionalParameters: [],
			hello: false,
			userId: document.cookie.userId
		},
		methods: {
			sendMessage : function(){
				if (this.newMessage === "" || this.newMessage === null || this.newMessage === undefined){
					return;
				}
				let newMessage = {
					text: this.newMessage,
					bysimbi: false,
					attachment: null,
					type: 'text'
				}
				this.messages.push(newMessage);
				this.storeMessage(newMessage);
				this.newMessage = '';

				let $message = $($('.message_template').clone().html());
				// let messages = $(".messages");
				
				let $messages = $('.messages');
				$messages.animate({ scrollTop: $messages.prop('scrollHeight') }, 300);

				let that = this;
				axios.post(endpoint,
					{driver: 'web',
					userId: this.getCookie('userId'),
					message: newMessage.text,
					additionalParameters: this.additionalParameters}
					).then(function(response){
					let messages = response.data.messages || [];
					
					messages.forEach(msg => {
						let message_object = {text: msg.text, attachment: msg.attachment, type: msg.type, bysimbi: true, time: msg.time, actions: msg.actions};
						that.messages.push(message_object);
						that.storeMessage(message_object);
					});
					that.additionalParameters = [];
					let $message = $($('.message_template').clone().html());
					let msgs = $(".messages");
					
					let $msgs = $('.messages');
					$msgs.animate({ scrollTop: $msgs.prop('scrollHeight') }, 2000);
					
				}).catch(function(error){
					console.log(error);
				});

			},
			storeMessage : function(message){
				if (typeof(Storage) !== "undefined") {
				    // Code for localStorage/sessionStorage.
				    let messages = this.getMessages();
				    
				    if ( messages == null || messages == undefined){
				    	let msgs = [];
				    	msgs.push(message);
				    	localStorage.setItem("messages", JSON.stringify(msgs));
				    }
				    else{
				    	messages = JSON.parse(messages);
				    	messages.push(message);
				    	localStorage.setItem('messages', JSON.stringify(messages));
				    }
				    
				} else {
				    // Sorry! No Web Storage support..
				}
			},
			getMessages: function(){
				messages = localStorage.messages;
				return messages;
			},
			getLastMessage: function(){
				
				if (this.getMessages() === undefined || this.getMessages() === null || this.getMessages() === ''){
					return [];
				}
				let messages = JSON.parse(this.getMessages());
				return messages[messages.length - 1];
				
			},
			setCookie: function(cname, cvalue, exdays){
				var d = new Date();
				    d.setTime(d.getTime() + (exdays*24*60*60*1000));
				    var expires = "expires="+ d.toUTCString();
				    document.cookie = cname + "=" + cvalue + ";" + expires + ";path=/";
			},
			getCookie: function(cname){
				var name = cname + "=";
			    var decodedCookie = decodeURIComponent(document.cookie);
			    var ca = decodedCookie.split(';');
			    for(var i = 0; i <ca.length; i++) {
			        var c = ca[i];
			        while (c.charAt(0) == ' ') {
			            c = c.substring(1);
			        }
			        if (c.indexOf(name) == 0) {
			            return c.substring(name.length, c.length);
			        }
			    }
			    return "";
			}
		},
		mounted: function(){
			
			if (this.isNew){
				
				this.messages = [{
					text: "Hi I'm Simbi!, your tutor; I can take you through courses", type:'text', bysimbi: true}, {
					text: 'Say Hello for us to get started', type: 'text', bysimbi: true		
				}];
			}
			else{

				let messages = localStorage.messages;

				if (messages != null && messages != undefined && messages.length > 0){
					messages = JSON.parse(messages);
					//I know this might be a little hard to understand at a glance
					//but all I'm doing is to reverse the original array, so it starts
					//from the back, then I slice to get the last 20, and then return to the normal sequence
					this.messages = messages.reverse().slice(0, 20).reverse();	
				}
				
			}
			
			
		}

	})
</script>
</body>
</html>
