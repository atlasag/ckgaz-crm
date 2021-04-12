<?php


namespace App\Services;


class CKGasBitrixService extends Bitrix24Service
{

    const VORONKA_NAME = 'C6';
    const STAGE_DEFAULT = self::VORONKA_NAME.':PREPAYMENT_INVOICE';
    const STAGE_SEND_TO_ENGINEER = self::VORONKA_NAME.':EXECUTING';
    const STAGE_VISIT_CONFIRMED = self::VORONKA_NAME.':2';
    const STAGE_VISIT_DONE = self::VORONKA_NAME.':FINAL_INVOICE';

    const ENGINEER_FIELD_NAME = 'UF_CRM_1618153773812';
    private $engineers;

    /**
     * @var array параметры для обновления
     */
    private $params = [];

    /**
     * Выбрать все сделки, у которых не назначен инженер
     *
     * @param string $stage
     * @return object
     */
    public function getUnassignedDeals(string $stage = self::STAGE_DEFAULT)
    {
        $filter = [
            'STAGE_ID' => $stage,
            self::ENGINEER_FIELD_NAME => false
        ];
        return $this->crmDealList($filter);
    }

    /**
     * Выбрать все сделки, которым назначен инженер (конкретный, или любой)
     *
     * @param string $stage
     * @param int $engineerId
     * @return object
     */
    public function getAssignedDeals(int $engineerId = 0, string $stage = '')
    {
        $filter = [];
        if($engineerId > 0){
            $filter[self::ENGINEER_FIELD_NAME] = $engineerId;
        }else{
            $filter['>'.self::ENGINEER_FIELD_NAME] = 0;
        }

        if(!empty($stage)){
            $filter['STAGE_ID'] = $stage;
        }

        return $this->crmDealList($filter);
    }

    /**
     * Задать статус сделки (требует ->update() )
     * @param $stage
     * @return $this
     */
    public function setDealStage($stage)
    {
        $this->params['FIELDS[STAGE_ID]'] = $stage;

        return $this;
    }

    /**
     * Задать комментарий к сделке
     * @param $comment
     * @return $this
     */
    public function setDealComment($comment)
    {
        $this->params['FIELDS[COMMENTS]'] = $comment;

        return $this;
    }


    /**
     * Получить список инженеров
     *
     * @return array  [bitrix_id => [name => '', 'tg_key' => 12346]]
     */
    public function getEngineersList()
    {
        return $this->engineers;
    }

    /**
     * Проверить наличие инженера по TelegramId
     *
     * @param $telegramId
     * @return int
     */
    public function checkEngineer($telegramId): int
    {
        foreach($this->engineers as $id => $data){
            if($data['tg_key'] == $telegramId){
                return $id;
            }
        }
        return 0;
    }

    /**
     * Получить TelegramId инженера
     *
     * @param $id
     * @return int
     */
    public function getEngineersTelegramId($id): int
    {
        return (int) ($this->engineers[$id]['tg_key'] ?? 0);
    }

    /**
     * Получить имя инженера
     *
     * @param $id
     * @return string
     */
    public function getEngineersName($id): string
    {
        return (string) ($this->engineers[$id]['name'] ?? '-');
    }

    /**
     * Загрузить список инженеров из Битрикса
     *
     * @return $this
     */
    public function loadEngineers()
    {
        $this->engineers = [];
        $fields = $this->crmDealFields();
        if(isset(
            $fields->result,
            $fields->result->{self::ENGINEER_FIELD_NAME},
            $fields->result->{self::ENGINEER_FIELD_NAME}->items)
        ){
            foreach ($fields->result->{self::ENGINEER_FIELD_NAME}->items as $item){
                $key = self::extractTelegramKeyFromName($item->VALUE);
                if(!empty($key)){
                    $name =  trim(str_replace('{'.$key.'}', '', $item->VALUE));
                    $this->engineers[$item->ID] = ['name' => $name, 'tg_key' => (int) $key];
/*                }else{
                    $this->engineers[$item->ID] = ['name' => $item->VALUE, 'tg_key' => $key];*/
                }
            }
        }

        return $this;
    }


    /**
     * Назначить инженера на сделку (требует ->update() )
     *
     * @param int $engineerId
     * @return $this
     */
    public function assignEngineerForDeal($engineerId = 0)
    {
        $this->params['FIELDS['.self::ENGINEER_FIELD_NAME.']'] = $engineerId;

        return $this;
    }

    /**
     * Отправить измененные параментры сделки в Битрикс
     *
     * @param $dealId
     * @return mixed
     */
    public function updateDeal($dealId)
    {
        $params = $this->params;
        if(empty($params)) return false;

        $params['ID'] = $dealId;
        $this->params = [];

        return $this->get('crm.deal.update', self::makeQueryParam($params));
    }


    /**
     * Вспомогательная функция извлечения {telegramId} из имени инженера
     *
     * @param string $name
     * @return mixed|string
     */
    protected static function extractTelegramKeyFromName(string $name)
    {
        preg_match('/\{(\d+)\}/',$name, $matches);
        return $matches[1] ?? '';
    }

}