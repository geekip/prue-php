<?php
// https://www.jb51.net/article/81706.htm
namespace Prue;

class Http
{

	private $_requestUri;
	private $_pathInfo;
	private $_scriptFile;
	private $_scriptUrl;
	private $_hostInfo;
	private $_baseUrl;
	private $_securePort;
	private $_port;
  private $_cookies;
  private $_preferredLanguage;
  private $_deleteParams;
  private $_putParams;

  public function __construct(){}

  public function getParam($name){
    return isset($_GET[$name]) ? $_GET[$name] : (isset($_POST[$name]) ? $_POST[$name] : null);
  }
  public function getQuery($name){
    return isset($_GET[$name]) ? $_GET[$name] : null;
  }
  public function getPost($name){
    return isset($_POST[$name]) ? $_POST[$name] : null;
  }
  public function getDelete($name){
    if($this->_deleteParams===null){
      $this->_deleteParams=$this->getIsDeleteRequest() ? $this->getRestParams() : array();
    }
    return isset($this->_deleteParams[$name]) ? $this->_deleteParams[$name] : null;
  }
  public function getPut($name){
    if($this->_putParams===null){
      $this->_putParams=$this->getIsPutRequest() ? $this->getRestParams() : array();
    }
    return isset($this->_putParams[$name]) ? $this->_putParams[$name] : null;
  }
  protected function getRestParams(){
    $result=array();
    if(function_exists('mb_parse_str')){
      mb_parse_str(file_get_contents('php://input'), $result);
    }else{
      parse_str(file_get_contents('php://input'), $result);
    } 
    return $result;
  }
  public function getRequestType(){
    return strtoupper(isset($_SERVER['REQUEST_METHOD'])?$_SERVER['REQUEST_METHOD']:'GET');
  }
  public function getIsPostRequest(){
    return isset($_SERVER['REQUEST_METHOD']) && !strcasecmp($_SERVER['REQUEST_METHOD'],'POST');
  }
  public function getIsDeleteRequest(){
    return isset($_SERVER['REQUEST_METHOD']) && !strcasecmp($_SERVER['REQUEST_METHOD'],'DELETE');
  }
  public function getIsPutRequest(){
    return isset($_SERVER['REQUEST_METHOD']) && !strcasecmp($_SERVER['REQUEST_METHOD'],'PUT');
  }
  public function getIsAjaxRequest(){
    return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH']==='XMLHttpRequest';
  }

