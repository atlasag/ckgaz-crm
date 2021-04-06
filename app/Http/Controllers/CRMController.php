<?php

namespace App\Http\Controllers;

use App\Conversations\AssignVisitConversation;
use BotMan\BotMan\BotMan;
use Illuminate\Http\Request;

class CRMController extends Controller
{
    public function assignVisitAction(Request $request, BotMan $bot)
    {
        $to = $request->input('to');
        $text = $request->input('text');
        $bot->startConversation(new AssignVisitConversation($text), $to);

        return response('ok',200);
    }
}
