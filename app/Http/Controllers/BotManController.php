<?php

namespace App\Http\Controllers;

use BotMan\BotMan\BotMan;
use Illuminate\Http\Request;
use App\Conversations\ExampleConversation;
use App\Conversations\GreetingConversation;
use BotMan\BotMan\Middleware\ApiAi;
use App\User;
use App\UserData;
use App\Transaction;
use Illuminate\Support\Facades\DB;
use App\Conversations\PersonalInformationConversation;
use App\Conversations\TestNameConversation;
use App\Conversations\TestSelectionConversation;
use App\Conversations\StartPaymentConversation;
use App\Conversations\StartTestConversation;
use App\Conversations\TestCompletionConversation;
use App\Conversations\PaymentOptionConversation;


class BotManController extends Controller
{

    public $botman;

    public function handle(Request $request)
    {
        /*This place should be executed at first */
        $this->botman = app('botman');

        $user = new User();
        if (!$user->user_exists($request->userId)) {
            $user->create_user($request->userId);
        }

        $this->botman->hears('__payment_successful__', function($bot) use($request){
            $reply_array = array(
                "Your payment is successful",
                "Your payment has been received"
            );
            $reply = $reply_array[rand(0, sizeof($reply_array)-1)];
            $bot->reply($reply);
            $start_test_convo = new StartTestConversation();
            $start_test_convo->set_user_id($request->userId);
            $start_test_convo->set_test_id(DB::table('user_datas')->where('user_id', $request->userId)->value('test_id'));
            $start_test_convo->set_author_id(DB::table('user_datas')->where('user_id', $request->userId)->value('test_by_author_id'));
            $bot->startConversation($start_test_convo);
        });
        /*End of This place */

        $greeting_keywords = $this->get_greeting_keywords();
        $this->botman->hears($greeting_keywords, function($bot) use($request){
            $this->matches_greeting($bot, $request);
        });

        // $this->botman->hears('my email is {mail}', function($bot, $mail) use($request){
        //     $pic = new PersonalInformationConversation();
        //     $pic->set_user_id($request->userId);
        //     $pic->confirm_email($mail, $bot);
        // });
       
        $dialogflow = ApiAi::create('3353f8816a3748afa3380c2dc5a1ea7b')->listenForAction();
        $this->botman->middleware->received($dialogflow);
        $this->botman->hears('test_available', function (BotMan $bot) use($request){
            $extras = $bot->getMessage()->getExtras();
            $apiReply = $extras['apiReply'];
            $apiAction = $extras['apiAction'];
            $apiIntent = $extras['apiIntent'];
            $apiParameters = $extras['apiParameters'];
            //$bot->reply($api_reply);
            $testnameconvo = new TestNameConversation();
            $testnameconvo->set_user_id($request->userId);
            $testnameconvo->show_all_test_list($bot);
        })->middleware($dialogflow);
        $this->botman->hears('test_name', function (BotMan $bot) use($request){
            $extras = $bot->getMessage()->getExtras();
            $apiReply = $extras['apiReply'];
            $apiAction = $extras['apiAction'];
            $apiIntent = $extras['apiIntent'];
            $apiParameters = $extras['apiParameters'];

            if (DB::table('user_datas')->where('user_id', $request->userId)->value('context')=='test_selection') {
                self::fallback_reply($bot, $request->userId, $bot->getMessage()->getText());
                return;
            }

            if (isset($apiParameters['test_name']) && isset($apiParameters['author_name'])) {
                $test_selection = new TestSelectionConversation();
                $test_selection->set_user_id($request->userId);
                $test_selection->confirm_full_text_entry(strtolower($apiParameters['test_name']), strtolower($apiParameters['author_name']), $bot);
            }
            elseif (isset($apiParameters['test_name']) && !isset($apiParameters['author_name'])) {
                $testnameconvo = new TestNameConversation();
                $testnameconvo->set_user_id($request->userId);
                $testnameconvo->confirm_testname($apiParameters['test_name'], $bot);    
            }
            elseif (!isset($apiParameters['test_name']) && isset($apiParameters['author_name'])) {
                $testnameconvo = new TestNameConversation();
                $testnameconvo->set_user_id($request->userId);
                $testnameconvo->confirm_testname($apiParameters['author_name'], $bot);
            }
        })->middleware($dialogflow);
        $this->botman->hears('well_being', function (BotMan $bot) use($request){
            $extras = $bot->getMessage()->getExtras();
            $apiReply = $extras['apiReply'];
            $bot->reply($apiReply);
            $pic = new PersonalInformationConversation();
            $pic->set_user_id($request->userId);
            $bot->startConversation($pic); 
        })->middleware($dialogflow);
        $this->botman->hears('name_question', function (BotMan $bot) use($request){
            $extras = $bot->getMessage()->getExtras();
            $apiReply = $extras['apiReply'];
            $bot->reply($apiReply); 
        })->middleware($dialogflow);
        $this->botman->hears('matches_lovely_question', function (BotMan $bot) use($request){
            $extras = $bot->getMessage()->getExtras();
            $apiReply = $extras['apiReply'];
            $bot->reply($apiReply); 
        })->middleware($dialogflow);
        $this->botman->hears('unsure_action', function (BotMan $bot) use($request){
            $extras = $bot->getMessage()->getExtras();
            $apiReply = $extras['apiReply'];
            $bot->reply($apiReply); 
        })->middleware($dialogflow);




        
        $this->botman->fallback(function($bot) use($request) {
            self::fallback_reply($bot, $request->userId, strtolower($bot->getMessage()->getText()));    
        });

        $this->botman->listen();        
    }


    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function tinker()
    {
         $botman = app('botman');

        $isNewUser = false;
        $userId = $_COOKIE['userId'] ?? null;
        
        if ($userId){
            
            $user = new User;
            $current_user = $user->getByUniqueId($userId);

            if (is_null($current_user)){
                //user has used simbi but no registered email
                $data['userId'] = $userId;
            }
            else{
                $data['userId'] = $current_user->user_id;                
            }
        }
        else{
            $data['userId'] = rand(1, 10) . str_random('15') . rand(1, 10);
            $isNewUser  = true;
            $data['isNewUser'] = $isNewUser;
            // Cookie::queue('userId', $data['userId'], 18000000);
            setcookie("userId", $data['userId'], time() + (86400 * 30));
        }
        $data['isNewUser'] = $isNewUser;
        
        //return view('tinker');
        return view('chat-screen', $data);
    }



