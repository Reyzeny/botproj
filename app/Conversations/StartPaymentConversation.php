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
    protected $user_id;
    protected $test_id;
    protected $author_id;

    public function set_user_id($user_id) {
        $this->user_id = $user_id;
    }
    public function set_test_id($test_id) {
        $this->test_id = $test_id;
    }
    public function set_author_id($author_id) {
        $this->author_id = $author_id;
    }
    public function get_user_id() {
        return $this->user_id;
    }
    public function get_test_id() {
        return $this->test_id;
    }
    public function get_author_id() {
        return $this->author_id;
    }
    
    

    public function check_for_payment() {
        echo "test id is ".$this->test_id;
        echo "user id is ".$this->user_id;
        echo "author_id is ".$this->author_id;
        if (!$this->test_is_paid()) {
            $start_test_convo = new StartTestConversation();
            $start_test_convo->set_user_id($this->user_id);
            $start_test_convo->set_test_id($this->test_id);
            $start_test_convo->set_author_id($this->author_id);
            UserData::updateOrCreate(["user_id"=>$this->user_id], ["context"=>"test_started"]);
            $this->bot->startConversation($start_test_convo);
            return;
        }
        if ($this->test_is_paid() && $user->has_paid($this->user_id)) {
            
        }
        
    }

    public function test_is_paid() {
        echo "test id is ".$this->test_id;
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
