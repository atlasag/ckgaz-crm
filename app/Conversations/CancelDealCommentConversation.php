<?php

namespace App\Conversations;

use App\Services\CKGasBitrixService;
use BotMan\BotMan\Messages\Incoming\Answer;
use BotMan\BotMan\Messages\Outgoing\Question;
use BotMan\BotMan\Messages\Conversations\Conversation;

class CancelDealCommentConversation extends Conversation
{
    protected $dealId;


    /**
     * CancelDealCommentConversation constructor.
     * @param $dealId
     */
    public function __construct($dealId)
    {
        $this->dealId = $dealId;
    }

    /**
     * Start the conversation
     */
    public function run()
    {
        $this->askReason();
    }

    /**
     * First question
     * @return self
     */
    public function askReason()
    {
        $question = Question::create("Отказ от заявки.  Напишите причину отказа:")
            ->fallback('Я вас не понимаю...')
            ->callbackId('cancel_reason')
        ;

        return $this->ask($question, function (Answer $answer) {
            $reason = $answer->getText();

           // Переместить по воронке
            $gasBitrixService = app(CKGasBitrixService::class);
            $gasBitrixService
                //->assignEngineerForDeal(0)
                ->setDealComment($reason)
                ->setDealStage(CKGasBitrixService::STAGE_DEFAULT)
                ->updateDeal($this->dealId);

            $this->say('Отказ зарегистрирован');

        });
    }


}
