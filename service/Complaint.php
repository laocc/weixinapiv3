<?php

namespace esp\weiPay\service;


use esp\weiPay\ApiV3Base;

class Complaint extends ApiV3Base
{

    private $comApi = '/v3/merchant-service/complaint-notifications';

    public function notifyUrl(string $action, string $url = null)
    {
        switch ($action) {
            case 'get':
                $data = $this->get($this->comApi);
                break;
            case 'set':
                $param = [];
                $param['url'] = $url;
                $data = $this->post($this->comApi, $param, 'post');
                break;
            case 'update':
                $param = [];
                $param['url'] = $url;
                $data = $this->post($this->comApi, $param, 'put');
                break;
            case 'delete':
                $data = $this->get($this->comApi, null, 'delete');
                break;
            default:
                return '';
        }
        if (is_string($data)) return $data;

        return $data;
    }


    public function reply(string $mchID, string $comID, string $content)
    {
        $param = [];
        $param['complaint_id'] = $comID;
        $param['complainted_mchid'] = $mchID;
        $param['response_content'] = $content;
//        $param['response_images'] = [];
        $data = $this->post($this->comApi, $param, 'post', true);
        if (is_string($data)) return $data;
        return $data === 204;
    }


}