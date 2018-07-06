<?php

namespace App\Conversations;

use Illuminate\Foundation\Inspiring;
use BotMan\BotMan\Messages\Incoming\Answer;
use BotMan\BotMan\Messages\Outgoing\Question;
use BotMan\BotMan\Messages\Outgoing\Actions\Button;
use BotMan\BotMan\Messages\Conversations\Conversation;
use App\Test;
use App\UserData;
use App\User;
use App\Transaction;
use Illuminate\Support\Facades\DB;
use App\SimbiReply;
use App\Http\Controllers\BotManController;

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
        if ($this->test_is_paid() && !$this->transaction_available($this->user_id)) {
            //var_dump(Test::find($this->test_id));
            $test_cost = DB::table('tests')->where('id', $this->test_id)->value('amount');
            $question_array = array(
                'This test is a paid test and it costs '.$test_cost.', would you like continue with payment?',
                'It costs '.$test_cost.' to take this test, would you like to proceed with payment?'
            );
            $question_text = $question_array[rand(0, sizeof($question_array)-1)];
            $question = Question::create($question_text)
            ->fallback('Unable to create a new database')
            ->callbackId('create_database')
            ->addButtons([Button::create('Yes')->value('Yes'), Button::create('No')->value('No')]);
            SimbiReply::reply($this->bot, $this->user_id, $question);
            return;
        }

        
        $start_test_convo = new StartTestConversation();
        $start_test_convo->set_user_id($this->user_id);
        $start_test_convo->set_test_id($this->test_id);
        $start_test_convo->set_author_id($this->author_id);
        $this->bot->startConversation($start_test_convo);
    }

    public function confirm_payment($user_id, $bot, $response) {
        $this->bot = $bot;
        $this->test_id = DB::table('user_datas')->where('user_id', $user_id)->value('test_id');
        $this->author_id = DB::table('user_datas')->where('user_id', $user_id)->value('test_by_author_id');
        if (preg_match("[yes|yea|yep]", strtolower($response))) {
            $id = DB::table('transactions')->insertGetId([ 'email'=>DB::table('users')->where('user_id', $user_id)->value('email'), 'user_id' => $this->user_id, 'test_id' => $this->test_id, 'author_id'=>$this->author_id]);

            $payment_option_convo = new PaymentOptionConversation();
            $payment_option_convo->set_user_id($user_id);
            $bot->startConversation($payment_option_convo);
            return;
        }
        if (preg_match("[no|nop|nah]", strtolower($response))) {
            $test_name_convo = new TestNameConversation();
            $test_name_convo->set_user_id($this->user_id);
            $this->bot->startConversation($test_name_convo);
            return;
        }
        BotManController::fallback_reply($bot, $this->user_id);
    }

    public function test_is_paid() {
        return Test::find($this->test_id)->first()->paid == 'yes';
    }

    public function transaction_available($user_id) {
        $this->user_id = $user_id;
        $this->test_id = UserData::where('user_id', $this->user_id)->value('test_id');
        $this->author_id = UserData::where('user_id', $this->user_id)->value('test_by_author_id');
        $status = DB::table('transactions')->where(["user_id"=>$this->user_id, "test_id"=>$this->test_id, "author_id"=>$this->author_id])->orderBy('id', 'desc')->value('status');
        return $status!=null && !empty($status) && $status=='processed';
    }

    /**
     * Start the conversation
     */
    public function run()
    {
        $this->check_for_payment();
    }
}
