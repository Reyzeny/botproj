var final_transcript;
	var recognition;
	var ongoing = false;
	if (!('webkitSpeechRecognition' in window)) {
	  
	  console.log('speech not supported');
	} else {
	  recognition = new webkitSpeechRecognition();
	  recognition.continuous = false;
	  recognition.interimResults = true;

	  recognition.onstart = function() {

	  };


	  recognition.onresult = function(event) {
	      //console.log("event is " + JSON.stringify(event));
	      var interim_transcript = '';

	      for (var i = event.resultIndex; i < event.results.length; ++i) {
	        if (event.results[i].isFinal) {
	          final_transcript += event.results[i][0].transcript;
	        } else {
	          interim_transcript += event.results[i][0].transcript;
	        }
	      }
	      //final_transcript = capitalize(final_transcript);
	      console.log("at the end, final transcript is " + final_transcript);

	      //startButton(event);

	      //final_span.innerHTML = linebreak(final_transcript);
	      //interim_span.innerHTML = linebreak(interim_transcript);
	  };






	  recognition.onend = function() { 
	    ongoing = false;
	    startButton(event);
	  }
	}

	startButton(event);
