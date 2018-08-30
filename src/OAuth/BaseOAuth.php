<?php
namespace Zodream\ThirdParty\OAuth;
/**
 * Created by PhpStorm.
 * User: zx648
 * Date: 2016/4/10
 * Time: 14:34
 */
use Zodream\Http\Http;
use Zodream\Http\Uri;
use Zodream\ThirdParty\ThirdParty;
use Zodream\Helpers\Str;
use Zodream\Service\Factory;
use Zodream\Infrastructure\Http\Request;

/**
 * Class BaseOAuth
 * @package Zodream\Domain\ThirdParty\OAuth
 *
 * @property string $identity
 * @property string $username
 * @property string $sex
 * @property string $avatar
 * @property string $email
 */
abstract class BaseOAuth extends ThirdParty  {

    protected $codeKey = 'code';

    public function getBaseHttp($url = null) {
        return $this->getHttp($url)
            ->parameters($this->get());
    }

    /**
     * @return Http
     */
    abstract public function getLogin();


    public function callback() {
        Factory::log()
            ->info(strtoupper($this->configKey).' CALLBACK: '.var_export($_GET, true));
        $state = app('request')->get('state');
        if (empty($state) || $state != Factory::session()->get('state')) {
            return false;
        }
        $code = app('request')->get($this->codeKey);
        if (empty($code)) {
            return false;
        }
        $this->set('code', $code);
        return true;
    }

    /**
     * 返回重定向到登录页面的链接
     * @return Uri
     * @throws \Exception
     */
    public function login() {
        $state = Str::randomNumber(7);
        Factory::session()->set('state', $state);
        $this->set('state', $state);
        return $this->getLogin()->getUrl();
    }

    /**
     * 获取用户信息
     * @return array
     */
    public abstract function info();
}