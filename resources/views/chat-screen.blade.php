<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>SimbiLearn - Your intelligent lesson partner</title>
	<link rel="stylesheet" type="text/css" href="{{ secure_asset('css/chat-dialog.css') }}">
	<link rel="stylesheet" type="text/css" href="{{ secure_asset('css/app.css') }}">
	<link href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet" integrity="sha384-wvfXpqpZZVQGK6TAh5PVlGOfQNHSoD2xbE+QkPxCAFlNEevoEH3Sl0sibVcOQVnN" crossorigin="anonymous">
	<link href="https://fonts.googleapis.com/css?family=Roboto" rel="stylesheet">
	<link href="https://fonts.googleapis.com/css?family=Montserrat" rel="stylesheet">
</head>
<body>

	
	<div class="chat_window" id="application">
		<div class="top_menu">
			<div class="buttons">
				<img src="{{ secure_asset('images/simbibot-icon.png') }}" width="60">
				
			</div>
			<div class="title"><img src="{{ secure_asset('images/simbibot-favicon.png') }}" style="width:20px; height: 20px">SimbiBot</div>
			<div id="counter" class="timer">
				<p>Time</p>
			</div>
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
				<input id="txt_input" class="message_input" placeholder="Type your message here..." v-model="newMessage" @keyup.enter="sendMessage"/>
			</div>

			<span>
				<div id="btn_voice_input" class="voice_record" onclick="startButton(event);">
					<div class="text"></div>
				</div>
			</span>
			
			<div class="send_message" @click="sendMessage">
				<div class="icon"></div>
				<div class="text"><i class="fa fa-send"></i></div>
			</div>
		</div>
	</div>
<script src="{{ secure_asset('js/app.js') }}"></script>
{{-- <script src="{{ secure_asset('js/chat-dialog.js') }}"></script> --}}
<script src="{{ secure_asset('js/axios.min.js') }}"></script>
<script src="{{ secure_asset('js/vue.min.js') }}"></script>
<script src="https://js.paystack.co/v1/inline.js"></script>
<script
  src="https://code.jquery.com/jquery-3.3.1.min.js"
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
			userId: document.cookie.userId,
			timer_started: false,
			timeout: null
		},
		methods: {
			start_timer : function(minutes) {
				var seconds = 60;
				var mins = minutes;
				function tick() {
				    //This script expects an element with an ID = "counter". You can change that to what ever you want. 
				    var counter = document.getElementById("counter");
				    var current_minutes = mins-1;
				    seconds--;
				    counter.innerHTML = current_minutes.toString() + ":" + (seconds < 10 ? "0" : "") + String(seconds);
				    if( seconds > 0 ) {
				        vm.timeout = setTimeout(tick, 1000);
				    } else {
				        if(mins > 1){
				            vm.start_timer(mins-1);           
				        }
				        else{
				            vm.newMessage = "quit test";
				            vm.sendMessage();
				        }
				        
				    }
				}
				tick();
			},
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
						console.log(response);
						messages_length = response.data.messages.length;
						console.log("message length is " + messages_length);
						for (let i=0; i < messages_length; i++) {
							if (response.data.messages[i].additionalParameters['timer_action']=='start_time') {
								if (!that.timer_started){
									that.timer_started = true;
									that.start_timer(response.data.messages[i].additionalParameters['test_time']);
									document.getElementById("counter").style.display = "inline";
								}
							}
							else if (response.data.messages[i].additionalParameters['timer_action']=='stop_time') {
								that.timer_started = false;
								clearTimeout(vm.timeout);
								document.getElementById("counter").style.display = "none";
							}
							else if (response.data.messages[i].additionalParameters['payment_action']=='show_payment') {
								console.log("showing pay stack");
								//that.show_payment(that.getCookie('userId'));
								that.show_paystack(response.data.messages[i].additionalParameters['amount'], that.getCookie('userId'), response.data.messages[i].additionalParameters['transaction_id'], response.data.messages[i].additionalParameters['user_email']);
							}
						}
						
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
			},
			show_paystack : function(var_amount, userId, trans_id, user_email) {
						console.log(trans_id);
						console.log("email is " + user_email);
				      var handler = PaystackPop.setup({
				      key: 'pk_test_7dfcdc2c0d8e91d525e52ee606b01e707b805b09',
				      email: user_email,
				      amount: var_amount*100,
				      
				      callback: function(myresponse){



				      	//let that = this;
				      	axios.post("{{ url('confirm_complete_payment') }}",
				      		{driver: 'web',
							userId: userId,
							message: "__payment_successful__",
							transaction_id: trans_id,
							amount: var_amount,
							ref_no: myresponse.reference,
							additionalParameters: []}
				      		).then(function(response){
				      			console.log("response is paystack success is " + response);
				      			if (response.status=="200") {
						    		let messages = response.data.messages || [];
						    		
						    		messages.forEach(msg => {
						    			let message_object = {text: msg.text, attachment: msg.attachment, type: msg.type, bysimbi: true, time: msg.time, actions: msg.actions};
						    			vm.messages.push(message_object);
						    			vm.storeMessage(message_object);
						    		});
						    		additionalParameters = [];
						    		let $message = $($('.message_template').clone().html());
						    		let msgs = $(".messages");
						    		
						    		let $msgs = $('.messages');
						    		$msgs.animate({ scrollTop: $msgs.prop('scrollHeight') }, 2000);
						    	}
				      		
				      	}).catch(function(error){
				      		console.log(error);
				      	}); 
						
				      },
				      onClose: function(){

				      }
				    });

				    handler.openIframe();
			}
		},
		mounted: function(){
			
			if (this.isNew){
				
				this.messages = [{
					text: "Hi I'm Simbi! Your Interactive Learning Assistant", type:'text', bysimbi: true}, {
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
<script src="{{ secure_asset('js/voice_input.js') }}"></script>
</body>
</html>
