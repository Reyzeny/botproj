<?php

namespace App\Conversations;

use Illuminate\Foundation\Inspiring;
use BotMan\BotMan\Messages\Incoming\Answer;
use BotMan\BotMan\Messages\Outgoing\Question;
use BotMan\BotMan\Messages\Outgoing\Actions\Button;
use BotMan\BotMan\Messages\Conversations\Conversation;
use App\User;
use App\UserData;
use App\Http\Controllers\BotManController;
use Illuminate\Http\Request;
use App\SimbiReply;

class PersonalInformationConversation extends Conversation
{
    /**
     * First question
     */
    public $user_id;

    public function set_user_id($user_id) {
        $this->user_id = $user_id;
    }
    public function get_user_id() {
        return $this->user_id;
    }


    public function check_if_user_known() {
        $user = new User();
        $user_data = new UserData();

        

        if (!$user->email_exists($this->user_id) && !$user->firstname_exists($this->user_id) && !$user->lastname_exists($this->user_id)) {
            $reply_array = array(
                "Let me get to you know, we could have met before😁"
            );
            $reply = $reply_array[rand(0, sizeof($reply_array)-1)];
            SimbiReply::reply($this->bot, $this->user_id, $reply);
            $this->askForEmail();
            

            // $user_data->user_id = $this->user_id;
            // $user_data->context = 'email';
            // $user_data->save();
        }
        else if (!$user->email_exists($this->user_id)) {
            $reply = "Dear friend, i haven't known you pretty well. Let me know you better";
            SimbiReply::reply($this->bot, $this->user_id, $reply);
            $this->askForEmail();
        }
        else if (!$user->firstname_exists($this->user_id)) {
            $reply = "Dear friend, i haven't known you pretty well. Let me know you better";
            SimbiReply::reply($this->bot, $this->user_id, $reply);
            $this->askForFirstname();
            
        }
        else if (!$user->lastname_exists($this->user_id)) {
            $reply = "Dear friend, i haven't known you pretty well. Let me know you better";
            SimbiReply::reply($this->bot, $this->user_id, $reply);
            $this->askForLastname();
            UserData::updateOrCreate(["user_id"=>$this->user_id], ["context"=>"lastname"]);
        }
        else {
            $test_name_convo = new TestNameConversation();
            $test_name_convo->set_user_id($this->user_id);
            UserData::updateOrCreate(["user_id"=>$this->user_id], ["context"=>"test_name"]);
            $this->bot->startConversation($test_name_convo);
        }

    }





/* ----------------------------------Relating to Email - Begin ----------------------------------------------- */

    public function askForEmail() {
        UserData::updateOrCreate(["user_id"=>$this->user_id], ["context"=>"email"]);
        $user = new User();
        if (!$user->email_exists($this->user_id)) {
            $this->request_mail();
            return;
        }
        $this->askForFirstname();
    }

    public function request_mail() {
        UserData::updateOrCreate(["user_id"=>$this->user_id], ["context"=>"email"]);
        $question = $this->get_email_question();
        SimbiReply::ask($this, $this->user_id, $question, function(Answer $answer) {
                $this->confirm_email($answer, $this->bot);
        });

         //SimbiReply::reply($this->bot, $this->user_id, $question);
    }

    public function confirm_email($email, $bot) {
        UserData::updateOrCreate(["user_id"=>$this->user_id], ["context"=>"email"]);
        $this->bot = $bot;
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            User::updateOrCreate(["user_id"=>$this->user_id], ["email"=>$email]);
            SimbiReply::reply($this->bot, $this->user_id, 'Okay');
            $this->askForFirstname();
            return true;
        }
        
        BotManController::fallback_reply($this->bot, $this->user_id);
    }

    public function get_email_question() {
        $question_array = array(
            "Kindly enter your email address",
            "What's your email address?"
        );
        $question_text = $question_array[rand(0, sizeof($question_array)-1)];
        $question = Question::create($question_text)
        ->fallback('Unable to create a new database')
        ->callbackId('create_database');
        return $question;
    }



