<?php

namespace laocc\weiPay\auto;

use laocc\weiPay\library\Entity;
use laocc\weiPay\library\PayFace;

class Pay implements PayFace
{
    protected Entity $entity;

    public function __construct(Entity $entity)
    {
        $this->entity = $entity;
    }

    /**
     * 受理通知数据，验签，并解密
     * @param array &$data
     * @return array
     */
    public function notify(array &$data): array
    {
        if ($this->entity->service) {
            $pay = new \laocc\weiPay\service\Pay($this->entity);
        } else {
            $pay = new \laocc\weiPay\merchant\Pay($this->entity);
        }

        $value = $pay->notifyDecrypt($data);

        $params = [];
        $params['success'] = $value['trade_state'] === 'SUCCESS';
        $params['waybill'] = $value['transaction_id'];
        $params['time'] = strtotime($value['success_time']);
        $params['state'] = strtolower(substr($value['trade_state'], -20));
        $params['amount'] = intval($value['amount']['total']);
        return $params;
    }


    /**
     * app支付
     *
     * @param array $params
     * @return array|string
     */
    public function app(array $params): array|string
    {
        if ($this->entity->service) {
            $pay = new \laocc\weiPay\service\Pay($this->entity);
        } else {
            $pay = new \laocc\weiPay\merchant\Pay($this->entity);
        }
        return $pay->app($params);
    }

    /**
     * 发起公众号、小程序支付
     * 服务商和直连都可用，取决于 $this->entity->service
     *
     * @param array $params
     * @return array|string
     */
    public function jsapi(array $params): array|string
    {
        if ($this->entity->service) {
            $pay = new \laocc\weiPay\service\Pay($this->entity);
        } else {
            $pay = new \laocc\weiPay\merchant\Pay($this->entity);
        }
        return $pay->jsapi($params);
    }

    public function h5(array $params): array|string
    {
        if ($this->entity->service) {
            $pay = new \laocc\weiPay\service\Pay($this->entity);
        } else {
            $pay = new \laocc\weiPay\merchant\Pay($this->entity);
        }
        return $pay->h5($params);
    }


    /**
     * @param array $params
     * @return array|string
     */
    public function query(array $params): array|string
    {
        if ($this->entity->service) {
            $pay = new \laocc\weiPay\service\Pay($this->entity);
        } else {
            $pay = new \laocc\weiPay\merchant\Pay($this->entity);
        }
        return $pay->query($params);
    }

}