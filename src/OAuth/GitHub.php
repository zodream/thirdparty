<?php
namespace Zodream\ThirdParty\OAuth;
/**
 * Created by PhpStorm.
 * User: zx648
 * Date: 2016/5/13
 * Time: 11:33
 */
class GitHub extends BaseOAuth {

    protected string $configKey = 'github';

    public function getLogin() {
        return $this->getBaseHttp()
            ->url('https://github.com/login/oauth/authorize', [
                '#client_id',
                '#redirect_uri',
                'scope', //=> 'user',
                'state',
                'allow_signup'
            ]);
    }

    public function getAccess() {
        return $this->getBaseHttp('https://github.com/login/oauth/access_token')
            ->maps([
                '#client_id',
                '#client_secret',
                '#code',
                'redirect_uri',
                'state'
            ])->setHeader([
                'Accept' => 'application/json'
            ]);
    }

    public function getInfo() {
        return $this->getBaseHttp()
            ->url('https://api.github.com/user', [
                '#access_token',
            ])
            ->setHeader([
                'Accept' => 'application/vnd.github.v3+json',
                'User-Agent' => 'zodream', // 必须有请求头
                'Authorization' => 'token '.$this->get('access_token')
            ])
            ;
    }

    /**
     * @return array|false
     * @throws \Exception
     */
    public function callback() {
        parent::callback();
        $access = $this->getAccess()->json();
        if (!array_key_exists('access_token', $access)) {
            return false;
        }
        $this->set($access);
        $user = $this->getInfo()->json();
        $user['identity'] = $user['id'];
        $user['username'] = $user['login'];
        $user['avatar'] = $user['avatar_url'];
        $this->set($user);
        return $access;
    }

    /**
     * @return array|mixed
     * @throws \Exception
     */
    public function info() {
        return $this->get();
    }
}