<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use BotMan\BotMan\BotMan;
use App\Conversations\StartTestConversation;

class PaymentConfirmationController extends Controller
{
    //

    public function confirm_complete_payment(Request $request, BotMan $bot) {
        Transaction::updateOrCreate(["id"=>$request->trans_id], ["amount"=>$request->amount, "payment_ref"=>$request->ref_no, "status"=>"processed"]);
        $bot->reply("Your transaction is successful");
        $start_test_convo = new StartTestConversation();
        $start_test_convo->set_user_id($request->userId);
        $start_test_convo->set_test_id(DB::table('user_datas')->where('user_id', $request->userId)->value('test_id'));
        $start_test_convo->set_author_id(DB::table('user_datas')->where('user_id', $request->userId)->value('test_by_author_id'));
        $this->bot->startConversation($start_test_convo);
    }
}
