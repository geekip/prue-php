<?php
namespace Prue;

// 错误类
class Error extends \Exception{

  public function __construct($errorMessage='', $errorCode = 500) {
  	parent::__construct($errorMessage, $errorCode);
		$data = [
			"message" => $this->message,
			"code" => $errorCode == 0 ? $this->code : $errorCode,
			"file" => $this->file,
			"line" => $this->line,
			"trace" => $this->getTrace()
		];
		$data = self::getData($data);
		// ob_end_clean();
		\Prue::renderString($data,$this->getTpl());
  }

	public static function errorHandler(int $errno, string $errstr, string $errfile, int $errline) {
		switch ($errno) {
			case E_ERROR:
			case E_PARSE:
			case E_CORE_ERROR:
			case E_COMPILE_ERROR:
			case E_USER_ERROR:
				$msg=$errstr.$errfile."第{$errline}行";
				// errHalt($msg);
				exit;
			case E_STRICT:
			case E_USER_WARNING:
			case  E_USER_NOTICE:
			default:
				if(Config::get('DEBUG')){
					$msg=$errstr.$errfile."第{$errline}行";
					echo $msg;
					// errLog($msg);
					// include KJ_CORE.'/tpl/notice.tpl';
					exit;
				}
		}
		// echo $errstr;
		// echo "<b>Custom Error:</b> [$errno] $errstr on line $errline in file $errfile<br>";
		exit(1);
	}

	public static function fatalHandler($error) {
		if ($error !== NULL) {
			$data = [
				"message" => $error["message"],
				"code" => 500,
				"file" => $error["file"],
				"line" => $error["line"],
				"trace" => []
			];
			$data = self::getData($data);
			// ob_end_clean();
			\Prue::renderString($data,self::getTpl());
		}
	}

	protected static function getData(array $data): array{
		if(isset($data["message"])){
			$data["message"] = nl2br($data["message"]);
		}
		if(isset($data["trace"])){
			$data["trace"] = self::getTraces($data["trace"]);
		}
		$title = "";
		if(isset($data["code"])){
			switch ($data["code"]) {
				case 404:
					$title = "Page Not Found";
					break;
				case 405:
					$title = "Method Not Allowed";
					break;
				default:
					$title = "Internal Server Error";
					break;
			}
			http_response_code($data["code"]);
		}
		$data["title"] = $title;
		return $data;
	}

	protected static function getTraces(array $trace): array{
		$traces=[];
		foreach ($trace as $t) {
			$msg = "";
			if(isset($t["file"])){
				$msg .= $t["file"];
			}
			if(isset($t["line"])){
				$msg .= "(".$t["line"].")";
			}
			if(isset($t["class"])){
				$msg .= $t["class"];
			}
			if(isset($t["type"])){
				$msg .= $t["type"];
			}
			if(isset($t["function"])){
				$msg .= $t["function"]."()";
			}
			if(!empty(trim($msg))){
				array_push($traces, "<li>$msg</li>");
			}
		}
		return $traces;
	}

	protected static function getTpl() {
		$tpl = '
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
<title><?php echo($code);?> <?php echo($title);?></title>
<style type="text/css">
body,code,dd,div,dl,dt,fieldset,form,h1,h2,h3,h4,h5,h6,input,legend,li,ol,p,pre,td,textarea,th,ul{margin:0;padding:0}
body{font:14px/1.5 "Microsoft YaHei",Helvetica,Sans-serif;min-width:1200px;background:#f0f1f3;}
:focus{outline:0}
h1,h2,h3,h4,h5,h6,strong{font-weight:700}
a{color:#428bca;text-decoration:none}
a:hover{text-decoration:underline}
.error-page{background:#f0f1f3;padding:80px 0 180px}
.error-page-container{position:relative;z-index:1}
.error-page-main{position:relative;background:#f9f9f9;margin:0 auto;width:617px;-ms-box-sizing:border-box;-webkit-box-sizing:border-box;-moz-box-sizing:border-box;box-sizing:border-box;padding:50px 50px 70px}
.error-page-main:before{content:"";display:block;height:7px;position:absolute;top:-7px;width:100%;left:0}
.error-page-main h3{font-size:24px;font-weight:400;border-bottom:1px solid #d0d0d0}
.error-page-main h3 strong{font-size:54px;font-weight:400;margin-right:20px}
.error-page-main h4{font-size:20px;font-weight:400;color:#333}
.error-page-actions{font-size:0;z-index:100}
.error-page-actions div{font-size:14px;display:inline-block;padding:30px 0 0 0;-ms-box-sizing:border-box;-webkit-box-sizing:border-box;-moz-box-sizing:border-box;box-sizing:border-box;color:#666}
.error-page-actions ol{list-style:decimal;padding-left:20px}
.error-page-actions ul{padding-left:20px}
.error-page-actions li{line-height:1.6em;padding-bottom:5px;word-wrap: break-word;overflow-wrap: break-word;}
.error-page-actions:before{content:"";display:block;position:absolute;z-index:-1;bottom:17px;left:50px;width:200px;height:10px;-moz-box-shadow:4px 5px 31px 11px #999;-webkit-box-shadow:4px 5px 31px 11px #999;box-shadow:4px 5px 31px 11px #999;-moz-transform:rotate(-4deg);-webkit-transform:rotate(-4deg);-ms-transform:rotate(-4deg);-o-transform:rotate(-4deg);transform:rotate(-4deg)}
.error-page-actions:after{content:"";display:block;position:absolute;z-index:-1;bottom:17px;right:50px;width:200px;height:10px;-moz-box-shadow:4px 5px 31px 11px #999;-webkit-box-shadow:4px 5px 31px 11px #999;box-shadow:4px 5px 31px 11px #999;-moz-transform:rotate(4deg);-webkit-transform:rotate(4deg);-ms-transform:rotate(4deg);-o-transform:rotate(4deg);transform:rotate(4deg)}
</style>
</head>
<body>
<div class="error-page">
	<div class="error-page-container">
		<div class="error-page-main">
			<h3><strong><?php echo($code);?></strong><?php echo($title);?></h3>
			<div class="error-page-actions">
				<div>
					<ul>
						<li><?php echo($message);?></li>
						<li><?php echo($file);?></li>
						<?php echo join("", $trace);?>
					</ul>
				</div>
			</div>
		</div>
	</div>
</div>
</body>
</html>';
		return $tpl;
	}
}