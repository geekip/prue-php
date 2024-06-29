<?php
namespace Prue;

class Config{
  public static $config = [ 
    'DEBUG' => true,
    'USE_SESSION' => true,
    'CACHE_DATA'=>true,
    'CHARSET' => 'UTF-8',
    'TIMEZONE' => 'PRC',
    'CROSS_DOMAIN' => false
  ];

  //获取
  public static function get( ?String $key=null) : array {
    $config = self::$config;
    if(isset($key) && $key !=''){
      $config = $config[$key];
    }
    return $config;
  }

  //设置
  public static function set( $key , $value='') : array {
    $config = [];
    if(is_array($key)){
      $config = $key;
    }else if(isset($key) && $key !=''){
      $config = [$key=>$value];
    }
    if(!empty($config)){
      self::$config = array_merge(self::$config, $config);
    }
    return self::$config;
  }
}