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
        // if (!$this->test_is_paid()) {

        //     $start_test_convo = new StartTestConversation();
        //     $start_test_convo->set_user_id($this->user_id);
        //     $start_test_convo->set_test_id($this->test_id);
        //     $start_test_convo->set_author_id($this->author_id);
        //     UserData::updateOrCreate(["user_id"=>$this->user_id], ["context"=>"test_start"]);
        //     $this->bot->startConversation($start_test_convo);
        //     return;
        // }
        
        if ($this->test_is_paid() && !$this->transaction_available($this->user_id)) {
            //var_dump(Test::find($this->test_id));
            $test_cost = Test::find($this->test_id)->first()->amount;
            $question = Question::create('This test is a paid test and it costs '.$test_cost.', would you like continue with payment?')
            ->fallback('Unable to create a new database')
            ->callbackId('create_database')
            ->addButtons([Button::create('Yes')->value('Yes'), Button::create('No')->value('No')]);
            $this->bot->reply($question);
            return;
        }

        
        $start_test_convo = new StartTestConversation();
        $start_test_convo->set_user_id($this->user_id);
        $start_test_convo->set_test_id($this->test_id);
        $start_test_convo->set_author_id($this->author_id);
        //UserData::updateOrCreate(["user_id"=>$this->user_id], ["context"=>"test_start"]);
        $this->bot->startConversation($start_test_convo);
    }

    public function confirm_payment($user_id, $bot, $response) {
        $this->bot = $bot;
        $this->test_id = DB::table('user_datas')->where('user_id', $user_id)->value('test_id');
        $this->author_id = DB::table('user_datas')->where('user_id', $user_id)->value('test_by_author_id');
        if (preg_match("[yes]", strtolower($response))) {
            $id = DB::table('transactions')->insertGetId([ 'email'=>DB::table('users')->where('user_id', $user_id)->value('email'), 'user_id' => $this->user_id, 'test_id' => $this->test_id, 'author_id'=>$this->author_id]);

            $payment_option_convo = new PaymentOptionConversation();
            $payment_option_convo->set_user_id($user_id);
            $bot->startConversation($payment_option_convo);
            return;
        }
        if (preg_match("[no]", strtolower($response))) {
            $test_name_convo = new TestNameConversation();
            $test_name_convo->set_user_id($this->user_id);
            $this->bot->startConversation($test_name_convo);
            return;
        }
    }

    public function test_is_paid() {
        return Test::find($this->test_id)->first()->paid == 'yes';
    }

    public function transaction_available($user_id) {
        $this->user_id = $user_id;
        $this->test_id = UserData::where('user_id', $this->user_id)->pluck('test_id');
        $this->author_id = UserData::where('user_id', $this->user_id)->pluck('test_by_author_id');
        //echo "test id is ".$this->test_id;
        //echo "author id is ".$this->author_id;
        // $status = Transaction::where('user_id', $this->user_id)
        //             ->where('test_id', $this->test_id)
        //             ->where('author_id', $this->author_id)
        //             ->pluck('status');
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
