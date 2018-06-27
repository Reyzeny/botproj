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
    }
    public function set_author_id($author_id) {
        $this->author_id = $author_id;
    }
    

    /**
     * First question
     */

    public function showTestDetails() {
        $take_test = "Take test<br><br>";
        $test_name = "Test name : Unilag Maths<br><br>";
        $author_name = "Author : Pelumi<br><br>";
        $time = "Time : 20mins<br><br>";

        $display_text = $take_test.$test_name.$author_name.$time;

        $question = Question::create($display_text)
            ->fallback('Unable to ask question')
            ->callbackId('ask_reason')
            ->addButtons([
                Button::create('Start')->value('Start')
        ]);

        return $this->ask($question, function (Answer $answer) {
            if (strtolower($answer->getText())=='start') {
                $this->loadQuestions();
                $this->displayQuestion();
            }
        });
    }

    public function loadQuestions() {
        $this->questions_array = $this->get_questions();
        $this->options_array = $this->get_question_options($this->questions_array);
        $this->answers_array = $this->get_question_answers($this->questions_array);
        $this->user_selected_answer = array();
        $this->correct_selections = array();
        $this->incorrect_selections = array();
    }

    public function displayQuestion($count=0, $score=0) {
        //var_dump($options_array);
        // while (self::$count <= 20) {
        //     $option_button_array = $this->get_option_button_array($options_array[self::$count]);
        //     $question = Question::create($questions_array[self::$count]->question)
        //         ->fallback('Unable to ask question')
        //         ->callbackId('ask_reason')
        //         ->addButtons($option_button_array);

        //     $this->ask('Hello! What is your firstname?', function(Answer $answer) {
        //         // Save result
        //         $this->firstname = $answer->getText();

        //         $this->say('Nice to meet you '.$this->firstname);
        //         
        //     });    
        //     self::$count++;
        // }
        //var_dump($this->questions_array);

        if ($count < sizeof($this->questions_array)) {
            $option_button_array = $this->get_option_button_array($this->options_array[$count]);
            $question = Question::create($this->questions_array[$count]->question)
                ->fallback('Unable to ask question')
                ->callbackId(4)
                ->addButtons($option_button_array);

            $this->ask($question, function(Answer $answer) use($count, $score){
                if (!$answer->isInteractiveMessageReply()) {
                    $this->user_selected_answer[$count] = $answer->getText();
                    $score = $this->verifyAnswer($count, $score);
                    $this->say("Ok");
                    $count++;
                    $this->displayQuestion($count, $score);
                    return;    
                }
                $this->say("Don't type the answer, select from the options");
                $this->displayQuestion($count);
                
            });    
        }
        else {
            $this->proceedOnTestCompletion($score);
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
        //var_dump($this->answers_array[$count][0]->option_text);
        //var_dump($this->user_selected_answer[$count]);
        if ($this->answers_array[$count][0]->option_text==$this->user_selected_answer[$count]) {
            array_push($this->correct_selections, $count);
            $score++;
        }else{
            array_push($this->incorrect_selections, $count);
        }

        return $score;
    }

    public function proceedOnTestCompletion($score) {
        $question = Question::create("Test completed! Your score is ".$score)
                        ->fallback('Unable to ask question')
                        ->callbackId(4)
                        ->addButtons([
                            Button::create('View corrections')->value('View corrections'),
                            Button::create('Proceed')->value('Proceed')
                        ]);

        $this->ask($question, [
                [
                    'pattern' => 'View corrections|correction',
                    'callback' => function () {
                        $this->displayCorrectionExplanation();
                    }
                ],
                [
                    'pattern' => 'Proceed',
                    'callback' => function () {
                        $this->bot->startConversation(new TestCompletionConversation($this->user_id, $this->test_id, $this->author_id));
                    }
                ]
            ]); 
    }

    public function displayCorrectionExplanation($pagecount=0) {
        $fragments = sizeof($this->questions_array) / 4;
        $pagecount++; 
        $page_record = $fragments * $pagecount;
        $start = $page_record - $fragments;
        if ($page_record < sizeof($this->questions_array)) {
            for ($i=$start; $i < $page_record; $i++) {
                $display_text = ($i+1).". ".$this->questions_array[$i]->question;
                $display_text.="<br><br>";
                $display_text.="Your answer : ".$this->user_selected_answer[$i];
                $display_text.="<br>";
                $display_text.="Correct answer : ".$this->answers_array[$i][0]->option_text;

                //echo "fragment is $fragments, page count is $pagecount, page record is $page_record<br>";

                $question = Question::create($display_text)
                    ->fallback('Unable to ask question')
                    ->callbackId(4)
                    ->addButtons([
                        Button::create('More')->value('More'),
                        Button::create('End')->value('End')
                    ]);

                $this->ask($question, [
                    [
                        'pattern' => 'More',
                        'callback' => function () use($pagecount) {
                            $this->displayCorrectionExplanation($pagecount);
                        }
                    ],
                    [
                        'pattern' => 'End',
                        'callback' => function () {
                            $this->bot->startConversation(new TestCompletionConversation($this->user_id, $this->test_id, $this->author_id));
                        }
                    ]
                ]);   
            }
            return;
        }
        $this->say('That\'s the end actually');
        $this->bot->startConversation(new TestCompletionConversation($this->user_id, $this->test_id, $this->author_id));
    }

    /**
     * Start the conversation
     */
    public function run()
    {
        $this->showTestDetails();
    }
}
