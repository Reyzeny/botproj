<?php

namespace App\Conversations;

use Illuminate\Foundation\Inspiring;
use BotMan\BotMan\Messages\Incoming\Answer;
use BotMan\BotMan\Messages\Outgoing\Question;
use BotMan\BotMan\Messages\Outgoing\Actions\Button;
use BotMan\BotMan\Messages\Conversations\Conversation;
use App\UserData;
use App\Test;
use Illuminate\Support\Facades\DB;
use App\SimbiReply;
use App\Http\Controllers\BotManController;

class TestCompletionConversation extends Conversation
{

    protected $user_id;
    protected $test_id;
    protected $author_id;

    protected $questions_array;
    protected $options_array;
    protected $answers_array;
    protected $user_selected_answer;
    protected $correct_selections;
    protected $incorrect_selections;


    public function set_user_id($user_id) {
        $this->user_id = $user_id;
    }
    public function set_test_id($test_id) {
        $this->test_id = $test_id;
        UserData::updateOrCreate(["user_id"=>$this->user_id], ["test_id"=>$this->test_id]);
        UserData::updateOrCreate(["user_id"=>$this->user_id], ["test_name"=>Test::find($this->test_id)->title]);
    }
    public function set_author_id($author_id) {
        $this->author_id = $author_id;
        UserData::updateOrCreate(["user_id"=>$this->user_id], ["test_by_author_id"=>$this->author_id]);
    }
    

    public function onTestComplete() {
        UserData::updateOrCreate(["user_id"=>$this->user_id], ["context"=>"test_completed"]);
        $button_array = $this->get_button_type();
        $question = Question::create("What will you like to do now?")
                        ->fallback('Unable to ask question')
                        ->callbackId(4)
                        ->addButtons($button_array);

        SimbiReply::reply($this->bot, $this->user_id, $question); 
    }

    public function test_completion_response($response, $bot) {
        UserData::updateOrCreate(["user_id"=>$this->user_id], ["context"=>"test_completed"]);
        $this->bot = $bot;
        if (preg_match("[try again]", strtolower($response))) {
            $startPaymentConvo = new StartPaymentConversation();
            $startPaymentConvo->set_user_id($this->user_id);
            $startPaymentConvo->set_test_id(DB::table('user_datas')->where('user_id', $this->user_id)->value('test_id'));
            $startPaymentConvo->set_author_id(DB::table('user_datas')->where('user_id', $this->user_id)->value('test_by_author_id'));
            $this->bot->startConversation($startPaymentConvo);
        }
        elseif (preg_match("[another test]", strtolower($response))) {
            $test_name_convo = new TestNameConversation();
            $test_name_convo->set_user_id($this->user_id);
            $this->bot->startConversation($test_name_convo);
        }
        elseif (preg_match("[nothing]", strtolower($response))) {
            SimbiReply::reply($this->bot, $this->user_id, "Alright. Buzz me whenever you are interested again");
        }
        else{
            BotManController::fallback_reply($bot, $this->user_id);
        }
    }

    public function get_button_type() {
        
        $button_array = [
                Button::create('Try again')->value('Try again'),
                Button::create('Take another test')->value('Take another test'),
                Button::create('Nothing')->value('Nothing')
            ];
        return $button_array;
    }
    

    /**
     * Start the conversation
     */
    public function run()
    {
        $this->onTestComplete();
    }
}
