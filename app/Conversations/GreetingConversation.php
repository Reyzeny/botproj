<?php

namespace App\Conversations;

use Illuminate\Foundation\Inspiring;
use BotMan\BotMan\Messages\Incoming\Answer;
use BotMan\BotMan\Messages\Outgoing\Question;
use BotMan\BotMan\Messages\Outgoing\Actions\Button;
use BotMan\BotMan\Messages\Conversations\Conversation;
use App\Http\Controllers\BotManController;
use App\User;
use App\UserData;
use App\SimbiReply;

class GreetingConversation extends Conversation
{
    public $user_id;
    public $user;

    public function __construct($user_id) {
        $user = new User();
        $this->user = $user;
        $this->user_id = $user_id;
    }

    public function greetUser() {
        UserData::updateOrCreate(["user_id"=>$this->user_id], ["context"=>"greeting"]);
        $name = $this->user->get_name($this->user_id);
        $greeting_array = array(
            "Hello ","Hey ", "What's up ", "Welcome ", "Hi ", "Good day ", "Howdy ", "Holla ", "Xup Xup ", "Sup "
        );
        $greeting = $greeting_array[rand(0, sizeof($greeting_array)-1)];
        // $this->bot->typesAndWaits(2);
        SimbiReply::reply($this->bot, $this->user_id, "{$greeting} {$name}");
        $pic = new PersonalInformationConversation();
        $pic->set_user_id($this->user_id);
        $this->bot->startConversation($pic);
    }

    /**
     * Start the conversation
     */
    public function run()
    {
        $this->greetUser();
    }
}