    public function get_greeting_keywords() {
        $possible_text = '.*(^hi |^hi$|^hello|^helo|^wassup|^wasup|^xup|^sup|^hlo|^elo$|^how far|^hy$|^hy simbi|^i$|simbi$|^hey|buzz|wadup|what.*up.*|good day|^howva$|^restart|^refresh|mornin.*|aft.*noon|evening|you there|can i learn more about a service|what services do you offer|how much do your services cost|can I get customer service help|get started).*';

        return $possible_text;
    }

    public function matches_greeting(BotMan $bot, Request $request) {
        $bot->startConversation(new GreetingConversation($request->userId));
    }











    public static function get_random_fallback_reply() {
        $reply_array = array(
            "I don't understand that",
            "What do you mean "
        );
        $reply = $reply_array[rand(0, sizeof($reply_array)-1)];
        return $reply;
    }

    public static function get_random_email_fallback_reply() {
        $reply_array = array(
            "Email is not valid",
            "Enter a valid email address "
        );
        $reply = $reply_array[rand(0, sizeof($reply_array)-1)];
        return $reply;
    }
    public static function get_random_testtitle_fallback_reply() {
        $reply_array = array(
            "I don't know that test. Probably try something else",
            "I don't have that as a test "
        );
        $reply = $reply_array[rand(0, sizeof($reply_array)-1)];
        return $reply;
    }

    public function confirm_complete_payment(Request $request) {
        $this->botman = app('botman');
        Transaction::updateOrCreate(["id"=>$request->transaction_id], ["amount"=>$request->amount, "payment_ref"=>$request->ref_no, "status"=>"processed"]);

        $this->botman->hears('.*', function($bot) use($request){
            $reply_array = array(
                "Your payment is successful",
                "Your payment has been received"
            );
            $reply = $reply_array[rand(0, sizeof($reply_array)-1)];
            $bot->reply($reply);
            $start_test_convo = new StartTestConversation();
            $start_test_convo->set_user_id($request->userId);
            $start_test_convo->set_test_id(DB::table('user_datas')->where('user_id', $request->userId)->value('test_id'));
            $start_test_convo->set_author_id(DB::table('user_datas')->where('user_id', $request->userId)->value('test_by_author_id'));
            $bot->startConversation($start_test_convo);
        });
        
        
        //$payment_option_convo = new PaymentOptionConversation();
        //$payment_option_convo->set_user_id($request->userId);
        //$payment_option_convo->confirm_complete_payment($request->userId, $this->botman);
        

        $this->botman->listen();

        // $successful_reply_array = array(
        //     "Your transaction is successful"
        // );
        // $reply = $successful_reply_array[rand(0, sizeof($successful_reply_array)-1)];
        // $this->botman->say($reply, $request->userId, 'web');
        // $this->botman->listen();


        // $start_test_convo = new StartTestConversation();
        // $start_test_convo->set_user_id($request->userId);
        // $start_test_convo->set_test_id(DB::table('user_datas')->where('user_id', $request->userId)->value('test_id'));
        // $start_test_convo->set_author_id(DB::table('user_datas')->where('user_id', $request->userId)->value('test_by_author_id'));
        // $this->botman->startConversation($start_test_convo);
         


        // $this->botman->hears('.*', function($bot) use($request){
        //     $this->bot = $bot;
        //     Transaction::updateOrCreate(["id"=>$request->transaction_id], ["amount"=>$request->amount, "payment_ref"=>$request->ref_no, "status"=>"processed"]);
        //     $this->bot->reply("Your transaction is successful");
        //     $start_test_convo = new StartTestConversation();
        //     $start_test_convo->set_user_id($request->userId);
        //     $start_test_convo->set_test_id(DB::table('user_datas')->where('user_id', $request->userId)->value('test_id'));
        //     $start_test_convo->set_author_id(DB::table('user_datas')->where('user_id', $request->userId)->value('test_by_author_id'));
        //     $this->bot->startConversation($start_test_convo);
        // });
        // $this->botman->listen(); 
        
    }

   
    

