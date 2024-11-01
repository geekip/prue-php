<?php
namespace Prue;

class View{

  public static function renderString(array $data=[], string $tpl){
    $tpl = trim($tpl);
    extract($data);
    eval('?>' . $tpl);
    unset($data, $tpl);
    exit(" ");
  }

  public static function render(array $data=[], string $tplPath){
    $tplPath = trim($tplPath);
    if(!file_exists($tplPath)){
			\Prue::error('Theme Error: template '.$tplPath.' does not exist！', 500) ;	
		}
    extract($data);
		\Prue::response()->updateHeader();
		include $tplPath;
    unset($data, $tplPath);
		exit(" ");
  }
}
