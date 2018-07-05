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
use App\Question as QuestionTable;
use App\SimbiReply;
use App\Http\Controllers\BotManController;

class TestSelectionConversation extends Conversation
{
    protected $test_entered;
    protected $author_id_entered;
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
        SimbiReply::reply($this->bot, $this->user_id, $question);
    }


    public function confirm_suggestion_selection($selection, $bot) {
        $this->bot = $bot;
        UserData::updateOrCreate(["user_id"=>$this->user_id], ["context"=>"test_selection"]);
        $data = $this->analyze_text($selection);
        if (!isset($data['test_id'])) {
            BotManController::fallback_reply($bot, $this->user_id);
            return;
        }
        $question_count = QuestionTable::where(['test_id' => $data['test_id']])->count();
        if ($question_count < 20) {
            $reply_array = array(
                "Sorry, there are no sufficient questions for this test yet. Try again later"
            );
            $reply = $reply_array[rand(0, sizeof($reply_array)-1)];
            SimbiReply::reply($this->bot, $this->user_id, $reply);
            UserData::updateOrCreate(["user_id"=>$this->user_id], ["context"=>"test_name"]);
            return;
        }

        $startPaymentConvo = new StartPaymentConversation();
        $startPaymentConvo->set_user_id($this->user_id);
        $startPaymentConvo->set_test_id($data['test_id']);
        $startPaymentConvo->set_author_id($data['author_id']);
        UserData::updateOrCreate(["user_id"=>$this->user_id], ["context"=>"payment"]);
        $bot->startConversation($startPaymentConvo);
    }

    public function confirm_full_text_entry($test_name, $author_name, $bot) {
        $this->bot = $bot;
        $data = $this->analyze_text($test_name. " by ".$author_name);
        if (!empty($data)) {
            $this->confirm_suggestion_selection(strtolower($test_name). " by ".strtolower($author_name), $bot);
            return;
        }
        $test_array = array();
        $test_name_particle = explode(" ", $test_name);
        $author_name_particle = explode(" ", $author_name);
        //echo "test name particle is "; print_r($test_name_particle)."<br>";
        //echo "author name particle is "; print_r($author_name_particle);
        
        for($i=0; $i < sizeof($test_name_particle); $i++) {
            $arr = array($test_name_particle[0], $test_name_particle[$i]);
            $combine = implode(" ", $arr);
            //echo "<br> combine is $combine";
            $result = DB::select("select * from tests where title like '%$combine%'");
            //echo "<br> result is ".var_dump($result);
            foreach ($result as $test) {
                if ($this->author_name_present_in_array($author_name_particle, $test->author_id)) {
                    array_push($test_array, $test);    
                }
            }
        }

        if (!empty($test_array)) {
            UserData::updateOrCreate(["user_id"=>$this->user_id], ["context"=>"test_selection"]);
            $question = $this->get_selection_question($test_array);
            SimbiReply::reply($this->bot, $this->user_id, $question);
            return;
        }
        UserData::updateOrCreate(["user_id"=>$this->user_id], ["context"=>"test_name"]);
        BotManController::fallback_reply($bot, $this->user_id);
    }

    public function author_name_present_in_array($author_name_particle, $author_id) {
        //$author_id = Test::find($test_id)->test_by_author_id; 
        foreach ($author_name_particle as $name) {
            //echo "<br> from db, name is ".strtolower(Author::find($author_id)->author_name);
            //echo "<br> name is $name and author id is $author_id";
            if (!empty(trim($name)) && strpos(strtolower(Author::find($author_id)->author_name), strtolower($name))!==false) {
                //echo "<br> returning true";
                return true;
            }
        }
        //echo "<br> returning false";
        return false;
    }

    public function analyze_text($answer) {
        $result = Test::all();
        $data = array();
        foreach ($result as $test) {
            if (strpos(strtolower($answer), strtolower($test->title))!==false && 
                strpos(strtolower($answer), strtolower(Author::find($test->author_id)->author_name))!==false) {
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
            "Try out these below"
        );
        $question_text = $question_array[rand(0, sizeof($question_array)-1)];

        //var_dump($test_button_array);
        $question = Question::create($question_text)
        ->fallback('Unable to create a new database')
        ->callbackId('create_database')
        ->addButtons($test_button_array);

        
        return $question;
    }

    public function get_selection_question2() {

        //dd($test_list);
        $author_id_entered = $this->author_id_entered;
        $test_button_array = array();

        foreach ($author_id_entered as $id) {
            $result = DB::select("select * from tests where test_by_author_id like '$author_id_entered'");
            foreach ($result as $test) {

                $button_text = $test->title." test by ".Author::find($test->author_id)->author_name;
                $mybutton = Button::create(ucwords($button_text))->value(ucwords($button_text));
                array_push($test_button_array, $mybutton);
            }
            
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
