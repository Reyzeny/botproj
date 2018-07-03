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
        $this->bot->reply($question);
    }

    public function confirm_payment_option($user_id, $bot, $response) {
        $this->bot = $bot;
        $this->transaction_id = Transaction::select('id')->where('user_id', $user_id)->orderBy('id', 'desc')->value('id');
        $user_email = DB::table('users')->where('user_id', $user_id)->value('email');
        $test_id = DB::table('user_datas')->where('user_id', $user_id)->value('test_by_author_id');
        $amount = DB::table('tests')->where('test_id', $test_id)->value('amount');
        if (preg_match("[online]", strtolower($response))) {
            $this->bot->reply('ok', ['payment_action'=>'show_payment', 'transaction_id'=>$this->transaction_id, 'user_email'=>$user_email, 'amount'=>$amount]);
        }
        elseif (preg_match("[transfer]", strtolower($response))) {

        }
    }

    public function confirm_complete_payment(Request $request) {

    }

    /**
     * Start the conversation
     */
    public function run()
    {
        $this->askPaymentOption();
    }
}
