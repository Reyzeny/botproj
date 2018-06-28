<?php

namespace App\Conversations;

use Illuminate\Foundation\Inspiring;
use BotMan\BotMan\Messages\Incoming\Answer;
use BotMan\BotMan\Messages\Outgoing\Question;
use BotMan\BotMan\Messages\Outgoing\Actions\Button;
use BotMan\BotMan\Messages\Conversations\Conversation;
use Illuminate\Support\Facades\DB;
use App\Author;
use App\Test;
use App\UserData;

class TestSelectionConversation extends Conversation
{
    protected $test_entered;
    protected $user_id;

    public function set_test_entered($test_entered) {
        $this->test_entered = $test_entered;
    }
    public function set_user_id($user_id){
        $this->user_id = $user_id;
    }
    /**
     * First question
     */
    public function showSuggestion() {
        UserData::updateOrCreate(["user_id"=>$this->user_id], ["context"=>"test_selection"]);
        $test_entered = $this->test_entered;
        $result = DB::select("select * from tests where title like '%$test_entered%'");
        $question = $this->get_selection_question($result);
        $this->say($question);
    }

    public function confirm_suggestion_selection($selection, $bot) {
        UserData::updateOrCreate(["user_id"=>$this->user_id], ["context"=>"test_selection"]);
        $data = $this->analyze_text($selection);
        $startPaymentConvo = new StartPaymentConversation();
        $startPaymentConvo->set_user_id($this->user_id);
        $startPaymentConvo->set_test_id($data['test_id']);
        $startPaymentConvo->set_author_id($data['author_id']);
        UserData::updateOrCreate(["user_id"=>$this->user_id], ["context"=>"payment"]);
        $bot->startConversation($startPaymentConvo);
    }

    public function analyze_text($answer) {
        $result = Test::all();
        $data = array();
        foreach ($result as $test) {
            if (strpos(strtolower($answer), strtolower($test->title))!==false && 
                strpos(strtolower($answer), strtolower(Author::find($test->author_id)->author_name))) {
                $data['test_id'] = $test->id;
                $data['author_id'] = $test->author_id;
                break;
            }
        }
        return $data;
    }


    public function get_selection_question($data) {

        //dd($test_list);
        $test_button_array = array();
        foreach ($data as $test) {

            $button_text = $test->title." test by ".Author::find($test->author_id)->author_name;
            $mybutton = Button::create(ucwords($button_text))->value(ucwords($button_text));
            array_push($test_button_array, $mybutton);
        }
        
        $question_array = array(
            "I have",
            "Below is the following i have"
        );
        $question_text = $question_array[rand(0, sizeof($question_array)-1)];

        //var_dump($test_button_array);
        $question = Question::create($question_text)
        ->fallback('Unable to create a new database')
        ->callbackId('create_database')
        ->addButtons($test_button_array);

        
        return $question;
    }
    

    /**
     * Start the conversation
     */
    public function run()
    {
        $this->showSuggestion();
    }
}
