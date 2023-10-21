<?php
declare(strict_types=1);
namespace Zodream\ThirdParty\SMS;

use Zodream\Http\Http;
use Zodream\ThirdParty\ThirdParty;

/**
 * example: [
'app_key' => '',
'secret' => '',
 ]
 * 阿里大于短信
 * @package Zodream\ThirdParty\SMS
 */
class ALiDaYu extends ThirdParty implements IShortMessageProtocol {


    public function getBaseHttp() {
        return $this->getHttp('http://gw.api.taobao.com/router/rest')
            ->maps([
                '#app_key',
                'target_app_key',
                'sign_method' => 'md5',
                'sign',
                'session',
                '#timestamp', // date('Y-m-d H:i:s')
                'format' => 'json',
                'v' => '2.0',
                'partner_id',
                'simplify',
            ])->encode(function($data) {
                $data['sign'] = $this->sign($data);
                return $data;
            })->parameters($this->get());
    }

    public function getSend() {
        return $this->getBaseHttp()->appendMaps([
            'method' => 'alibaba.aliqin.fc.sms.num.send',
            'extend',
            'sms_type' => 'normal',
            '#sms_free_sign_name',
            'sms_param',   //值必须为字符串
            '#rec_num',
            '#sms_template_code'
        ]);
    }

    public function getQuery() {
        return $this->getBaseHttp()->appendMaps([
            'method' => 'alibaba.aliqin.fc.sms.num.query',
            'extend',
            'sms_type' => 'normal',
            '#sms_free_sign_name',
            'sms_param',   //值必须为字符串
            '#rec_num',
            '#sms_template_code'
        ]);
    }

    public function getVoice() {
        return $this->getBaseHttp()
            ->appendMaps([
                'method' => 'alibaba.aliqin.fc.voice.num.doublecall',
                'session_time_out',
                'extend',
                '#caller_num',
                '#caller_show_num',
                '#called_num',
                '#called_show_num'
            ]);
    }

    public function getTts() {
        return $this->getBaseHttp()
            ->appendMaps([
                'method' => 'alibaba.aliqin.fc.tts.num.singlecall',
                'extend',
                'tts_param',
                '#called_num',
                '#called_show_num',
                '#tts_code'
            ]);
    }

    public function getSingleCall() {
        return $this->getBaseHttp()
            ->appendMaps([
                'method' => 'alibaba.aliqin.fc.voice.num.singlecall',
                'extend',
                '#called_num',
                '#called_show_num',
                '#voice_code'
            ]);
    }

    public function isOnlyTemplate(): bool {
        return true;
    }


    /**
     * @param $mobile
     * @param $templateId
     * @param $data
     * @param string $signName
     * @return bool
     * @throws \Exception
     */
    public function send(string $mobile, string $templateId, array $data,
                         string $signName = '阿里大于'): bool|string {
        $args = $this->getSend()->parameters([
            'sms_template_code' => $templateId,
            'sms_free_sign_name' => $signName,
            'rec_num' => $mobile,
            'sms_param' => json_encode($data),
            'timestamp' => date('Y-m-d H:i:s')
        ])->json();
        if (array_key_exists('error_response', $args)) {
            throw new \Exception($args['error_response']['msg']);
        }
        return array_key_exists('alibaba_aliqin_fc_sms_num_send_response', $args);
    }

    /**
     * @param array $data
     * @return string
     * @throws \Exception
     */
    public function sign(array $data) {
        $secret = $this->get('secret');
        if (empty($secret)) {
            throw new \Exception('SECRET ERROR!');
        }
        ksort($data);
        $arg = '';
        foreach ($data as $key => $item) {
            if (Http::isEmpty($item) || $key == 'sign') {
                continue;
            }
            $arg .= $key.$item;
        }
        return strtoupper(md5($secret.$arg.$secret));
    }
}