<?php

namespace laocc\weiPay\merchant;

use laocc\weiPay\ApiV3Base;

class Complaint extends ApiV3Base
{

    public function notify()
    {
        return $this->notifyDecrypt();
    }

    public function reply(array $data)
    {
        $param = [];
        $param['complainted_mchid'] = $data['mchid'];
        $param['response_content'] = $data['content'];
//        $param['response_images'] = [];
        return $this->post("/v3/merchant-service/complaints-v2/{$data['complaint_id']}/response", $param, ['type' => 'post', 'returnCode' => true]);
    }

    public function download(array $data)
    {
        $time = $data['time'] ?? (strtotime('-29 day'));
        $begin = date('Y-m-d', $time);
        $end = date('Y-m-d', ($time + (86400 * 30)));
        $param = [];
        $param['limit'] = 50;
        $param['offset'] = $data['offset'] ?? 0;
        $param['begin_date'] = $begin;
        $param['end_date'] = $end;
        if (isset($data['mchid'])) $param['complainted_mchid'] = $data['mchid'];
        return $this->get("/v3/merchant-service/complaints-v2", $param, ['type' => 'get']);
    }

    public function read(array $data)
    {
        return $this->get("/v3/merchant-service/complaints-v2/{$data['id']}/", null, ['type' => 'get']);
    }

    public function history(array $data)
    {
        $param = [];
        $param['limit'] = 300;
        $param['offset'] = 0;
        return $this->get("/v3/merchant-service/complaints-v2/{$data['id']}/negotiation-historys", $param, ['type' => 'get']);
    }

    /**
     * 投诉回调
     *
     * 文档：
     *
     * https://pay.weixin.qq.com/wiki/doc/apiv3/apis/chapter10_2_11.shtml
     *
     * https://pay.weixin.qq.com/doc/v3/merchant/4012459282
     */

    public function notifyUrl(string $action, string $url = null)
    {
        $comApi = '/v3/merchant-service/complaint-notifications';

        switch ($action) {
            case 'get':
                $data = $this->get($comApi);
                break;
            case 'set':
                $param = [];
                $param['url'] = $url;
                $data = $this->post($comApi, $param, ['type' => 'post']);
                break;
            case 'update':
                $param = [];
                $param['url'] = $url;
                $data = $this->post($comApi, $param, ['type' => 'put']);
                break;
            case 'delete':
                $data = $this->get($comApi, null, ['type' => 'delete']);
                break;
            default:
                return '';
        }
        if (is_string($data)) return $data;

        return $data;
    }


}