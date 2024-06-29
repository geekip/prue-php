<?php
namespace Prue;
use Prue\Request;
use Prue\Response;

class Context {
	public Request $req;
	public Response $res;
	private array $data = [];
	
	public function __construct(Request $req = null, Response $res = null) {
		$this->req = $req ?? new Request();
		$this->res = $res ?? new Response();
    $functionPath = DIR_APP.DS.'function.php';
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
      new Error("POST data need json",500) ;
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
