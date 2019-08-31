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

    protected $stateKey = 'state';

    public function getBaseHttp($url = null) {
        return $this->getHttp($url)
            ->parameters($this->get());
    }

    /**
     * @return Http
     */
    abstract public function getLogin();

    /**
     * @return bool
     * @throws \Exception
     */
    public function callback() {
        Http::log(strtoupper($this->configKey).' CALLBACK: '.var_export($_GET, true));
        $state = isset($_GET[$this->stateKey]) ? $_GET[$this->stateKey] : null;
        if (empty($state)) {
            return false;
        }
        if (function_exists('session')
            && $state !== session('state')) {
            return false;
        }
        $code = isset($_GET[$this->codeKey]) ? $_GET[$this->codeKey] : null;
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
        if (function_exists('session')) {
            session([
                'state' => $state
            ]);
        }
        $this->set('state', $state);
        return $this->getLogin()->getUrl();
    }

    /**
     * 获取用户信息
     * @return array
     */
    public abstract function info();
}