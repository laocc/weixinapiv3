<?php
declare(strict_types=1);

namespace laocc\weiPay\library;

use esp\error\Error;

/**
 * 服务器，或直连商户，身份构造
 */
class Entity
{
    public string $mchID;//微信商户号
    public string $appID;//APPID

    public string $certKey;//私钥Key和证书串号
    public string $certSerial;

    public string $publicPath;
    public string $publicSerial;

    public array $merchant;//子商户

    public int $service = 1;//服务商类型，1直连商户，2普通服务商，4电商服务商，32自建支付中心
    public bool $improve = false;//商户性质提升，从二级子商户提升为直接商户

    public mixed $certEncrypt;

    /**
     * Entity constructor.
     * @param array $conf
     */
    public function __construct(array $conf)
    {
        $this->mchID = $conf['mchid'] ?? ($conf['mchID'] ?? ($conf['mchId'] ?? ''));
        if (!$this->mchID) throw new Error("传入数据需要含有微信支付商户mchID");

        $this->appID = $conf['appid'] ?? ($conf['appID'] ?? ($conf['appId'] ?? ''));
        if (!$this->appID) throw new Error("传入数据需要含有微信支付商户appID");

        if ($conf['custom'] ?? 0) {
            $this->service = 32;

        } else if (is_array($conf['merchant'] ?? '')) {
            $this->service = ($conf['ecommerce'] ?? 0) ? 4 : 2;
            $this->merchant = [
                'mchid' => $conf['merchant']['mchid'] ?? '',
                'appid' => $conf['merchant']['appid'] ?? '',
            ];
        } else {
            $this->service = 1;
        }

        $this->certKey = $conf['certKey'] ?? ($conf['key'] ?? ($conf['v3Key'] ?? ''));
        $this->certSerial = $conf['certSerial'] ?? ($conf['serial'] ?? '');
        $this->publicSerial = $conf['publicSerial'] ?? ($conf['public'] ?? '');
        $this->improve = ($conf['improve'] ?? false);

        if (isset($conf['cert'])) {
            if (is_string($conf['cert'])) {
                $privatePath = $conf['cert'];
                $publicPath = $conf['cert'];
            } else {
                $privatePath = $conf['cert']['private'] ?? '';
                $publicPath = $conf['cert']['public'] ?? '';
            }
        } else if (defined('_CERT')) {
            if (is_string(_CERT)) {
                $privatePath = _CERT;
                $publicPath = _CERT;
            } else {
                $privatePath = _CERT['private'] ?? '';
                $publicPath = _CERT['public'] ?? '';
            }
        } else {
            throw new Error('未指定商户私钥证书目录');
        }

        if (!$privatePath) throw new Error('未指定商户私钥证书目录');
        if (!$publicPath) throw new Error('未指定微信公钥证书目录');

        $privatePath = rtrim($privatePath, '/');
        $this->publicPath = rtrim($publicPath, '/');

        /**
         * 这里用到的密钥是在微信支付后台申请的商户私钥，或服务商私钥
         */
        $certFile = "{$privatePath}/{$this->certSerial}/apiclient_key.pem";
        if (!is_readable($certFile)) {
            throw new Error("商户证书文件{$certFile}不存在，请检查");
        }

        $this->certEncrypt = \openssl_get_privatekey(\file_get_contents($certFile));
    }

    public function __toString()
    {
        $value = [
            'service' => $this->service,
            'mchID' => $this->mchID,
            'appID' => $this->appID,
            'serial' => $this->certSerial,
            'public' => $this->publicSerial,
        ];
        if (isset($this->merchant)) $value['merchant'] = $this->merchant;
        return json_encode($value, 256 | 64 | 128);
    }


}