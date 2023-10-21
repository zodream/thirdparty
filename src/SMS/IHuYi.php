<?php
declare(strict_types=1);
namespace Zodream\ThirdParty\SMS;

/**
 * Created by PhpStorm.
 * User: zx648
 * Date: 2016/5/12
 * Time: 15:13
 */
use Zodream\ThirdParty\ThirdParty;

/**
 * Class IHuYi
 * http://www.ihuyi.com/
 * @package Zodream\Domain
 * @property string $template 短信模板 {code} 代替验证码
 * @property string $account 账户
 * @property string $password 密码 可以是32位md5加密过的
 */
class IHuYi extends ThirdParty implements IShortMessageProtocol  {

    protected string $configKey = 'sms';

    protected $__attributes = array(
        'template' => '您的验证码是：{code}。请不要把验证码泄露给其他人。'
    );

    public function getSend() {
        return $this->getHttp()
            ->url('https://106.ihuyi.com/webservice/sms.php', [
                'format' => 'json',
                '#method'
            ])->maps([
                '#account',
                '#password',
                'mobile',
                'content'
            ]);
    }

    public function isOnlyTemplate(): bool {
        return false;
    }

    /**
     * 发送短信
     * @param $mobile
     * @param $content
     * @return bool
     * @throws \Exception
     */
    public function send(string $mobile, string $templateId, array $data, string $signName = ''): bool|string {
        $data = $this->getSend()->parameters([
            'mobile' => $mobile,
            'content' => $templateId,
            'method' => 'Submit'
        ])->json();
        if (intval($data['code']) === 2) {
            return (string)$data['smsid'];
        }
        throw new \Exception($data['msg']);
    }

    /**
     * 发送验证短信
     * @param string|integer $mobile
     * @param string|integer $code
     * @return bool|integer false|短信ID
     * @throws \Exception
     */
    public function sendCode(string $mobile, string|int $code) {
        return $this->send($mobile, str_replace('{code}', $code, $this->get('template')), []);
    }

    /**
     * 余额查询
     * @return bool|int
     * @throws \Exception
     */
    public function balance() {
        $data = $this->getSend()->parameters([
            'method' => 'GetNum'
        ])->json();
        if ($data['code'] == 2) {
            return $data['num'];
        }
        throw new \Exception($data['msg']);
    }
}