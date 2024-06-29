<?php

namespace Prue;

class Request {
    
  private array $values;
  private array $params;
  private string $method;
  private array $header;
  private array $body;
  
  public function __construct() {
    $this->values = array();
    $this->body = array();
    $this->method = explode("?", $_SERVER["REQUEST_METHOD"])[0];
    $this->params = $_REQUEST;
    $this->header = apache_request_headers();
    parse_str(file_get_contents("php://input"),$this->body);
  }

  public function getMethod(): string {
    return $this->method;
  }

  public function getHostName(): string {
    return $_SERVER["HTTP_HOST"];
  }

  public function getOriginalUrl(): string {
    return $_SERVER["REQUEST_URI"];
  }

  public function getBaseUrl(): string {
    $uri = pathinfo($this->getOriginalUrl());
    return $uri["dirname"];
  }

  public function getPath(): string  {
    $uri = pathinfo($this->getOriginalUrl());
    $uri_explode = explode("?", $uri["basename"]);
    // if(sizeof($uri) > 1) $uri = $uri_explode[0];
    if(count($uri_explode) > 1) $uri = $uri_explode[0];
    else $uri = $uri["basename"];
    return $uri;
  }

  public function setValue(string $key, $value) {
    $this->values[$key] = $value;
  }

  public function getValue(string $key) {
    if(key_exists($key, $this->values)) return $this->values[$key];
    return null;
  }

  public function getValues(): ?array {
    return $this->values;
  }

  public function setParam(string $key, $value) {
    $this->params[$key] = $value;
  }

  public function getParam(string $key) {
    if(key_exists($key, $this->params)) return $this->params[$key];
    return null;
  }

  public function getParams(): array {
    return $this->params;
  }

  public function getBody(): array {
    return $this->body;
  }

 
  public function setHeader(string $key, $value) {
    $this->header[$key] = $value;
  }

  
  public function getHeader(string $key) {
    if(key_exists($key, $this->header)) return $this->header[$key];
    return null;
  }

  public function getHeaders(): ?array {
    return $this->header;
  }
  
  public function setCookie(string $key, $value, string $path = "/", int $time = NULL) {
    if ($time !== null && is_int($time)) setcookie($key, $value, $time, $path);
    else setcookie($key, $value, time() + 604800, $path); // 7 days default
  }

  public function getCookie(string $key): ?string {
    if(isset($_COOKIE[$key])) return $_COOKIE[$key];
    return null;
  }

  public function getCookies(): array {
    return $_COOKIE;
  }

  public function clearCookies() {
    foreach ($_COOKIE as $key => $item) {
      $this->setCookie($key, "", time() - 3600);
    }
  }

  public function getIP(): string {
    if (!empty($_SERVER['HTTP_CLIENT_IP']))
      return $_SERVER['HTTP_CLIENT_IP'];

    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])){
      $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
      return trim(end($ips));
      // return $_SERVER['HTTP_X_FORWARDED_FOR'];
    }
      
    // return $_SERVER['REMOTE_ADDER'];
    return $_SERVER['REMOTE_ADDR'];
  }

  public function isXHR(): bool {
    return $this->getHeader("XMLHttpRequest") !== null;
  }

  public function is(): ?string {
    return $this->getHeader("Content-Type");
  }

}