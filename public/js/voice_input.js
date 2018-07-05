
var final_transcript;
var recognition;
var ongoing = false;
var recognition_supported = false;
var txt_input = document.getElementsByClassName("message_input");
var btn_voice_input = document.getElementsByClassName("voice_record");
if (!('webkitSpeechRecognition' in window)) {
  recognition_supported = false;
} else {
  recognition_supported = false;
  recognition = new webkitSpeechRecognition();
  recognition.continuous = false;
  recognition.interimResults = true;

  recognition.onstart = function() {
  	
  };

  recognition.onresult = function(event) {
      var interim_transcript = '';
      for (var i = event.resultIndex; i < event.results.length; ++i) {
        if (event.results[i].isFinal) {
          final_transcript += event.results[i][0].transcript;
          txt_input[0].setAttribute('value', final_transcript);
        } else {
          interim_transcript += event.results[i][0].transcript;
          txt_input[0].setAttribute('value', interim_transcript);
        }
      }
      txt_input[0].setAttribute('value', final_transcript);
      vm.newMessage = final_transcript;
	  vm.sendMessage();
  };

  recognition.onend = function() { 
    if (ongoing==true){
    	startRecognition();
    } 
  }

  recognition.onerror = function(event) {
   	console.log("There is an error");
  }
}







function startButton(event) {
  if (ongoing==false)  {
	ongoing = true;
	btn_voice_input[0].style.backgroundImage = "url(../images/microphone-active.png)";
	startRecognition();
  }else {
  	ongoing = false;
	btn_voice_input[0].style.backgroundImage = "url(../images/microphone-inactive.png)";
	stopRecognition();
  }
}

function startRecognition() {
	final_transcript = '';
	recognition.start();
}

function stopRecognition() {
	recognition.stop();
}