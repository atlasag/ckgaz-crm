<?php


namespace App\Services;


use GuzzleHttp\Client as GuzzleClient;
use Psr\Http\Message\ResponseInterface;

class Bitrix24Service
{
    /**
     * @var string Адрес сервера с Битриксом
     */
    private $url;

    /**
     * @var GuzzleClient Guzzle-клиент для обращений через HTTP
     */
    private $http;


    /**
     * Bitrix24Service constructor.
     *
     * @param GuzzleClient $httpClient
     */
    public function __construct(GuzzleClient $httpClient)
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

    /**
     * Отправляет GET-запрос в битрикс
     *
     * @param string $method
     * @param array $params
     * @return ResponseInterface
     */
    protected function sendGET(string $method, array $params = [])
    {
        return $this->http->get($this->getUrl().$method, $params);
    }

    /**
     * Отправляет GET-запрос в битрикс и декодирует ответ в объект
     *
     * @param string $method
     * @param array $params
     * @return mixed
     */
    protected function get(string $method, array $params = [])
    {
        $response = $this->http->get($this->getUrl().$method, $params);
        return json_decode($response->getBody()->getContents(), false);
    }

    /**
     * Возвращает описание полей лида, в том числе пользовательских.
     * https://dev.1c-bitrix.ru/rest_help/crm/leads/crm_lead_fields.php
     */
    public function crmLeadFields():object
    {
        return $this->get('crm.lead.fields');
    }


    /**
     * Возвращает описание полей сделки, в том числе пользовательских
     * https://dev.1c-bitrix.ru/rest_help/crm/cdeals/crm_deal_fields.php
     */
    public function crmDealFields():object
    {
        return $this->get('crm.deal.fields');
    }

    /**
     * Возвращает описание список пользовательских полей
     * https://dev.1c-bitrix.ru/rest_help/crm/cdeals/crm_userfield_fields.php
     */
    public function crmUserfieldFields():object
    {
        return $this->get('crm.userfield.fields');
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
        return $this->get('crm.lead.get', self::makeQueryParam(['id' => $id]));
    }


    /**
     * Возвращает сделку по идентификатору.
     * https://dev.1c-bitrix.ru/rest_help/crm/leads/crm_deal_get.php
     *
     * @param $id
     *
     * @return object
     */
    public function crmDealGet($id):object
    {
        return $this->get('crm.deal.get', self::makeQueryParam(['id' => $id]));
    }


    public function crmLeadList($filter = []):object
    {
        $params = [
            'order'=>['ID' => 'DESC'],
//            'filter'=>[],
            'select'=>['*','UF_*'],
        ];

        return $this->get('crm.lead.list', self::makeQueryParam($params));
    }


    public function crmDealList($filter = []):object
    {
        $params = [
            'order'  => ['ID' => 'DESC'],
            'filter' => $filter,
            'select' => ['*','UF_*'],
        ];

        return $this->get('crm.deal.list', self::makeQueryParam($params));
    }

    protected static function makeQueryParam(array $param = [])
    {
        return empty($param) ? []  : ['query' => $param ];
    }
}
