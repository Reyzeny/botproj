<?php

namespace App\Conversations;

use Illuminate\Foundation\Inspiring;
use BotMan\BotMan\Messages\Incoming\Answer;
use BotMan\BotMan\Messages\Outgoing\Question;
use BotMan\BotMan\Messages\Outgoing\Actions\Button;
use BotMan\BotMan\Messages\Conversations\Conversation;
use App\Test;
use App\UserData;

class StartPaymentConversation extends Conversation
{
    private $user_id;
    private $test_id;
    private $author_id;

    public function set_user_id($user_id) {
        $this->user_id = $user_id;
    }
    public function set_test_id($test_id) {
        $this->test_id = $test_id;
    }
    public function set_author_id($author_id) {
        $this->author_id = $author_id;
    }
    
    

    public function check_for_payment() {
        UserData::updateOrCreate(["user_id"=>$this->user_id], ["context"=>"payment"]);
        if (!$this->test_is_paid()) {
            $start_test_convo = new StartTestConversation();
            $start_test_convo->set_user_id($this->user_id);
            $start_test_convo->set_test_id($this->test_id);
            $start_test_convo->set_author_id($this->author_id);
            UserData::updateOrCreate(["user_id"=>$this->user_id], ["context"=>"test_start"]);
            $this->bot->startConversation($start_test_convo);
            return;
        }
        if ($this->test_is_paid() && $user->has_paid($this->user_id)) {
            $this->bot->reply('This test is a paid test, would you like continue with payment?');
        }
        
    }

    public function test_is_paid() {
        return Test::find($this->test_id)->paid == 'yes';
    }

    /**
     * Start the conversation
     */
    public function run()
    {
        $this->check_for_payment();
    }
}
