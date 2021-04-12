<?php

namespace App\Conversations;

use BotMan\BotMan\Messages\Conversations\Conversation;
use BotMan\BotMan\Messages\Incoming\Answer;
use BotMan\BotMan\Messages\Incoming\IncomingMessage;
use BotMan\BotMan\Messages\Outgoing\Actions\Button;
use BotMan\BotMan\Messages\Outgoing\OutgoingMessage;
use BotMan\BotMan\Messages\Outgoing\Question;
use BotMan\Drivers\Telegram\Extensions\Keyboard;
use BotMan\Drivers\Telegram\Extensions\KeyboardButton;
use BotMan\Drivers\Telegram\TelegramDriver;

class AssignVisitConversation extends Conversation
{
    private $text;

    /**
     * Start the conversation.
     *
     * @return mixed
     */
    public function run()
    {
        $this->askVisit();
    }


    public function askVisit()
    {
        $keyboard = Keyboard::create(Keyboard::TYPE_KEYBOARD)
            ->resizeKeyboard(true)
            ->oneTimeKeyboard(true)
            ->addRow(
                KeyboardButton::create('Да')->callbackData('later'),
                KeyboardButton::create('Позже')->callbackData('later')
            )
            ->addRow(
                KeyboardButton::create('Отказался')->callbackData('refuse')
            )
        ->toArray();
        logger(print_r($keyboard,1));


        $tg = $this->bot->getUser()->getInfo();

        logger('in conversation with ', $tg);
        $reply = Question::create($this->text."\nНазначить выезд?")
            //->addButtons($keyboard);
            /*->addButtons([
                Button::create('Да')->value('random') ,
                Button::create('Позже')->value('random2'),
                Button::create('Клиент отказался')->value('random3')
            ])
            ->addButton(Button::create('yes')->value('tess'))*/
        ;
        logger('question prepared');

        $reply2= 'Мой вопрос +79101234567';
        $this->ask($reply2, function(Answer $answer) {
            logger('ask asked');
            if($answer->getText() == 'Да'){
                // ToDo: accept visit action
                $this->say('Выезд назначен Вам.');
                return;
            };

            if($answer->getText() == 'Позже'){
                // ToDo: delay visit action
                $this->say('Вызов поставлен в список ожидания.');
                return;
            };

            if($answer->getText() == 'Клиент отказался'){
                $this->askReason();
                return;
            };
            logger('here');
        }, $keyboard);
    }

    public function askReason()
    {
        $this->ask($this->text."\nУкажите причину отказа?", function(Answer $answer) {
            // ToDo:  reject visit action
        });
    }

    public function __construct($text)
    {
        $this->text = $text;
    }

/*    public function stopsConversation(IncomingMessage $message)
    {
        if ($message->getText() == 'Отмена'){
            return true;
        }
        return false;
    }*/
}
