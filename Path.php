<?php
namespace Prue;

class Path {
  const PREFIX = '@';

  public static array $aliases = [];

  private static function _setAlia($key, $value = '') {
    if (strncmp($key, self::PREFIX, 1)) {
      $key = self::PREFIX . $key;
    }
    self::$aliases[$key] = self::_format($value);
  }
  private static function _format($path = '') {
    return str_replace("\\", DIRECTORY_SEPARATOR, $path);
  }

  public static function setAlia($key, $value = '') {
    if (is_array($key)) {
      foreach ($key as $k => $v) self::_setAlia($k, $v);
    } else {
      self::_setAlia($key, $value);
    }
  }

  public static function getAlia(string $key) {
    if (strncmp($key, self::PREFIX, 1)) {
      return $key;
    }
    foreach (self::$aliases as $alias => $path) {
      if (strpos($key, $alias) === 0) {
        return str_replace($alias, $path, $key);
      }
    }
    return null;
  }

  public static function resolve(...$paths) {  
		$basePath = '';  
		foreach ($paths as $path) {  
			$path = self::_format($path);
      $path = self::getAlia($path);
			if (strpos($path, DIRECTORY_SEPARATOR) === 0) {  
				$basePath = $path;  
				continue;  
			}  
			if (empty($basePath)) {  
				$basePath = $path;  
			} else {  
				$basePath = rtrim($basePath, DIRECTORY_SEPARATOR);  
				$path = trim($path, DIRECTORY_SEPARATOR);  
				$basePath .= DIRECTORY_SEPARATOR . $path;  
			}  
		}  
		return realpath($basePath) ?: $basePath;  
	} 
}
