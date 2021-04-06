<?php

use App\Conversations\AssignVisitConversation;
use App\Http\Controllers\BotManController;

$botman = resolve('botman');

$botman->hears('Hi', function ($bot) {
    $bot->reply('Hello!');
});
$botman->hears('ttt', function ($bot) {
    $bot->startConversation(new AssignVisitConversation('question'));
});
$botman->hears('Start conversation', BotManController::class.'@startConversation');