  public function getPreferredLanguage(){
    if($this->_preferredLanguage===null){
      if(isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) && ($n=preg_match_all('/([\w\-_]+)\s*(;\s*q\s*=\s*(\d*\.\d*))?/',$_SERVER['HTTP_ACCEPT_LANGUAGE'],$matches))>0){
        $languages=array();
        for($i=0;$i<$n;++$i)
          $languages[$matches[1][$i]]=empty($matches[3][$i]) ? 1.0 : floatval($matches[3][$i]);
        arsort($languages);
        foreach($languages as $language=>$pref)
          return $this->_preferredLanguage=$language;
      }
      return $this->_preferredLanguage=false;
    }
    return $this->_preferredLanguage;
  }

  public function getScriptFile() {
    if($this->_scriptFile!==null){
      return $this->_scriptFile;
    }else{
      return $this->_scriptFile=realpath($_SERVER['SCRIPT_FILENAME']);
    }
  }

	public function getScriptUrl(){
		if ($this->_scriptUrl === null) {
			$scriptName = basename($_SERVER['SCRIPT_FILENAME']);
			if (basename($_SERVER['SCRIPT_NAME']) === $scriptName) {
				$this->_scriptUrl = $_SERVER['SCRIPT_NAME'];
			} elseif (basename($_SERVER['PHP_SELF']) === $scriptName) {
				$this->_scriptUrl = $_SERVER['PHP_SELF'];
			} elseif (isset($_SERVER['ORIG_SCRIPT_NAME']) && basename($_SERVER['ORIG_SCRIPT_NAME']) === $scriptName) {
				$this->_scriptUrl = $_SERVER['ORIG_SCRIPT_NAME'];
			} elseif (($pos = strpos($_SERVER['PHP_SELF'], '/' . $scriptName)) !== false) {
				$this->_scriptUrl = substr($_SERVER['SCRIPT_NAME'], 0, $pos) . '/' . $scriptName;
			} elseif (isset($_SERVER['DOCUMENT_ROOT']) && strpos($_SERVER['SCRIPT_FILENAME'], $_SERVER['DOCUMENT_ROOT']) === 0) {
				$this->_scriptUrl = str_replace('\\', '/', str_replace($_SERVER['DOCUMENT_ROOT'], '', $_SERVER['SCRIPT_FILENAME']));
			} else {
				throw new Error("HttpRequest is unable to determine the entry script URL.");
			}
		}
		return $this->_scriptUrl;
	}

	public function getRequestUri(){
		if ($this->_requestUri === null) {
			if (isset($_SERVER['HTTP_X_REWRITE_URL'])) { // IIS
				$this->_requestUri = $_SERVER['HTTP_X_REWRITE_URL'];
			} elseif (isset($_SERVER['REQUEST_URI'])) {
				$this->_requestUri = $_SERVER['REQUEST_URI'];
				if (isset($_SERVER['HTTP_HOST']) && strpos($this->_requestUri, $_SERVER['HTTP_HOST']) !== false) {
					$this->_requestUri = preg_replace('/^\w+:\/\/[^\/]+/', '', $this->_requestUri);
				} else {
					$this->_requestUri = preg_replace('/^(http|https):\/\/[^\/]+/i', '', $this->_requestUri);
				}
			} elseif (isset($_SERVER['ORIG_PATH_INFO'])) { // IIS 5.0 CGI
				$this->_requestUri = $_SERVER['ORIG_PATH_INFO'];
				if (!empty($_SERVER['QUERY_STRING'])) {
					$this->_requestUri .= '?' . $_SERVER['QUERY_STRING'];
				}
			} else {
				throw new Error("HttpRequest is unable to determine the request URI.");
			}
		}
		return $this->_requestUri;
	}

	public function getIsSecureConnection()
	{
		return isset($_SERVER['HTTPS']) && !strcasecmp($_SERVER['HTTPS'], 'on');
	}

	public function getSecurePort()
	{
		if ($this->_securePort === null) {
			$this->_securePort = $this->getIsSecureConnection() && isset($_SERVER['SERVER_PORT']) ? (int)$_SERVER['SERVER_PORT'] : 443;
		}
		return $this->_securePort;
	}

	public function getPort()
	{
		if ($this->_port === null) {
			$this->_port = !$this->getIsSecureConnection() && isset($_SERVER['SERVER_PORT']) ? (int)$_SERVER['SERVER_PORT'] : 80;
		}
		return $this->_port;
	}

	public function getHostInfo($schema = '')
	{
		if ($this->_hostInfo === null) {
			$http = $this->getIsSecureConnection() ? 'https' : 'http';
			if (isset($_SERVER['HTTP_HOST'])) {
				$this->_hostInfo = $http . '://' . $_SERVER['HTTP_HOST'];
			} else {
				$this->_hostInfo = $http . '://' . $_SERVER['SERVER_NAME'];
				$port = $this->getIsSecureConnection() ? $this->getSecurePort() : $this->getPort();
				if (($port !== 80 && !$this->getIsSecureConnection()) || ($port !== 443 && $this->getIsSecureConnection())) {
					$this->_hostInfo .= ':' . $port;
				}
			}
		}
		if ($schema !== '') {
			$port = $schema === 'https' ? $this->getSecurePort() : $this->getPort();
			$port = (($port !== 80 && $schema === 'http') || ($port !== 443 && $schema === 'https')) ? ':' . $port : '';
			$pos = strpos($this->_hostInfo, ':');
			return $schema . substr($this->_hostInfo, $pos, strcspn($this->_hostInfo, ':', $pos + 1) + 1) . $port;
		}
		return $this->_hostInfo;
	}

	public function getBaseUrl($absolute = false){
		if ($this->_baseUrl === null) {
			$this->_baseUrl = rtrim(dirname($this->getScriptUrl()), '\\/');
		}
		return $absolute ? $this->getHostInfo() . $this->_baseUrl : $this->_baseUrl;
	}

  public function getServerPort(){
    return $_SERVER['SERVER_PORT'];
  }
  
  
	public function getPathInfo(){
		if ($this->_pathInfo === null) {
			$pathInfo = $this->getRequestUri();
			if (($pos = strpos($pathInfo, '?')) !== false) {
				$pathInfo = substr($pathInfo, 0, $pos);
			}
			$pathInfo = urldecode($pathInfo);
			$scriptUrl = $this->getScriptUrl();
			$baseUrl = $this->getBaseUrl();
			if (strpos($pathInfo, $scriptUrl) === 0) {
				$pathInfo = substr($pathInfo, strlen($scriptUrl));
			} elseif ($baseUrl === '' || strpos($pathInfo, $baseUrl) === 0) {
				$pathInfo = substr($pathInfo, strlen($baseUrl));
			} elseif (strpos($_SERVER['PHP_SELF'], $scriptUrl) === 0) {
				$pathInfo = substr($_SERVER['PHP_SELF'], strlen($scriptUrl));
			} else {
				throw new Error("HttpRequest is unable to determine the path info of the request.");
			}
			$this->_pathInfo = trim($pathInfo, '/');
		}
		return $this->_pathInfo;
	}
}
