<?php

namespace App\Http\Controllers;

use BotMan\BotMan\BotMan;
use Illuminate\Http\Request;
use App\Conversations\ExampleConversation;
use App\Conversations\GreetingConversation;
use BotMan\BotMan\Middleware\ApiAi;
use App\User;
use App\UserData;
use Illuminate\Support\Facades\DB;
use App\Conversations\PersonalInformationConversation;
use App\Conversations\TestNameConversation;
use App\Conversations\TestSelectionConversation;
use App\Conversations\StartPaymentConversation;
use App\Conversations\StartTestConversation;
use App\Conversations\TestCompletionConversation;

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
        /*End of This place */

        

        $greeting_keywords = $this->get_greeting_keywords();
        $this->botman->hears($greeting_keywords, function($bot) use($request){
            $this->matches_greeting($bot, $request);
        });

        $this->botman->hears('my email is {mail}', function($bot, $mail) use($request){
            $pic = new PersonalInformationConversation();
            $pic->set_user_id($request->userId);
            $pic->confirm_email($mail, $bot);
        });
       
        // $dialogflow = ApiAi::create('10b0f73b619b449b84c8ba0c025bac59')->listenForAction();
        // $botman->middleware->received($dialogflow);

        // $botman->hears('my_api_action', function (BotMan $bot) {
        //     // The incoming message matched the "my_api_action" on Dialogflow
        //     // Retrieve Dialogflow information:
        //     $extras = $bot->getMessage()->getExtras();
        //     $apiReply = $extras['apiReply'];
        //     $apiAction = $extras['apiAction'];
        //     $apiIntent = $extras['apiIntent'];


            
        //     $bot->reply("this is my reply");
        // })->middleware($dialogflow);

        
        $this->botman->fallback(function($bot) use($request) {
            self::fallback_reply($bot, $request->userId, $bot->getMessage()->getText());    
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











    public function get_random_fallback_reply() {
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

   
    

    public static function fallback_reply(BotMan $bot, $user_id, $unknown_word="") {
        $user_data = new UserData();
        $result = DB::select('select context from user_datas where user_id=?', [$user_id]);
        $context = $result[0]->context;
        
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
                $test_selection->showSuggestion($bot);
                return;
            }
            $reply = self::get_random_testtitle_fallback_reply();
            $bot->reply($reply);
        }
        // else if ($context=='payment') {
        //     if (!empty($unknown_word)) {
        //         $test_selection = new TestSelectionConversation();
        //         $test_selection->set_user_id($this->user_id);
        //         $test_selection->set_test_entered($test_title);
        //         $test_selection->showSuggestion();
        //         return;
        //     }
        //     $reply = self::get_random_testtitle_fallback_reply();
        //     $bot->reply($reply);
        // }
        else
        {
            $reply = $this->get_random_fallback_reply();
            $bot->reply($reply);
        }
    }
}
