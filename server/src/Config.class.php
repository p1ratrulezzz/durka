<?php

namespace Configuration;

class Config {
  protected static $data = [];
  protected static $cache_prefix = __FILE__;

  public static function getVkServiceToken() {
    return static::get('vk_service_token');
  }

  public static function getCacheKey($key) {
    return hash('adler32', static::$cache_prefix) . ':' . $key;
  }

  public static function getVkApiVersion() {
    return static::get('vk_api_version');
  }

  public static function getVkWallId() {
    return static::get('vk_wall_id');
  }

  public static function set($prop, $value) {
    static::$data[$prop] = $value;
  }

  public static function get($prop, $default = NULL) {
    return static::$data[$prop] ?? $default;
  }
}