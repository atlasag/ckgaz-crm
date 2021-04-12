<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Telegram Token
    |--------------------------------------------------------------------------
    |
    | Your Telegram bot token you received after creating
    | the chatbot through Telegram.
    |
    */
    'token' => env('TELEGRAM_TOKEN'),


    'new_deals_chat' => env('TELEGRAM_NEW_DEALS_CHAT', 0),
];
