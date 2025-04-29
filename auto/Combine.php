<?php

namespace laocc\weiPay\auto;

use laocc\weiPay\library\Entity;
use laocc\weiPay\library\PayFace;
use laocc\weiPay\merchant\Combine as mCombine;
use laocc\weiPay\service\Combine as sCombine;

/**
 * 合单支付
 */
class Combine implements PayFace
{

    protected Entity $entity;

    public function __construct(Entity $entity)
    {
        $this->entity = $entity;
    }

    private function createPay(): mCombine|sCombine
    {
        if ($this->entity->service) {
            return new sCombine($this->entity);
        } else {
            return new mCombine($this->entity);
        }
    }

    /**
     * 受理通知数据，验签，并解密
     * @return array|string
     */
    public function notify(): array|string
    {
        return $this->createPay()->notify();
    }


    /**
     * app支付
     *
     * @param array $params
     * @return array|string
     */
    public function app(array $params): array|string
    {
        return $this->createPay()->app($params);
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
        return $this->createPay()->jsapi($params);
    }

    public function h5(array $params): array|string
    {
        return $this->createPay()->h5($params);
    }


    /**
     * @param array $params
     * @return array|string
     */
    public function query(array $params): array|string
    {
        return $this->createPay()->query($params);
    }

    /**
     * native，也就是二维码支付
     *
     * @param array $params
     * @return array|string
     */
    public function native(array $params): array|string
    {
        return $this->createPay()->native($params);
    }


    /**
     * 关闭订单
     *
     * @param array $params
     * @return array|string
     */
    public function close(array $params): array|string
    {
        return $this->createPay()->close($params);
    }
}