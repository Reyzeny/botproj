<?php
namespace App;

use App\UserConversation;


class SimbiReply 
{

	public static function reply($bot, $user_id, $reply, $additionalParameters=[]) {
		$last_convo_id = UserConversation::where('user_id', $user_id)->latest()->value('id');
		//echo gettype($reply);
		
		//echo gettype($reply);

		if (gettype($reply)== "string"){
			$simbi_text = $reply;
			
		}else{
			
			$simbi_text = $reply->getText();
		}
		UserConversation::updateOrCreate(["id"=>$last_convo_id], ["simbi_response"=>$simbi_text]);
		$bot->reply($reply, $additionalParameters);
	}

	public static function ask($bot_class, $user_id, $question, $next, $additionalParameters = [], $recipient = null, $driver = null) {
		$last_convo_id = UserConversation::where('user_id', $user_id)->latest()->value('id');
		UserConversation::updateOrCreate(["id"=>$last_convo_id], ["simbi_response"=>$question->getText()]);
		$bot_class->ask($question, $next, $additionalParameters, $recipient, $driver);
	}
}