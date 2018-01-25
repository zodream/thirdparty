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

    protected $baseMap = [
        'http://dysmsapi.aliyuncs.com',
        [
            'RegionId' => 'cn-hangzhou',
            '#AccessKeyId',
            'Format' => 'JSON',
            'SignatureMethod' => 'HMAC-SHA1',
            'SignatureVersion' => '1.0',
            'SignatureNonce',
            '#Timestamp',
            '#Signature',
            'Version' => '2017-05-25',
        ],
        'POST'
    ];

    protected $apiMap = [
        'send' => [
            'Action' => 'SendSms',
            '#PhoneNumbers',
            '#SignName',
            '#TemplateCode',
            '#TemplateParam',
        ]
    ];

    public function getMap($name) {
        $data =$this->baseMap;
        $data[1] = array_merge($data, parent::getMap($name));
        return $data;
    }

    protected function getPostData($name, array $args) {
        $data = parent::getPostData($name, $args);
        $data['Signature'] = $this->sign($data);
        return $data;
    }

    public function send($mobile, $templateId, $data, $signName = '阿里云') {
        $args = $this->getJson('send', [
            'TemplateCode' => $templateId,
            'SignName' => $signName,
            'PhoneNumbers' => $mobile,
            'TemplateParam' => is_array($data) ?
                json_encode($data, JSON_FORCE_OBJECT) : $data,
            'Timestamp' => $this->getTimestamp()
        ]);
        if ($args['Code'] != 'OK') {
            throw new \Exception($args['Message']);
        }
        return $args;
    }

    public function sign(array $params) {
        $secret = $this->get('secret');
        if (empty($secret)) {
            throw  new \ErrorException('SECRET ERROR!');
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