<?php

namespace App\Http\Controllers;

use App\Conversations\AssignVisitConversation;
use App\Conversations\CancelDealCommentConversation;
use App\Services\CKGasBitrixService;
use BotMan\BotMan\BotMan;
use BotMan\Drivers\Telegram\Extensions\Keyboard;
use BotMan\Drivers\Telegram\Extensions\KeyboardButton;
use BotMan\Drivers\Telegram\TelegramDriver;
use Illuminate\Http\Request;

class CRMController extends Controller
{
    /**
     * @var BotMan
     */
    private $bot;
    /**
     * @var CKGasBitrixService
     */
    private $gasBitrixService;

    /**
     * CRMController constructor.
     * @param CKGasBitrixService $gasBitrixService
     */
    public function __construct(CKGasBitrixService $gasBitrixService)
    {
        $this->gasBitrixService = $gasBitrixService;
    }

    public function assignVisitAction(Request $request, BotMan $bot)
    {
        $to = $request->input('to');
        $text = $request->input('text');
        $bot->startConversation(new AssignVisitConversation($text), $to);

        return response('ok',200);
    }

    public function newDealAction(Request $request)
    {
        $validated = $this->validate($request, [
            'id' => 'required|numeric',
            'name' => 'required|string',
        ]);

        $message = 'Новая заявка #'.$validated['id'];
        $message .= "\n".$validated['name'];

        \BotMan::say(
            $message,
            config('botman.telegram.new_deals_chat'),
            TelegramDriver::class,
            $this->assignManagersKeyboard($validated['id'])
        );

        return response('ok',200);
    }

    protected function assignManagersKeyboard($deal): array
    {
        $engineers = $this->gasBitrixService->loadEngineers()->getEngineersList();

        $keyboard = Keyboard::create(Keyboard::TYPE_INLINE)
            ->resizeKeyboard(true)
            ->oneTimeKeyboard(true);

        foreach ($engineers as $key => $val){
            $callbackData = 'setDeal:'.$deal.':'.$key;
            $keyboard->addRow(KeyboardButton::create($val['name'])->callbackData($callbackData));
        }

        return $keyboard->toArray();
    }

    public function assignDealToEngineer(BotMan $bot, $dealId, $engId)
    {
        // Отправить инженеру
        $telegramId = $this->gasBitrixService->loadEngineers()->getEngineersTelegramId($engId);
        $payload =\BotMan::getMessage()->getPayload();
        $message = $payload['text'];
        \BotMan::say($message, $telegramId, TelegramDriver::class, $this->assignEngineersKeyboard($dealId));

        // Переместить по воронке
        $this->gasBitrixService
            ->setDealStage(CKGasBitrixService::STAGE_SEND_TO_ENGINEER)
            ->assignEngineerForDeal($engId)
            ->updateDeal($dealId);

        //Изменить исходное сообщение
        // ToDo: сделать красиво

        return response('ok',200);
    }

    public function  assignEngineersKeyboard($dealId)
    {
        $keyboard = Keyboard::create(Keyboard::TYPE_INLINE)
            ->resizeKeyboard(true)
            ->oneTimeKeyboard(true)
            ->addRow(KeyboardButton::create('✅ Принимаю')->callbackData('acceptDeal:'.$dealId))
            ->addRow(KeyboardButton::create('🚫 Отказываюсь')->callbackData('cancelDeal:'.$dealId))
        ;

        return $keyboard->toArray();
    }


    public function acceptDealByEngineer(BotMan $bot, $dealId)
    {
        // Переместить по воронке
        $this->gasBitrixService
            ->setDealStage(CKGasBitrixService::STAGE_VISIT_CONFIRMED)
            ->updateDeal($dealId);

        return response('ok',200);
    }

    public function cancelDealByEngineer(BotMan $bot, $dealId)
    {
        $bot->startConversation(new CancelDealCommentConversation($dealId));
    }

}
