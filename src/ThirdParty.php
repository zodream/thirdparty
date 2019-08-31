<?php
namespace Zodream\ThirdParty;
/**
 * 第三方接口
 * Created by PhpStorm.
 * User: zx648
 * Date: 2016/5/13
 * Time: 11:44
 */
use Zodream\Helpers\Str;
use Zodream\Http\Http;
use Zodream\Http\Uri;

abstract class ThirdParty {

    protected $__attributes = [];

    /**
     * KEY IN CONFIG
     * @var string
     */
    protected $configKey;

    public function __construct($config = array()) {
        if (empty($config)) {
            if (function_exists('config')) {
                $this->set(config($this->configKey));
            }
            return;
        }
        if (array_key_exists($this->configKey, $config)
            && is_array($config[$this->configKey])) {
            $this->set($config[$this->configKey]);
            return;
        }
        $this->set($config);
    }

    /**
     * GET NAME
     * @return string
     */
    public function getName() {
        return $this->configKey;
    }

    /**
     * 生成新的请求
     * @param Uri|string $url
     * @return Http
     */
    public function getHttp($url = null) {
        return new Http($url);
    }

    /**
     * @param $name
     * @param array ...$args
     * @return mixed|null
     * @throws \Exception
     */
    public function invoke($name, ...$args) {
        if (method_exists($this, $name)) {
            return $this->{$name}(...$args);
        }
        $method = 'get'.Str::studly($name);
        if (method_exists($this, $method)) {
            throw new \Exception('error api '.$name);
        }
        $args = array_merge($this->get(), $args);
        /** @var Http $http */
        $http = $this->{$method}(...$args);
        return $http->text();
    }

    /**
     * 合并数组并返回新数组
     * @param array $data
     * @return array
     */
    public function merge(array $data) {
        return array_merge($this->__attributes, $data);
    }

    public function get($key = null, $default = null) {
        if (empty($key)) {
            return $this->__attributes;
        }
        if (!is_array($this->__attributes)) {
            $this->__attributes = (array)$this->__attributes;
        }
        if ($this->has($key)) {
            return $this->__attributes[$key];
        }
        return $default;
    }

    /**
     * 判断是否有
     * @param string|null $key 如果为null 则判断是否有数据
     * @return bool
     */
    public function has($key = null) {
        if (is_null($key)) {
            return !empty($this->__attributes);
        }
        if (empty($this->__attributes)) {
            return false;
        }
        return isset($this->__attributes[$key]) || array_key_exists($key, $this->__attributes);
    }

    /**
     * 设置值
     * @param string|array $key
     * @param string $value
     * @return $this
     */
    public function set($key, $value = null) {
        if (is_object($key)) {
            $key = (array)$key;
        }
        if (is_array($key)) {
            $this->__attributes = array_merge($this->__attributes, $key);
            return $this;
        }
        if (empty($key)) {
            return $this;
        }
        $this->__attributes[$key] = $value;
        return $this;
    }

    /**
     * _call
     * 魔术方法，做api调用转发
     * @param string $name 调用的方法名称
     * @param $args
     * @return array          返加调用结果数组
     * @throws \Exception
     * @since 5.0
     */
    public function __call($name, $args) {
        return $this->invoke($name, ...$args);
    }

    public static function __callStatic($method, $parameters) {
        return call_user_func_array([
            new static, $method], $parameters);
    }

    /**
     * 获取缓存或设置缓存
     * @param $key
     * @param callable $cb
     * @return mixed
     * @throws \Exception
     */
    public static function getOrSetCache($key, callable $cb) {
        if (!function_exists('cache')) {
            return call_user_func($cb, function ($data, $duration) {
                return $data;
            });
        }
        if (cache()->has($key)) {
            return cache()->get($key);
        }
        return call_user_func($cb, function ($data, $duration) use ($key) {
            cache()->set($key, $data, $duration);
            return $data;
        });
    }
}