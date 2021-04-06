<?php

namespace App\Conversations;

use BotMan\BotMan\Messages\Conversations\Conversation;
use BotMan\BotMan\Messages\Incoming\Answer;
use BotMan\BotMan\Messages\Incoming\IncomingMessage;
use BotMan\BotMan\Messages\Outgoing\Actions\Button;
use BotMan\BotMan\Messages\Outgoing\OutgoingMessage;
use BotMan\BotMan\Messages\Outgoing\Question;

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
        $reply = Question::create($this->text."\nНазначить выезд?")
            ->addButtons([
                new Button('Да') ,
                new Button('Позже'),
                new Button('Клиент отказался')
            ]);

        $this->ask($reply, function(Answer $answer) {
            logger('uuu');
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
        });
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
