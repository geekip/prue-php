<?php

require_once 'version.php';

if (!defined("START_TIME")) define("START_TIME", microtime(true));
if (!defined("DIR_PRUE"))   define("DIR_PRUE", realpath(dirname(__FILE__)));
if (!defined("DIR_APP"))    define("DIR_APP", realpath("./"));
if (!defined("DIR_THEME"))  define("DIR_THEME", DIR_APP . DIRECTORY_SEPARATOR . "theme");

set_error_handler(function($errno, $errstr, $errfile, $errline){
	\Prue\Error::errorHandler($errno,$errstr,$errfile,$errline);
});

/** 设置异常截获函数 */
// set_exception_handler(function (\Throwable $exception) {
// 	echo '<pre><code>';
// 	echo '<h1>' . htmlspecialchars($exception->getMessage()) . '</h1>';
// 	echo htmlspecialchars($exception->__toString());
// 	echo '</code></pre>';
// 	exit;
// });

register_shutdown_function(function(){
	\Prue\Error::fatalHandler(error_get_last());
});

ini_set("display_errors", DEBUG ? "On" : "Off");
// ini_set("display_errors", "Off");
error_reporting(DEBUG ? E_ALL ^ E_NOTICE : 0);

spl_autoload_register(function (string $className){
	$className = str_replace("\\", DIRECTORY_SEPARATOR, $className);
	$class_file = DIR_APP . DIRECTORY_SEPARATOR . $className. '.php';
	if (file_exists($class_file)) {
		require_once $class_file;
		return;
	}
	if($className=="Prue"){
		$className = "Prue\Prue";
	}else{
		$className = preg_replace('/^Prue\//', '', $className);
	}
	$class_file = DIR_PRUE . DIRECTORY_SEPARATOR . $className. '.php';
	if (file_exists($class_file)) {
		require_once $class_file;
	}
}, true, true);

class Prue{
	private static $instance;
  public static $response;
  public static $request;
	public static $router;

	public function __construct(){}

	public static function init(array $config): Prue{
		if (!(self::$instance instanceof self)) {
			self::$instance = new self();
		}
		if (is_array($config)) self::setConfig($config);
		
		// init response
		// Response::getInstance()->enableAutoSendHeaders(false);

		// ob_start(function ($content) {
		// 	Response::getInstance()->sendHeaders();
		// 	return $content;
		// });

		return self::$instance;
	}

	public function dispatch(\Prue\Router $router): void{
		self::$router->dispatch();
	}

	public static function setConfig($key , $value=''): array {
		return \Prue\Config::set($key , $value='');
	}

	public static function getConfig(?String $key=null) {
		return \Prue\Config::get($key);
	}

  public static function response(): \Prue\Response {
		if (!(self::$response instanceof self)) {
			self::$response = new \Prue\Response();
		}
		return self::$response;
	}

  public static function request(): \Prue\Request {
		if (!(self::$request instanceof self)) {
			self::$request = new \Prue\Request();
		}
		return self::$request;
	}

  public static function router(...$options): \Prue\Router {
		if (!(self::$router instanceof self)) {
			self::$router = new \Prue\Router(...$options);
		}
		return self::$router;
	}
	
	public static function renderString(array $data=[], string $tpl){
		return \Prue\View::renderString($data, $tpl);
  }

  public static function render(array $data=[], string $tplPath){
		return \Prue\View::render($data, $tplPath);
  }

	public static function setAlia($key, ?string $value='') {
		return \Prue\Path::setAlia($key, $value);
	}

	public static function getAlia(string $key): string {  
		return \Prue\Path::getAlia($key);
	}

	public static function resolve(string ...$paths): string {
		return \Prue\Path::resolve(...$paths);
	} 

  public static function error(string $err, int $code): object{
    return new \Prue\Error($err, $code);
  }

	public function __clone(){}
}
