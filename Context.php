<?php
namespace Prue;
use Prue\Request;
use Prue\Response;
use Prue\Path;

class Context {
	public Request $req;
	public Response $res;
	public array $data = [];
	
	public function __construct() {
		$this->req = \Prue::request();
		$this->res = \Prue::response();
    $functionPath = Path::resolve(DIR_APP, 'function.php');
    if(file_exists($functionPath)){
      require_once $functionPath; 
    }
  }

  public function getPost(): array{
    $ret = [];
    try {
      $input = file_get_contents('php://input');
      $ret = json_decode($input,true);
    } catch (Exception $e) {
      \Prue::error("POST data need json",500) ;
    }
    return $ret;
  }

  public function Data($k, $v=null): Context {
    $data = [];
    if(is_array($k)){
      $data = $k;
    }else{
      $data[$k] = $v;
    }
    $this->assign('data',$data);
    return $this;
  }

  public function assign($k=[], $v=null): Context {
    if(is_array($k)){
      $this->data = array_merge($this->data, $k);
    }else{
      $this->data[$k] = $v;
    }
    return $this;
  }

  public function render(String $tplPath='') : Context {
    $tplPath = Path::resolve(DIR_THEME, $tplPath);
    // $this->res->updateHeader();
    \Prue::render($this->data, $tplPath);
    return $this;
  }

  public function json($code=0, ?string $msg='ok'): void {
    $data = $this->data['data'] ?? [];
    if(!is_numeric($code)){
      $msg = $code;
      $code = 0;
    }
    if(isset($code) && $code!=0){
      $data = null;
    }
    $this->assign([ 'code' => $code, 'msg' => $msg, 'data' => $data ]);
    $this->res->json($this->data);
  }
  
}
