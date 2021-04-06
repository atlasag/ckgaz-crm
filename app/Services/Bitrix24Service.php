<?php


namespace App\Services;


use App\Models\Client;
use Illuminate\Support\Facades\Validator;
use Psr\Http\Client\ClientInterface;

class Bitrix24Service
{
    const FIELD_NAME_SOURCE = 'SOURCE_ID';
    //const SOURCE_VALUE = 'ОхраПроБот';

    const SOURCE_ID = 21; // узнать можно из STATUS_ID: [API] /crm.status.list?filter[ENTITY_ID]=SOURCE
    const FIELD_NAME_ORIGINATOR = 'ORIGINATOR_ID';
    const ORIGINATOR_VALUE = 'TELEGRAM';
    const FIELD_NAME_TELEGRAM_ID = 'ORIGIN_ID';
    const UTM_LABEL = 'OxraProBot';

    /**
     *  Поля для отслеживания статуса лида в CRM: одобрен или отклонен
     */
    const FIELD_NAME_APPROVAL_STATUS = 'STATUS_ID';
    const STATUS_APPROVED = 'CONVERTED';
    const STATUS_REJECTED = 'JUNK';

    /**
     * Адрес сервера с Битриксом
     * @var string
     */
    private $url;

    /**
     * Guzzle-клиент для обращений через HTTP
     * @var ClientInterface
     */
    private $http;

    /**
     * Bitrix24Service constructor.
     *
     * @param \GuzzleHttp\Client $httpClient
     */
    public function __construct(\GuzzleHttp\Client $httpClient)
    {
        $this->url = rtrim(config('bitrix.url'),'/').'/';
        $this->http = $httpClient;
    }

    /**
     * URL сервера с Битриксом24 (https://example.com/)
     * @return string
     */
    public function getUrl():string
    {
        return $this->url;
    }

    private function sendGET(string $method, array $params = [])
    {
        return $this->http->get($this->getUrl().$method, $params);
    }

    /**
     * Создает или обновляет существуюзщй лид
     *
     * @param Client $client
     *
     * @return bool
     */
    public function crmLeadAddOrUpdate(Client $client): bool
    {
        return empty($client->crm_id)
            ? $this->crmLeadAdd($client)
            : $this->crmLeadUpdate($client);
    }


    protected function prepareQuery(Client $client): array
    {
        $query = [
            'FIELDS[TITLE]' => "Новый лид от OxraProBot",
            'FIELDS[NAME]' => $client->profile->first_name ?? $client->first_name ?? 'no name',
            'FIELDS[LAST_NAME]' => $client->profile->last_name ?? $client->last_name ?? 'неизвестно',
            'FIELDS[COMPANY_TITLE]' => $client->profile->company_name ?? '',
            'FIELDS[POST]' => $client->profile->job_title ?? '',
            //'FIELDS[BUSINESS_AREA]' => $client->profile->business_area ?? '',
            'FIELDS[PHONE][0][VALUE]' => $client->profile->phone ?? '+79000000000',
            'FIELDS[PHONE][0][VALUE_TYPE]' => 'WORK',
            'FIELDS[UTM_SOURCE]' => self::UTM_LABEL,
            'FIELDS['.self::FIELD_NAME_SOURCE.']' => self::SOURCE_ID,
            'FIELDS['.self::FIELD_NAME_ORIGINATOR.']' => self::ORIGINATOR_VALUE,
            'FIELDS['.self::FIELD_NAME_TELEGRAM_ID.']' => $client->key,
        ];
        if (filter_var($client->profile->email ?? 'no email', FILTER_VALIDATE_EMAIL)) {
            $query['FIELDS[EMAIL][0][VALUE]'] = $client->profile->email;
            $query['FIELDS[EMAIL][0][VALUE_TYPE]'] = 'WORK';
        }
        return $query;
        }

    /**
     * Создаёт новый лид.
     * https://dev.1c-bitrix.ru/rest_help/crm/leads/crm_lead_add.php
     *
     * @param Client $client
     *
     * @return bool
     */
    public function crmLeadAdd(Client $client): bool
    {
        $query = $this->prepareQuery($client);

        try {
            $response = $this->sendGET('crm.lead.add', ['query'=> $query]);
            logger(json_decode($response->getBody(), true));
            if($response->getStatusCode() === 200){
                $return = json_decode($response->getBody(), false);
                $client->crm_id = $return->result;
                $client->save();
                return true;
            }
        }catch(\Exception $e){
            logger('Ошибка добавления данных в CRM:'.$e->getMessage(), $query);
        }

        return false;
    }

    /**
     * Обновляет существующий лид.
     * https://dev.1c-bitrix.ru/rest_help/crm/leads/crm_lead_update.php
     *
     * @param Client $client
     *
     * @return bool
     */
    public function crmLeadUpdate(Client $client): bool
    {
        $query = $this->prepareQuery($client);
        $query['ID'] = $client->crm_id;
        $query['PARAMS[REGISTER_SONET_EVENT]'] = 'Y';

        try {
            $response = $this->sendGET('crm.lead.update',['query' => $query]);
            logger(json_decode($response->getBody(), true));
            return true;
        }catch(\Exception $e){
            logger('Ошибка добавления данных в CRM:'.$e->getMessage(), $query);
        }

        return false;
    }

    /**
     * Возвращает описание полей лида, в том числе пользовательских.
     * https://dev.1c-bitrix.ru/rest_help/crm/leads/crm_lead_fields.php
     */
    public function crmLeadFields():object
    {
        $response = $this->sendGET('crm.lead.fields');
        $result = $response->getBody()->getContents();
        return json_decode($result, false);
    }

    /**
     * Возвращает лид по идентификатору.
     * https://dev.1c-bitrix.ru/rest_help/crm/leads/crm_lead_get.php
     *
     * @param $id
     *
     * @return object
     */
    public function crmLeadGet($id):object
    {
        $response = $this->sendGET('crm.lead.get',[
            'query'=>[
                'id'=>$id
            ]
        ]);
        $result = $response->getBody()->getContents();
        return  json_decode($result, false) ;
    }


    public function crmLeadList($limit = 100):object
    {
        $response = $this->sendGET('crm.lead.list',[
            'query'=>[
                'order'=>['ID' => 'DESC'],
//                'filter'=>[],
                'select'=>['*','UF_*'],
            ]
        ]);
        $result = $response->getBody()->getContents();
        return  json_decode($result, false) ;
    }
}
