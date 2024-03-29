<?php
namespace Zodream\ThirdParty\OAuth;
/**
 * Created by PhpStorm.
 * User: zx648
 * Date: 2016/5/21
 * Time: 15:52
 */
class TaoBao extends BaseOAuth {

    protected string $configKey = 'taobao';

    public function getLogin() {
        return $this->getBaseHttp()
            ->url('https://oauth.taobao.com/authorize', [
                '#client_id',
                '#redirect_uri',
                'response_type' => 'code',
                'scope',
                'view' => 'web', //web、tmall或wap
                'state'
            ]);
    }

    public function getAccess() {
        return $this->getBaseHttp('https://oauth.taobao.com/token')
            ->maps([
                '#client_id',
                '#client_secret',
                '#code',
                '#redirect_uri',
                'view' => 'web',   //web、tmall或wap
                'grant_type' => 'authorization_code',
                'state'
            ]);
    }


    /**
     * @return bool|mixed
     * @throws \Exception
     */
    public function callback() {
        parent::callback();
        /**
         * access_token

        用户授权令牌，等价于Sessionkey

        token_type

        授权令牌类型，暂做保留参数备用

        expires_in

        授权令牌有效期，以秒为单位

        refresh_token

        刷新令牌，当授权令牌过期时，可以刷新access_token，如果有获取权限则返回

        re_expires_in

        刷新令牌的有效期

        hra_expires_in

        高危API有效期（短授权相关）

        taobao_user_id

        用户ID（子账号相关）

        taobao_user_nick

        用户nick

        taobao_sub_user_id

        子账号用户ID

        taobao_sub_user_nick

        子账号用户nick

        mobile_token

        无线端的ssid（对应于view=wap）
         */
        $access = $this->getAccess()->json();
        $this->set($access);
        return $access;
    }

    /**
     * 获取用户信息
     * @return array
     */
    public function info() {
        $this->identity = $this->get('taobao_user_id');
        $this->username = urldecode($this->get('taobao_user_nick'));
        return $this->get();
    }
}