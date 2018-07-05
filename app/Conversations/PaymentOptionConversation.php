<?php

namespace App\Conversations;

use Illuminate\Foundation\Inspiring;
use BotMan\BotMan\Messages\Incoming\Answer;
use BotMan\BotMan\Messages\Outgoing\Question;
use BotMan\BotMan\Messages\Outgoing\Actions\Button;
use BotMan\BotMan\Messages\Conversations\Conversation;
use App\UserData;
use App\User;
use App\Transaction;
use Illuminate\Support\Facades\DB;
use App\SimbiReply;
use App\Http\Controllers\BotManController;

class PaymentOptionConversation extends Conversation
{
    /**
     * First question
     */
    protected $user_id;
    protected $transaction_id;

    public function set_user_id($user_id) {
        $this->user_id = $user_id;
    }
    public function set_transaction_id($trans_id) {
        $this->transaction_id = $trans_id;
    }


    

    public function askPaymentOption() {
        UserData::updateOrCreate(["user_id"=>$this->user_id], ["context"=>"payment_option"]);
        $question_array = array(
            "Would you like to Pay online or By Bank Transfer"
        );
        $question_text = $question_array[rand(0, sizeof($question_array)-1)];
        $question = Question::create($question_text)
        ->fallback('Unable to create a new database')
        ->callbackId('create_database')
        ->addButtons([Button::create('Pay Online')->value('Pay Online'), Button::create('Bank Transfer')->value('Bank Transfer')]);
        SimbiReply::reply($this->bot, $this->user_id, $question);
    }

    public function confirm_payment_option($user_id, $bot, $response) {
        $this->bot = $bot;
        $this->transaction_id = Transaction::select('id')->where('user_id', $user_id)->orderBy('id', 'desc')->value('id');
        $user_email = DB::table('users')->where('user_id', $user_id)->value('email');
        $test_id = DB::table('user_datas')->where('user_id', $user_id)->value('test_id');
        $amount = DB::table('tests')->where('id', $test_id)->value('amount');
        if (preg_match("[online|atm card]", strtolower($response))) {
            Transaction::updateOrCreate(["id"=>$this->transaction_id], ["payment_method"=>"atm_card"]);
            SimbiReply::reply($this->bot, $this->user_id, 'ok, Please wait...', ['payment_action'=>'show_payment', 'transaction_id'=>$this->transaction_id, 'user_email'=>$user_email, 'amount'=>$amount]);
        }
        elseif (preg_match("[transfer]", strtolower($response))) {
            $reply_array = array(
                "Okay, kindly make a transfer to the following account details and contact one of my team members on 09095059116 to activate your payment.<br><br>

                Account name   : Brimatel Global Networks<br>
                Account number : 0117562221<br>
                Bank            : GTB<br>

                Transaction id :  ".$this->transaction_id;
            );
            $reply = $reply_array[rand(0, sizeof($reply_array)-1)];
            SimbiReply::reply($this->bot, $this->user_id, $reply);
        }
        else {
            BotManController::fallback_reply($bot, $this->user_id);
        }
    }

    public function confirm_complete_payment($user_id, $bot) {
        $this->bot = $bot;
        $this->user_id = $user_id;
        //Transaction::updateOrCreate(["id"=>$request->trans_id], ["amount"=>$request->amount, "payment_ref"=>$request->ref_no, "status"=>"processed"]);
        SimbiReply::reply($this->bot, $this->user_id, "Your transaction is successful");
        //$start_test_convo = new StartTestConversation();

        //$start_test_convo->set_user_id($user_id);
        //$start_test_convo->set_test_id(DB::table('user_datas')->where('user_id', $user_id)->value('test_id'));
        //$start_test_convo->set_author_id(DB::table('user_datas')->where('user_id', $user_id)->value('test_by_author_id'));
        //$this->bot->startConversation($start_test_convo);
    }

    /**
     * Start the conversation
     */
    public function run()
    {
        $this->askPaymentOption();
    }
}
