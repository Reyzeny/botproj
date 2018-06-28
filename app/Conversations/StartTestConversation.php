<?php

/*
This class consist of three contexts
    test_Started
    test_on
    test_finished
*/

namespace App\Conversations;

use Illuminate\Foundation\Inspiring;
use BotMan\BotMan\Messages\Incoming\Answer;
use BotMan\BotMan\Messages\Outgoing\Question;
use BotMan\BotMan\Messages\Outgoing\Actions\Button;
use BotMan\BotMan\Messages\Conversations\Conversation;
use Illuminate\Support\Facades\DB;
use App\Test;
use App\UserData;
use App\User;
use App\Author;

class StartTestConversation extends Conversation
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

    private $score=0;

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
    

    /**
     * First question
     */

    public function showTestDetails() {
        UserData::updateOrCreate(["user_id"=>$this->user_id], ["context"=>"test_start"]);
        $take_test = "Take test<br><br>";
        $test_name = "Test name : ".ucwords(Test::find($this->test_id)->title)."<br><br>";
        $author_name = "Author : ".ucwords(Author::find($this->author_id)->author_name)."<br><br>";
        $time = "Time : ".Test::find($this->test_id)->duration." minutes<br><br>";
        $instruction1 = "Type 'quit test' to end this test at any time<br>";
        $instruction2 = "Click the start button or type start to begin the test<br>";

        $display_text = $take_test.$test_name.$author_name.$time.$instruction1.$instruction2;

        $question = Question::create($display_text)
            ->fallback('Unable to ask question')
            ->callbackId('ask_reason')
            ->addButtons([
                Button::create('Start')->value('Start')
        ]);
        $this->say($question);
    }

    public function startTest($bot) {
        UserData::updateOrCreate(["user_id"=>$this->user_id], ["context"=>"test_start"]);
        $this->bot = $bot;
        $this->test_id = DB::table('user_datas')->where('user_id', $this->user_id)->value('test_id');
        $this->author_id = DB::table('user_datas')->where('user_id', $this->user_id)->value('test_by_author_id');
        UserData::updateOrCreate(["user_id"=>$this->user_id], ["context"=>"test_on"]);
        $this->loadQuestions();
        $this->displayQuestion();
    }

    public function loadQuestions() {
        $this->questions_array = $this->get_questions();
        $this->options_array = $this->get_question_options($this->questions_array);
        $this->answers_array = $this->get_question_answers($this->questions_array);
        $this->user_selected_answer = array();
        $this->correct_selections = array();
        $this->incorrect_selections = array();

        UserData::updateOrCreate(["user_id"=>$this->user_id], ["question"=>serialize($this->questions_array)]);
        UserData::updateOrCreate(["user_id"=>$this->user_id], ["options"=>serialize($this->options_array)]);
        UserData::updateOrCreate(["user_id"=>$this->user_id], ["question_answers"=>serialize($this->answers_array)]);
    }

    public function displayQuestion($count=0, $score=0) {

        if ($count < sizeof($this->questions_array)) {
            $option_button_array = $this->get_option_button_array($this->options_array[$count]);
            $question = Question::create($this->questions_array[$count]->question)
                ->fallback('Unable to ask question')
                ->callbackId(4)
                ->addButtons($option_button_array);

            $this->ask($question, function(Answer $answer) use($count, $score){
                if (strtolower($answer)=='quit test') {
                    $this->proceedOnTestFinished($score);
                    return;
                }
                if (!$answer->isInteractiveMessageReply()) {
                    $this->user_selected_answer[$count] = $answer->getText();
                    UserData::updateOrCreate(["user_id"=>$this->user_id], ["user_selected_answers"=>serialize($this->user_selected_answer)]);
                    $score = $this->verifyAnswer($count, $score);
                    $this->say("Ok");
                    $count++;
                    $this->displayQuestion($count, $score);
                    return;    
                }
                $this->say("Don't type the answer, select from the options");
                $this->displayQuestion($count);
                
            }, ['timer_action'=>'start_time']);    
        }
        else {
            $this->proceedOnTestFinished($score);
        }
    }

    public function get_questions() {
        $questions_array = DB::select('select * from questions where test_id=? order by rand() limit 20', [$this->test_id]);
        //var_dump($questions_array);
        return $questions_array;
    }

    public function get_question_options($questions) {
        $options_array = array();
        for($i=0; $i<sizeof($questions); $i++) {
            $option_result = DB::select('select * from options where q_id=?', [$questions[$i]->id]);
            //var_dump($answer_result);
            array_push($options_array, $option_result);
        }
        return $options_array;
    }

    public function get_question_answers($questions) {
        $answer_array = array();
        for($i=0; $i<sizeof($questions); $i++) {
            $answer_result = DB::select('select * from answers where q_id=?', [$questions[$i]->id]);
            //var_dump($answer_result);
            array_push($answer_array, $answer_result);
        }
        return $answer_array;
    }
    public function get_option_button_array($options_array) {
        $array = array();
        for ($i=0; $i<sizeof($options_array); $i++) {
            array_push($array, Button::create($options_array[$i]->option_text)->value($options_array[$i]->option_text)->additionalParameters(["option_id"=>$options_array[$i]->id])->name($options_array[$i]->id));
        }
        return $array;
    }

    public function verifyAnswer($count, $score) {
        if ($this->answers_array[$count][0]->option_text==$this->user_selected_answer[$count]) {
            array_push($this->correct_selections, $count);
            $score++;
        }else{
            array_push($this->incorrect_selections, $count);
        }

        return $score;
    }

    public function proceedOnTestFinished($score) {
        UserData::updateOrCreate(["user_id"=>$this->user_id], ["context"=>"test_finished"]);
        $question = Question::create("Test completed! Your score is ".$score)
                        ->fallback('Unable to ask question')
                        ->callbackId(4)
                        ->addButtons([
                            Button::create('View corrections')->value('View corrections'),
                            Button::create('Proceed')->value('Proceed')
                        ]);

        $this->say($question); 
    }

    public function confirm_test_finished_response($response, $bot){
        $this->bot = $bot;
        if (preg_match("[view corrections]", strtolower($response))) {
            $this->questions_array = unserialize(DB::table('user_datas')->where('user_id', $this->user_id)->value('question'));
            $this->options = unserialize(DB::table('user_datas')->where('user_id', $this->user_id)->value('options'));
            $this->answers_array = unserialize(DB::table('user_datas')->where('user_id', $this->user_id)->value('question_answers'));
            $this->user_selected_answer = unserialize(DB::table('user_datas')->where('user_id', $this->user_id)->value('user_selected_answers'));
            $this->displayCorrectionExplanation();
        }
        elseif (preg_match("[proceed|end]", strtolower($response))) {
            $this->test_id = DB::table('user_datas')->where('user_id', $this->user_id)->value('test_id');
            $this->author_id = DB::table('user_datas')->where('user_id', $this->user_id)->value('test_by_author_id');
            $test_completion_convo = new TestCompletionConversation();
            $test_completion_convo->set_user_id($this->user_id);
            $test_completion_convo->set_test_id($this->test_id);
            $test_completion_convo->set_author_id($this->author_id);
            $this->bot->startConversation($test_completion_convo);
        }
        else{
            $this->bot->reply("Type 'end' to end the session");
        }
    }

    public function displayCorrectionExplanation($pagecount=0) {
        $fragments = sizeof($this->questions_array) / 4;
        $pagecount++; 
        $page_record = $fragments * $pagecount;
        $start = $page_record - $fragments;
        if ($page_record <= sizeof($this->questions_array)) {
            for ($i=$start; $i < $page_record; $i++) {
                $display_text = ($i+1).". ".$this->questions_array[$i]->question;
                $display_text.="<br><br>";
                if (array_key_exists($i, $this->user_selected_answer)) {
                    $display_text.="Your answer : ".$this->user_selected_answer[$i];    
                }else{
                    $this->user_selected_answer[$i] = "";
                    $display_text.="Your answer : ".$this->user_selected_answer[$i];
                }
                $display_text.="<br>";
                $display_text.="Correct answer : ".$this->answers_array[$i][0]->option_text;

                //echo "fragment is $fragments, page count is $pagecount, page record is $page_record<br>";

                $question = Question::create($display_text)
                    ->fallback('Unable to ask question')
                    ->callbackId(4)
                    ->addButtons([
                        Button::create('More')->value('more'),
                        Button::create('End')->value('end')
                    ]);

                $this->ask($question, [
                    [
                        'pattern' => 'more',
                        'callback' => function () use($pagecount) {
                            $this->displayCorrectionExplanation($pagecount);
                        }
                    ],
                    [
                        'pattern' => 'end',
                        'callback' => function () {
                            $this->confirm_test_finished_response("proceed", $this->bot);
                        }
                    ]
                ]);   
            }
            return;
        }
        $this->say('That\'s the end actually');
        $this->confirm_test_finished_response("proceed", $this->bot);
    }

    /**
     * Start the conversation
     */
    public function run()
    {
        $this->showTestDetails();
    }
}
