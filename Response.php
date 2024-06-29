<?php

namespace Prue;

class Response {
  private array $headers;

  public function __construct() {
    $this->headers = [];
  }

  public function clearHeader() {
    $this->headers = [];
  }

  public function setHeader(string $key, $value) {
    $this->headers[$key] = $value;
  }

  public function updateHeader() {
    foreach (array_keys($this->headers) as $key) {
      header_remove($key);
      header($key . ":" . $this->headers[$key]);
    }
  }

  private function setContentType(string $type) {
    $this->setHeader("Content-Type", $type);
  }

  public function sendStatus(int $code) {
    $this->status($code)->send();
    $this->end();
  }

  public function status(int $code): Response {
    http_response_code($code);
    return $this;
  }

  public function end() {
    exit(" ");
  }

  public function redirect(string $url, int $permanent = NULL) {
    header("Location: $url", true, $permanent ? 301 : 302);
    $this->end();
  }

  public function download(string $path, string $name = "default") {
    if( !file_exists("$path") ) {
      $this->status(200)->send("File $path no found");
      $this->end();
    }

    $this->setContentType("application/octet-stream");
    $this->setHeader("Content-Transfer-Encoding", "Binary");
    $this->setHeader("Content-disposition", "attachment; filename=$name");
    $this->updateHeader();

    die(readfile("$path"));
  }

  public function render(string $path, array $props = null) {
    $this->setContentType("text/html; charset=UTF-8");
    $this->updateHeader();
    if(file_exists($path)) include_once "$path";
    else $this->status(200)->send("File no found");
    $this->end();
  }

  public function send(string $data = null) {
    $this->updateHeader();
    if (isset($data)) echo $data;
    $this->end();
  }

  public function json(?array $data = null) {
    $this->setContentType("text/json");
    $this->send(json_encode($data));
  }

}