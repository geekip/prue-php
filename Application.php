<?php
namespace Prue;

if(!defined("START_TIME")) define("START_TIME",microtime(true));
if(!defined("DS")) define("DS",DIRECTORY_SEPARATOR);
if(!defined("DIR_ROOT")) define("DIR_ROOT", strtr(dirname(dirname(__FILE__)),"\\",DS));
if(!defined("DIR_APP")) define("DIR_APP", strtr(getcwd(),"\\",DS));

// 错误屏蔽
ini_set("display_errors", DEBUG ? "On" : "Off");
error_reporting( DEBUG ? E_ALL ^ E_NOTICE : 0 );

// 自动加载
spl_autoload_register(function (string $className) : void {
  $app_file = DIR_APP.DS.str_replace("\\", DS, $className) . ".php";
  $root_file = DIR_ROOT.DS.str_replace("\\", DS, $className) . ".php";
  if (file_exists($app_file)) {
    @include_once $app_file;
  } else if (file_exists($root_file)) {
    @include_once $root_file;
  }
}, true, true);

class Application {
  
  // 实例
  private static $instance;
  private Router $router;

  // 构造
  private function __construct() { }

  // 初始化
  public static function init(array $config) : object {
    // 单例
    if(!(self::$instance instanceof self)) {
      self::$instance = new self();
    }
    if(is_array($config)) Config::set($config);
    
    return self::$instance;
  }

  public function dispatch(Router $router) : void {
    $this->router = $router ?? new Router();
    $this->router->dispatch();
  }

  //防止克隆
  public function __clone() { }
}