/* --------------------------------------Relating to Email - End ---------------------------------------------------------*/






/* ----------------------------------Relating to Name - Begin -------------------------------------------------------- */

    public function askForFirstname() {
        UserData::updateOrCreate(["user_id"=>$this->user_id], ["context"=>"firstname"]);
        $user = new User();
        if (!$user->firstname_exists($this->user_id)) {
            $question = $this->get_firstname_question();
            SimbiReply::ask($this, $this->user_id, $question, function(Answer $answer) {
                    $this->confirm_firstname($answer, $this->bot);
            });
            //SimbiReply::reply($this->bot, $this->user_id, $question);
            return;
        }
        $this->askForLastname();
    }
    public function confirm_firstname($name, $bot) {
         UserData::updateOrCreate(["user_id"=>$this->user_id], ["context"=>"firstname"]);
        $this->bot = $bot;
        User::updateOrCreate(["user_id"=>$this->user_id], ["firstname"=>$name]);
        SimbiReply::reply($this->bot, $this->user_id, 'Cool');
        $this->askForLastname();
    }


    public function askForLastname() {
        UserData::updateOrCreate(["user_id"=>$this->user_id], ["context"=>"lastname"]);
        $user = new User();
        if (!$user->lastname_exists($this->user_id)) {
            $question = $this->get_lastname_question();
            SimbiReply::ask($this, $this->user_id, $question, function(Answer $answer) {
                    $this->confirm_lastname($answer, $this->bot);
            });
            SimbiReply::reply($this->bot, $this->user_id, $question);
            return;
        }
        $test_name_convo = new TestNameConversation();
        $test_name_convo->set_user_id($this->user_id);
        $this->bot->startConversation($test_name_convo);
    }
    public function confirm_lastname($name, $bot) {
        UserData::updateOrCreate(["user_id"=>$this->user_id], ["context"=>"lastname"]);
        $this->bot = $bot;
        User::updateOrCreate(["user_id"=>$this->user_id], ["lastname"=>$name]);
        SimbiReply::reply($this->bot, $this->user_id, 'Noted');
        $test_name_convo = new TestNameConversation();
        $test_name_convo->set_user_id($this->user_id);
        $this->bot->startConversation($test_name_convo);
    }



    public function get_firstname_question() {
        $question_array = array(
            "What is your first name?",
            "Tell me your first name?"
        );
        $question_text = $question_array[rand(0, sizeof($question_array)-1)];
        $question = Question::create($question_text)
        ->fallback('Unable to create a new database')
        ->callbackId('create_database');
        return $question;
    }

    public function get_lastname_question() {
        $question_array = array(
            "What is your last name?",
            "Tell me your last name?"
        );
        $question_text = $question_array[rand(0, sizeof($question_array)-1)];
        $question = Question::create($question_text)
        ->fallback('Unable to create a new database')
        ->callbackId('create_database');
        return $question;
    }

/* ----------------------------------Relating to Name - End ----------------------------------------------------------- */

    
    
    

    // public function fallback_reply(BotMan $bot, $user_id) {
    //     $user_data = new UserData();
    //     $result = DB::select('select context from user_datas where user_id=?', [$user_id]);
    //     $context = $result[0]->context;
    //     if ($context=='email') {
    //         $reply = $this->get_random_email_fallback_reply();
    //         $bot->reply($reply);
    //     }
        
        
    // }

    // public function get_random_email_fallback_reply() {
    //     $reply_array = array(
    //         "Email is not valid",
    //         "Enter a valid email address "
    //     );
    //     $reply = $reply_array[rand(0, sizeof($reply_array)-1)];
    //     return $reply;
    // }


    // public function get_random_email_fallback_reply() {
    //     $reply_array = array(
    //         "Email is not valid",
    //         "Enter a valid email address "
    //     );
    //     $reply = $reply_array[rand(0, sizeof($reply_array)-1)];
    //     return $reply;
    // }
    

    /**
     * Start the conversation
     */
    public function run()
    {
        $this->check_if_user_known();
    }


}
