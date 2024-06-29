<?php
namespace Prue;

// 错误类
class Error extends \Exception{
  public function __construct($errorMessage='', $errorCode = 500) {
  	parent::__construct($errorMessage, $errorCode);
  	$message = $this->getErrorMessage();
  	$response = new Response();
    $response->status($errorCode)->send($message);
  }

  //获取错误详情
	protected function getErrorMessage() : string {
    $errorMessage= $this->message;
    if(DEBUG){
    	$trace = $this->getTrace();
    	$errorMessage .= "\r\n{$this->file}({$this->line})\r\n";
    	foreach($trace as $t) {
	    	$errorMessage .= "{$t['file']}({$t['line']}) {$t['class']}{$t['type']}{$t['function']}()\r\n";
	    }
    }
		return $errorMessage ;
  }
}