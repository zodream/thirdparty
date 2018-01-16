<?php
namespace Zodream\ThirdParty\SMS;

use Zodream\ThirdParty\ThirdParty;

/**
 * example: [
'AccessKeyId' => '',
'secret' => '',
]
 * 阿里云短信
 * @package Zodream\ThirdParty\SMS
 */
class ALiYun extends ThirdParty {

    public function getBaseHttp() {
        return $this->getHttp('http://dysmsapi.aliyuncs.com')
            ->maps([
                'RegionId' => 'cn-hangzhou',
                '#AccessKeyId',
                'Format' => 'JSON',
                'SignatureMethod' => 'HMAC-SHA1',
                'SignatureVersion' => '1.0',
                'SignatureNonce',
                '#Timestamp',
                '#Signature',
                'Version' => '2017-05-25',
            ])->encode(function($data) {
                $data['Signature'] = $this->sign($data);
                return $data;
            })->parameters($this->get());
    }

    public function getSend() {
        return $this->getBaseHttp()
            ->appendMaps([
                'Action' => 'SendSms',
                '#PhoneNumbers',
                '#SignName',
                '#TemplateCode',
                '#TemplateParam',
            ]);
    }

    /**
     * @param $mobile
     * @param $templateId
     * @param $data
     * @param string $signName
     * @return mixed
     * @throws \Exception
     */
    public function send($mobile, $templateId, $data, $signName = '阿里云') {
        $args = $this->getSend()->parameters([
            'TemplateCode' => $templateId,
            'SignName' => $signName,
            'PhoneNumbers' => $mobile,
            'TemplateParam' => is_array($data) ?
                json_encode($data, JSON_FORCE_OBJECT) : $data,
            'Timestamp' => $this->getTimestamp()
        ])->json();
        if ($args['Code'] != 'OK') {
            throw new \Exception($args['Message']);
        }
        return $args;
    }

    /**
     * @param array $params
     * @return string
     * @throws \Exception
     */
    public function sign(array $params) {
        $secret = $this->get('secret');
        if (empty($secret)) {
            throw new \Exception('SECRET ERROR!');
        }
        ksort($params);
        $stringToSign = 'GET&%2F&'.
            urlencode(http_build_query(
            $params, null,
                    '&', PHP_QUERY_RFC3986));
        return base64_encode(hash_hmac('sha1',
            $stringToSign, $secret.'&', true));
    }

    /**
     * @return false|string
     */
    protected function getTimestamp() {
        $timezone = date_default_timezone_get();
        date_default_timezone_set('GMT');
        $timestamp = date('Y-m-d\TH:i:s\Z');
        date_default_timezone_set($timezone);
        return $timestamp;
    }
}