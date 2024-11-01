<?php
namespace Prue;

class Node {
	public $handler = null;
	public array $methods = [];
	public array $middlewares = [];
	public string $paramName = "";
	public array $params = [];
	public array $children = [];
	public ?Node $paramChild = null;
	public ?Node $wildcardChild = null;
	public bool $isEnd = false;
}

class Router {
	const PREFIX_PARAM = ":";
	const PREFIX_WILDCARD = "*";
	const PREFIX_METHOD_ALL = "*";

	private string $prefix;
	private array $methods = [];
	private array $middlewares = [];
	private array $options = [];
	private Node $node;
	public $notFoundFunc;
	public $internalErrorFunc;
	public $methodNotAllowedFunc;

	public function __construct(...$options){
		$this->node = new Node();
		$this->prefix = '/';
		$this->options = $options;
		$this->initializeDefaultHandlers();
	}
	
	private function initializeDefaultHandlers(): void {
		$this->notFoundFunc = function (string $msg = "404 page not found") {
			\Prue::error($msg, 404);
		};
		$this->internalErrorFunc = function (string $msg = "500 internal server error") {
			\Prue::error($msg, 500);
		};
		$this->methodNotAllowedFunc = function (string $msg = "405 method not allowed") {
			\Prue::error($msg, 405);
			// http_response_code(405) && exit($msg);
		};
	}
	
	public function notFound($handler): Router {
		$this->notFoundFunc = $handler;
		return $this;
	}

	public function internalError($handler): Router {
		$this->internalErrorFunc = $handler;
		return $this;
	}

	public function methodNotAllowed($handler): Router {
		$this->methodNotAllowedFunc = $handler;
		return $this;
	}

	public function dispatch(): void {
		$node = $this->match();
		if ($node) {
			if ($node->handler) {
				$this->executeHandlers($node);
			} else {
				($this->methodNotAllowedFunc)();
			}
		} else {
			($this->notFoundFunc)();
		}
	}

	public function match(): ?Node {
		$method = explode("?", $_SERVER["REQUEST_METHOD"])[0];
		$url = explode("?", $this->getCurrentUri())[0];
		return $this->search($method, $url);
	}

	public function use(...$middlewares): Router {
		if (count($middlewares)) {
			$this->middlewares = array_merge($this->middlewares, $middlewares);
		}
		return $this;
	}

	public function group(string $pattern): Router {
		$router = new Router();
		$router->prefix = $this->prefix . '/' . trim($pattern, '/');
		$router->node = $this->node;
		$router->middlewares = $this->middlewares;
		$router->options = $this->options;
		return $router;
	}

	public function method(string ...$methods): Router {
		if (count($methods)) {
			$this->methods = array_merge($this->methods, $methods);
		}
		return $this;
	}

	public function handle(string $pattern, $handler): Router {
		$fullPattern = $this->prefix . '/' . trim($pattern, '/');
		$methods = $this->methods ?: [self::PREFIX_METHOD_ALL];
		foreach ($methods as $method) {
			$this->insert(strtoupper($method), $fullPattern, $handler);
		}
		$this->methods = [];
		return $this;
	}

	public function ALL(string $pattern, $handler): Router {
    return $this->method("*")->handle($pattern, $handler);
  }

  public function GET(string $pattern, $handler): Router {
		return $this->method("GET")->handle($pattern, $handler);
  }

  public function POST(string $pattern, $handler): Router {
		return $this->method("POST")->handle($pattern, $handler);
  }

  public function PUT(string $pattern, $handler): Router {
    return $this->method("PUT")->handle($pattern, $handler);
  }

  public function PATCH(string $pattern, $handler): Router {
    return $this->method("PATCH")->handle($pattern, $handler);
  }

  public function DELETE(string $pattern, $handler): Router {
    return $this->method("DELETE")->handle($pattern, $handler);
  }

	private function executeHandlers($node): void {
		$handlers = $this->formatHandlers($node);
		$len = count($handlers);
		$execute = function ($index) use (&$handlers, &$execute, $len) {
			if ($index < $len) {
				$handler = $handlers[$index];
				$next = function () use ($index, &$execute) {
					$execute($index + 1);
				};
				$this->call($handler, $index + 1 < $len ? $next : null);
			}
		};
		$execute(0);
	}

