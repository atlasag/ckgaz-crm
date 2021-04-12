<?php

use App\Conversations\AssignVisitConversation;
use App\Http\Controllers\BotManController;
use App\Http\Controllers\CRMController;

/** @var \BotMan\BotMan\BotMan $botman */
$botman = resolve('botman');

$botman->hears('Hi', function ($bot) {
    $bot->reply('Hello!');
});

$botman->hears('/start', BotManController::class.'@startCommandAction');


$botman->hears('setDeal:{dealId}:{engId}', CRMController::class.'@assignDealToEngineer');
$botman->hears('acceptDeal:{dealId}', CRMController::class.'@acceptDealByEngineer');
$botman->hears('cancelDeal:{dealId}', CRMController::class.'@cancelDealByEngineer');


$botman->group(['recipient' => config('botman.telegram.new_deals_chat')], function($bot){
    //
});

$botman->fallback(function ($bot){
    logger(print_r($bot->getMessage(),1));
    $bot->reply('Я вас не понимаю.');
});