    public static function fallback_reply(BotMan $bot, $user_id, $unknown_word="") {
        $result = DB::select('select context from user_datas where user_id=?', [$user_id]);
        $context = $result[0]->context;
        $unknown_word==strtolower($unknown_word);
        
        if ($context=='email') {
            if (!empty($unknown_word)) {
                $pic = new PersonalInformationConversation();
                $pic->set_user_id($user_id);
                $pic->confirm_email($unknown_word, $bot);
                return;
            }
            $reply = self::get_random_email_fallback_reply();
            $bot->reply($reply);
            
        }
        else if ($context=='firstname') {
            if (!empty($unknown_word)) {
                $pic = new PersonalInformationConversation();
                $pic->set_user_id($user_id);
                $pic->confirm_firstname($unknown_word, $bot);
                return;
            }
        }
        else if ($context=='lastname') {
            if (!empty($unknown_word)) {
                $pic = new PersonalInformationConversation();
                $pic->set_user_id($user_id);
                $pic->confirm_lastname($unknown_word, $bot);
                return;
            }
        }
        else if ($context=='test_name') {
            if (!empty($unknown_word)) {
                $testnameconvo = new TestNameConversation();
                $testnameconvo->set_user_id($user_id);
                $testnameconvo->confirm_testname($unknown_word, $bot);
                return;
            }
            $reply = self::get_random_testtitle_fallback_reply();
            $bot->reply($reply);
        }
        else if ($context=='test_selection') {
            if (!empty($unknown_word)) {
                $test_selection = new TestSelectionConversation();
                $test_selection->set_user_id($user_id);
                $test_selection->set_test_entered($unknown_word);
                $test_selection->confirm_suggestion_selection($unknown_word, $bot);
                return;
            }
            $reply = self::get_random_testtitle_fallback_reply();
            $bot->reply($reply);
        }
        else if ($context=='payment') {
            if (!empty($unknown_word)) {
                $StartPaymentConversation = new StartPaymentConversation();
                $StartPaymentConversation->set_user_id($user_id);
                $StartPaymentConversation->confirm_payment($user_id, $bot, $unknown_word);
                return;
            }
            $reply = self::get_random_testtitle_fallback_reply();
            $bot->reply($reply);
        }
        else if ($context=='payment_option') {
            if (!empty($unknown_word)) {
                $payment_option_convo = new PaymentOptionConversation();
                $payment_option_convo->set_user_id($user_id);
                $payment_option_convo->confirm_payment_option($user_id, $bot, $unknown_word);
                return;
            }
            $reply = self::get_random_testtitle_fallback_reply();
            $bot->reply($reply);
        }
        else if ($context=='test_start') {
            if (!empty($unknown_word)) {
                if ($unknown_word=='start') {
                    $start_test_convo = new StartTestConversation();
                    $start_test_convo->set_user_id($user_id);
                    $start_test_convo->startTest($bot);

                }
                return;
            }
            
        }
        else if ($context=='test_finished') {
            if (!empty($unknown_word)) {
                $start_test_convo = new StartTestConversation();
                $start_test_convo->set_user_id($user_id);
                $start_test_convo->confirm_test_finished_response($unknown_word, $bot);
                return;
            }
            //$reply = self::get_random_testtitle_fallback_reply();
            //$bot->reply($reply);
        }
        else if ($context=='test_completed') {
            if (!empty($unknown_word)) {
                $test_completion_conv = new TestCompletionConversation();
                $test_completion_conv->set_user_id($user_id);
                $test_completion_conv->test_completion_response($unknown_word, $bot);
                return;
            }
            //$reply = self::get_random_testtitle_fallback_reply();
            //$bot->reply($reply);
        }
        else
        {
            $reply = self::get_random_fallback_reply();
            $bot->reply($reply);
        }
    }
}
