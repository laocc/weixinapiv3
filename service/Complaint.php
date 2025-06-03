<?php

namespace laocc\weiPay\service;

use laocc\weiPay\ApiV3Base;

class Complaint extends ApiV3Base
{

    public function notify()
    {
        return $this->notifyDecrypt();
    }

    public function reply(array $data): bool|string
    {
        $param = [];
        $param['complainted_mchid'] = $data['mchid'];
        $param['response_content'] = $data['content'];
//        $param['response_images'] = [];
        $option = ['type' => 'post', 'returnHttp' => true];
        $request = $this->post("/v3/merchant-service/complaints-v2/{$data['complaint_id']}/response", $param, $option);
        $code = (int)$request->info('code');
        if ($code >= 200 and $code < 300) return true;
        return $request->error();
    }

    public function complete(array $data): bool|string
    {
        $param = [];
        $param['complainted_mchid'] = $data['mchid'];
        $option = ['type' => 'post', 'returnHttp' => true];
        $request = $this->post("/v3/merchant-service/complaints-v2/{$data['complaint_id']}/complete", $param, $option);
        $code = (int)$request->info('code');
        if ($code >= 200 and $code < 300) return true;
        return $request->error();
    }

    public function refund(array $data): bool|string
    {
        if ($data['action'] === true) $data['action'] = 'APPROVE';
        if ($data['action'] === false) $data['action'] = 'REJECT';
        $data['action'] = strtoupper($data['action']);
        if (!in_array($data['action'], ['APPROVE', 'REJECT'])) return 'action需为REJECT|APPROVE，或true|false';

        $param = [];
        $param['action'] = $data['action'];//REJECT: 拒绝退款 APPROVE: 同意退款
        $param['launch_refund_day'] = intval($data['day'] ?? 0);//预计将在多少个工作日内能发起退款
        $param['reject_reason'] = $data['reason'] ?? '';//在拒绝退款时返回拒绝退款的原因
        $param['remark'] = $data['remark'] ?? '';//任何需要向微信支付客服反馈的信息
//        $param['reject_media_list'] = [];
        if (!$param['launch_refund_day']) unset($param['launch_refund_day']);
        if (empty($param['reject_reason'])) unset($param['reject_reason']);
        if (empty($param['remark'])) unset($param['remark']);

        $option = ['type' => 'post', 'returnHttp' => true];
        $request = $this->post("/v3/merchant-service/complaints-v2/{$data['complaint_id']}/update-refund-progress", $param, $option);
        $code = (int)$request->info('code');
        if ($code >= 200 and $code < 300) return true;
        return $request->error();
    }

    public function download(array $data)
    {
        $time = $data['time'] ?? (strtotime('-30 day'));
        $begin = date('Y-m-d', $time);
        $end = date('Y-m-d', (strtotime($begin) + (86400 * 30) - 1));
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

    public function image(array $data): array|string
    {
        $option = ['type' => 'get', 'returnHttp' => 1];
        $image = $this->get("/v3/merchant-service/images/" . urlencode($data['media_id']), null, $option);
        return ['src' => 'data:image/png;base64,' . base64_encode($image->html())];
    }

    public function upload(array $data)
    {
        $post = [];
        $post['file'] = fread(fopen($data['file'], "rb"), filesize($data['file']));
        $post['meta'] = [
            'filename' => \basename($data['file']),
            'sha256' => hash_file('sha256', $data['file']),
        ];
        return $this->post("/v3/merchant-service/images/upload", $post, ['type' => 'upload']);
    }

    public function history(array $data)
    {
        $param = [];
        $param['limit'] = 300;
        $param['offset'] = 0;
        return $this->get("/v3/merchant-service/complaints-v2/{$data['id']}/negotiation-historys", $param, ['type' => 'get']);
    }


    /**
     * 文档：
     *
     * https://pay.weixin.qq.com/wiki/doc/apiv3/apis/chapter10_2_11.shtml
     *
     * https://pay.weixin.qq.com/doc/v3/merchant/4012459282
     */
    public function notifyUrl(string $method, string $url = null)
    {
        $comApi = '/v3/merchant-service/complaint-notifications';

        switch ($method) {
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