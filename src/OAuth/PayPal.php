<?php
namespace Zodream\ThirdParty\OAuth;
/**
 * Created by PhpStorm.
 * User: zx648
 * Date: 2016/9/8
 * Time: 9:44
 */
class PayPal extends BaseOAuth {

    const LIVE = 'live';
    const SANDBOX = 'sandbox';

    protected string $configKey = 'paypal';

    protected array $baseUrl = [
        self::LIVE => 'https://www.paypal.com/',
        self::SANDBOX => 'https://www.sandbox.paypal.com/'
    ];

    public function baseUriHttp($path, $maps = []) {
        return $this->getBaseHttp()
            ->url($this->baseUrl[$this->mode].$path, $maps);
    }

    public function getLogin() {
        return $this->baseUriHttp('signin/authorize', [
            '#client_id',
            'response_type' => 'code',
            'scope' => 'openid profile address email phone https://uri.paypal.com/services/paypalattributes https://uri.paypal.com/services/expresscheckout',
            '#redirect_uri',
            'nonce',
            'state'
        ]);
    }

    public function getAccess() {
        return $this->getBaseHttp('webapps/auth/protocol/openidconnect/v1/identity/tokenservice')
            ->maps([
                'grant_type' => 'authorization_code',
                '#code',
                '#redirect_uri'
            ])->setOption([
                CURLOPT_VERBOSE        => 1,
                CURLOPT_RETURNTRANSFER => 1,
                CURLOPT_SSL_VERIFYPEER => FALSE
            ])->setHeader([
                'Content-Type' => 'application/x-www-form-urlencoded',
                'Authorization' => 'Basic ' . base64_encode($this->get('client_id') .
                        ':' . $this->get('client_secret'))
            ]);
    }

    public function getRefresh() {
        return $this->baseUriHttp('webapps/auth/protocol/openidconnect/v1/identity/tokenservice',
            [
                'grant_type' => 'refresh_token',
                '#refresh_token',
                'scope'
            ]);
    }

    public function getInfo() {
        return $this->baseUriHttp('webapps/auth/protocol/openidconnect/v1/identity/openidconnect/userinfo',
            [
                'schema' => 'openid',
                '#access_token'
            ])->setHeader([
                'Authorization' => "Bearer " . $this->get('access_token'),
                'Content-Type' => 'x-www-form-urlencoded'
            ]);
    }

    /**
     *
     * @var string
     */
    protected string $mode = self::SANDBOX;

    public function __construct(array $config = array()) {
        parent::__construct($config);
        $this->http->setOpts(array(
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_SSL_VERIFYPEER => FALSE,
            CURLOPT_SSLVERSION => 3
        ));
        $this->setMode($this->get('mode', self::SANDBOX));
    }

    /**
     * IS TEST OR LIVE
     * @param string $arg
     * @return $this
     */
    public function setMode(string $arg) {
        $this->mode = strtolower($arg) === self::LIVE ? self::LIVE : self::SANDBOX;
        return $this;
    }

    /**
     * @return bool|mixed
     * @throws \Exception
     */
    public function callback() {
        if (!parent::callback()) {
            return false;
        }
        $access = $this->getAccess()->json();
        $this->set($access);
        return $access;
    }

    /**
     * 获取用户信息
     * @return array|false
     * @throws \Exception
     */
    public function info() {
        $user = $this->getInfo()->json();
        if (!is_array($user) || !array_key_exists('user_id', $user)) {
            return false;
        }
        $user['username'] = $user['name'];
        $user['avatar'] = $user['picture'];
        $user['sex'] = $user['gender'];
        $this->set($user);
        return $user;
    }
}