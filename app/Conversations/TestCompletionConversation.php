<?php

namespace App\Conversations;

use Illuminate\Foundation\Inspiring;
use BotMan\BotMan\Messages\Incoming\Answer;
use BotMan\BotMan\Messages\Outgoing\Question;
use BotMan\BotMan\Messages\Outgoing\Actions\Button;
use BotMan\BotMan\Messages\Conversations\Conversation;

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


    public function __construct($user_id, $test_id, $author_id) {
        $this->user_id = $user_id;
        $this->test_id = $test_id;
        $this->author_id = $author_id;
    }
    

    public function onTestComplete() {
        $button_array = $this->get_button_type();
        $question = Question::create("What will you like to do now?")
                        ->fallback('Unable to ask question')
                        ->callbackId(4)
                        ->addButtons($button_array);

        $this->ask($question, [
                [
                    'pattern' => 'Try again',
                    'callback' => function () {
                        $this->bot->startConversation(new StartPaymentConversation($this->user_id, $this->test_id, $this->author_id));
                    }
                ],
                [
                    'pattern' => 'Pick another subject',
                    'callback' => function () {
                        $this->bot->startConversation(new TestCompletionConversation());
                    }
                ],
                [
                    'pattern' => 'Pick another school',
                    'callback' => function () {
                        $this->bot->startConversation(new TestCompletionConversation());
                    }
                ]
            ]); 
    }

    public function get_button_type($exam_type="post_utme") {
        if ($exam_type=='post_utme') {
            $button_array = [
                Button::create('Try again')->value('Try again'),
                Button::create('Pick another subject')->value('Pick another subject'),
                Button::create('Pick another school')->value('Pick another school'),
                Button::create('Nothing')->value('Nothing')
            ];
            return $button_array;
        }

        $button_array = [
                Button::create('Try again')->value('Try again'),
                Button::create('Pick another subject')->value('Pick another subject')
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