	private function formatHandlers($node): array {
		$handlers = [];
		$middlewares = [...$node->middlewares, $node->handler];
		$len = count($middlewares);
		for ($i = 0; $i < $len; $i++) {
			$mw = $this->checkHandler($middlewares[$i]);
			if (!$mw) {
				$type = $i == $len-1 ? "routing handler" : "middleware";
				($this->internalErrorFunc)("500 error: $type '$mw' not found");
			} else {
				array_push($handlers, $mw);
			}	
		}
		return $handlers;
	}

	private function checkHandler($handler) {
		if (is_string($handler)) {
			if(function_exists($handler)){
				return $handler;
			}
			$handler = str_replace('/', '\\', $handler);
			if (class_exists($handler)) {
				return $handler;
			}
			if (preg_match('/^(.*)\\\\(.*?)$/', $handler, $matches)) {
				$handler = [$matches[1], $matches[2]];
			}
		}
		if (is_array($handler)) {
			list($class, $action) = $handler;
			$class = str_replace('/', '\\', $class);
			$isEmpty = empty($class) || empty($action);
			if(!$isEmpty && class_exists($class) && method_exists($class, $action)){
				return $handler;
			}
		}
		if (is_callable($handler)) {
			return $handler;
		}
		return null;
	}

	private function call($handler, $next = null) {
		$options = is_callable($next) ? [$next, ...$this->options] : $this->options;
		if (is_string($handler)) {
			return new $handler(...$options);
		}
		if (is_array($handler)) {
			$handler[0] = new $handler[0](...$options);
			return call_user_func($handler, ...$options);
		}
		if (is_callable($handler)) {
			return $handler(...$options);
		}
	}
	
	private function getCurrentUri(): string {
		$uri = $_SERVER['PATH_INFO'];
		$uri = empty($uri) ? ($_SERVER['REQUEST_URI'] ?? '/') : $uri;
		$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
		$scriptPath = str_replace(basename($scriptName), '', $scriptName);
		if (strpos($uri, $scriptName) === 0) {
			$uri = substr($uri, strlen($scriptName));
		} elseif (strpos($uri, $scriptPath) === 0) {
			$uri = substr($uri, strlen($scriptPath));
		} else {
			$uri = '/';
		}
		return '/' . trim($uri, '/');
	}

	private function insert(string $method, string $pattern, $handler): ?Node {
		if ($method === '' || $pattern === '' || !$handler) {
			return null;
		}
		$node = $this->node;
		$segments = explode('/', $pattern);
		foreach ($segments as $segment) {
			if ($segment === '') continue;
			if (strpos($segment, self::PREFIX_PARAM) === 0) {
				$node = $node->paramChild ??= new Node();
				$node->paramName = substr($segment, 1);
			} elseif (strpos($segment, self::PREFIX_WILDCARD) !== false) {
				$node = $node->wildcardChild ??= new Node();
				$node->paramName = $segment;
			} else {
				for ($i = 0; $i < strlen($segment); $i++) {
					$char = $segment[$i];
					$node = $node->children[$char] ??= new Node();
				}
			}
		}
		$node->isEnd = true;
		$node->methods[$method] = $handler;
		$node->middlewares = array_merge($node->middlewares, $this->middlewares);
		return $node;
	}
	
	private function search(string $method, string $url): ?Node {
		$node = $this->node;
		$params = [];
		$segments = explode('/', $url);
		foreach ($segments as $segment) {
			if (empty($segment)) continue;
			$match = true;
			for ($i = 0; $i < strlen($segment); $i++) {
				$char = $segment[$i];
				if (!isset($node->children[$char])) {
					$match = false;
					break;
				}
				$node = $node->children[$char];
			}
			if ($match) continue;
			if ($node->paramChild) {
				$node = $node->paramChild;
				$params[$node->paramName] = $segment;
				$_GET[$node->paramName] = $segment;
				continue;
			}
			if ($node->wildcardChild) {
				$node = $node->wildcardChild;
				$index = array_search($segment, $segments);
				$param = implode('/', array_slice($segments, $index));
				$params[$node->paramName] = $param;
				$_GET[$node->paramName] = $param;
				break;
			}
			return null;
		}
		if ($node->isEnd) {
			$node->params = $params;
			$node->handler = $node->methods[$method] ?? $node->methods[self::PREFIX_METHOD_ALL] ?? null;
			return $node;
		}
		return null;
	}
}
