<?php

namespace App\Http\Controllers;

use BotMan\BotMan\BotMan;

class BotManController extends Controller
{
    /**
     * Place your BotMan logic here.
     */
    public function handle()
    {
        $botman = app('botman');

        $botman->listen();
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function tinker()
    {
        return view('tinker');
    }

    /**
     * Loaded through routes/botman.php
     * @param  BotMan $bot
     */
    public function startCommandAction(BotMan $bot)
    {
        $id = $bot->getUser()->getId();
        $name = $bot->getUser()->getFirstName();
        $bot->reply("Привет $name!  Ваш id = $id");
    }

}
