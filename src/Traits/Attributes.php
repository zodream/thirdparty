<?php
declare(strict_types=1);
namespace Zodream\ThirdParty\Traits;


trait Attributes {

    protected mixed $__attributes = [];

    /**
     * 合并数组并返回新数组
     * @param array $data
     * @return array
     */
    public function merge(array $data): array {
        return array_merge($this->__attributes, $data);
    }

    public function get(mixed $key = null, mixed $default = null): mixed {
        if (empty($key)) {
            return $this->__attributes;
        }
        if (!is_array($this->__attributes)) {
            $this->__attributes = (array)$this->__attributes;
        }
        if (method_exists($this, 'preProcessKey')) {
            $key = $this->preProcessKey($key);
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
    public function has(mixed $key = null): bool {
        if (is_null($key)) {
            return !empty($this->__attributes);
        }
        if (empty($this->__attributes)) {
            return false;
        }
        if (method_exists($this, 'preProcessKey')) {
            $key = $this->preProcessKey($key);
        }
        return isset($this->__attributes[$key]) || array_key_exists($key, $this->__attributes);
    }

    /**
     * 设置值
     * @param string|array $key
     * @param string $value
     * @return $this
     */
    public function set(mixed $key, mixed $value = null): static {
        if (is_object($key)) {
            $key = (array)$key;
        }
        if (is_array($key)) {
            foreach ($key as $k => $val) {
                if (method_exists($this, 'preProcessKey')) {
                    $k = $this->preProcessKey($k);
                }
                $this->__attributes[$k] = $val;
            }
            return $this;
        }
        if (empty($key)) {
            return $this;
        }
        if (method_exists($this, 'preProcessKey')) {
            $key = $this->preProcessKey($key);
        }
        $this->__attributes[$key] = $value;
        return $this;
    }

    public function offsetGet($offset) {
        return $this->get($offset);
    }

    public function __get($attribute) {
        return $this->get($attribute);
    }

    public function offsetSet($offset, $value): void {
        $this->set($offset, $value);
    }

    public function __set($attribute, $value): void {
        $this->set($attribute, $value);
    }
}