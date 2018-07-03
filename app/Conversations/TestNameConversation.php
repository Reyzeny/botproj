<?php

namespace App\Conversations;

use Illuminate\Foundation\Inspiring;
use BotMan\BotMan\Messages\Incoming\Answer;
use BotMan\BotMan\Messages\Outgoing\Question;
use BotMan\BotMan\Messages\Outgoing\Actions\Button;
use BotMan\BotMan\Messages\Conversations\Conversation;
use Illuminate\Support\Facades\DB;
use App\Test;
use App\UserData;
use App\Http\Controllers\BotManController;

class TestNameConversation extends Conversation
{
    private $user_id;

    public function set_user_id($user_id) {
        $this->user_id = $user_id;
    }
    public function get_user_id() {
        return $this->user_id;
    }
    /**
     * First question
     */

    public function askTestName() {
        UserData::updateOrCreate(["user_id"=>$this->user_id], ["context"=>"test_name"]);
        $question = $this->get_what_test();
        $this->say($question);
    }
    public function confirm_testname($test_title, $bot) {
        UserData::updateOrCreate(["user_id"=>$this->user_id], ["context"=>"test_name"]);
        $this->bot = $bot;
        $test = $this->get_available_test();
        if (preg_match($test, $test_title)) {
            $this->say('Alright');
            $test_selection = new TestSelectionConversation();
            $test_selection->set_user_id($this->user_id);
            $test_selection->set_test_entered($test_title);
            UserData::updateOrCreate(["user_id"=>$this->user_id], ["context"=>"test_selection"]);
            $this->bot->startConversation($test_selection);
            return;
        }
        // if (!$suggestion=get_closer_keyword($test_title)) {

        // }
        BotManController::fallback_reply($bot, $this->user_id);
    }
    public function confirm_authorname($author_name, $bot) {
        UserData::updateOrCreate(["user_id"=>$this->user_id], ["context"=>"test_name"]);
        $this->bot = $bot;
        $author_id_array = array();
        $author_list = Author::all();
        foreach ($author_list as $author) {
            if ( strpos($author_list->author_name, $author_name) || strpos($author_name, $author_list->author_name) ) {
                array_push($author_id_array, $author_list->id);
            }
        }
        // if (!$suggestion=get_closer_keyword($test_title)) {

        // }
        if (empty($author_id_array)) {
            BotManController::fallback_reply($bot, $this->user_id); 
            return;   
        }

        $test_selection = new TestSelectionConversation();
        $test_selection->set_user_id($this->user_id);
        $test_selection->set_author_id_entered($author_id_array);
        $test_selection->showAuthorSuggestion();
    }

    public function get_what_test() {
        $test_list = Test::select('title')->distinct()->get();
        //$test_list = Test::all();
        $test_button_array = array();
        foreach ($test_list as $test) {
            $mybutton = Button::create($test->title)->value($test->title);
            array_push($test_button_array, $mybutton);
        }
        $question_array = array(
            "What test would you like to take today?",
            "What would you like to prepare on?"
        );
        $question_text = $question_array[rand(0, sizeof($question_array)-1)];


        $question = Question::create($question_text)
        ->fallback('Unable to create a new database')
        ->callbackId('create_database')
        ->addButtons($test_button_array);

        
        return $question;
    }

    public function get_available_test() {
        $text = "[";
        $result = DB::select('select distinct title from tests');
        for ($i=0; $i<sizeof($result); $i++) {
            if ($i==sizeof($result)-1) {
                $text.=strtolower($result[$i]->title);
                break;
            }
            $text = $text.strtolower($result[$i]->title)."|";
        }
        $text.="]";
        return $text;
    }

    public function show_all_test_list($bot) {
        UserData::updateOrCreate(["user_id"=>$this->user_id], ["context"=>"test_name"]);
        $this->bot = $bot;
        $test_list = Test::select('title')->distinct()->get();

        $response = "I have <br><br>";
        foreach ($test_list as $test) {
            $response .= $test->title."<br>";
        }
        $response .= "<br>Which would you like to take?";
        $this->bot->reply($response);
    }

    /**
     * Start the conversation
     */
    public function run()
    {
        $this->askTestName();
    }
}
