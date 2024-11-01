# geekip/mux
A simple and lightweight PHP MVC framework.

## Features

* [Bootstrap App](#static-routes)
* [Config](#custom-handler)
* [Router](#group)
	* [Context Options](#group)
	* [Custom error handler](#group)
	* [Methods](#group)
	* [Parameters](#group)
	* [Wildcard](#group)
	* [Group](#group)
	* [Middleware](#group)
* [Context](#custom-error-handler)
	* [Http Request](#methods)
	* [Http Response](#parameters)
* [Error](#wildcard)

# Install
`$ go get -u github.com/geekip/mux`

# Usage

### Bootstrap App
``` php
<?php
namespace Demo;

define('DEBUG', true);
require_once '/Prue/Application.php';
$router = require('./Router/router.php');
$config = require('./config/config.php');

\Prue\Application::init($config)->dispatch($router);
```

### Config
``` php
<?php

return [
	'TIMEZONE' => 'PRC',
	'DB' => [...],
	...
];

```

### Router

``` php
<?php
namespace Router;

$context = new Context();
$router = new \Prue\Router($context);

$test = function (Context $ctx){
  echo "home test";
};

$router->use("/Middleware/Test","/Middleware/Cors");

$router->method("GET")->handle("/",$test);

$user = $router->group("/user");
$user->GET("/","Api/Home/index");
$user->GET("/:id","Api/Home/GetUserInfo");
$user->GET("/list","Api/Home/GetUserList");

return $router;
```

### Methods

``` go
func handler(w http.ResponseWriter, req *http.Request) {
  w.Write([]byte("hello world!"))
}

func main() {
  router := mux.New()
  // all Methods
  router.Handle("/hello", http.HandlerFunc(handler))
  router.Method("*").Handle("/hello", http.HandlerFunc(handler))
  // GET
  router.Method("GET").Handle("/hello", http.HandlerFunc(handler))
  // More...
  router.Method("POST","PUT").Handle("/hello", http.HandlerFunc(handler))

  log.Fatal(http.ListenAndServe(":8080", router))
}
```

### Parameters

``` go
func handler(w http.ResponseWriter, req *http.Request) {
  params,_ := mux.Params(req)
  w.Write([]byte("match user/:id ! get id:" + params["id"]))
}

func main() {
  router := mux.New()
  // http://localhost:8080/user/123
  router.Handle("/user/:id", http.HandlerFunc(handler))
  
  log.Fatal(http.ListenAndServe(":8080", router))
}
```

### Wildcard

``` go
func handler(w http.ResponseWriter, req *http.Request) {
  params := mux.Params(req)
  // foo/bar
  w.Write([]byte(params["*"]))
}

func main() {
  router := mux.New()
  // http://localhost:8080/user/foo/bar
  router.Handle("/user/*", http.HandlerFunc(handler))
  
  log.Fatal(http.ListenAndServe(":8080", router))
}
```


### Group

``` go
func handler(w http.ResponseWriter, req *http.Request) {
  w.Write([]byte("hello world!"))
}

func main() {
  router := mux.New()
  user := router.Group("/admin")
  {
    // get /admin/user/list
    user.Method("GET").HandlerFunc("/user",handler)
    // put /admin/user/edit
    user.Method("PUT").HandlerFunc("/user",handler)
  }
  
  log.Fatal(http.ListenAndServe(":8080", router))
}
```

### Middleware

``` go
func handler(w http.ResponseWriter, req *http.Request) {
  w.Write([]byte("hello world!"))
}

func middleware1(next http.Handler) http.Handler {
  return http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
    ctx := context.WithValue(r.Context(), "user", "admin")
    w.Write([]byte("middleware 1"))
    next.ServeHTTP(w, r.WithContext(ctx))
  })
}

func middleware2(next http.Handler) http.Handler {
  return http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
    if user, ok := r.Context().Value("user").(string); ok {
      w.Write([]byte("middleware 2, user:"+user))
    }
    next.ServeHTTP(w, r)
  })
}

func main() {
  router := mux.New()
  router.Use(middleware1, middleware2)
  router.HandlerFunc("/user", handler)
  
  log.Fatal(http.ListenAndServe(":8080", router))
}
```

### FileServe

``` go
func fileHandler(dir string) http.Handler {
  return func(w http.ResponseWriter, req *http.Request) {
    params := mux.Params(req)
    basePath := strings.TrimSuffix(req.URL.Path, params["*"])
    fs := http.StripPrefix(basePath, http.FileServer(http.Dir(dir)))
    fs.ServeHTTP(w, req)
  }
}

func main() {
  router := mux.New()
  router.HandleFunc("/files/*",fileHandler("./folder"))
  
  log.Fatal(http.ListenAndServe(":8080", router))
}
```