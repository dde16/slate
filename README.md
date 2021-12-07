# Slate

Slate is a PHP 8+ backend development framework.

## Features

* Extensive use of PHP 8+ Attributes
    * Uses a Metalang class to manage Attributes
    * No stringent conventions
    * Plethora of utilities; Getters and Setters to name a few
    * Implementing your own and injecting into magic methods simple and easy
* Routing
    * Routes use Attributes for easy extensibility
    * Customisable Route groups
* Object Orientated Sysv Utilities
    * Indexed Memory Tables (with data loading to/from a sql source)
    * Hashmaps
    * Doubly Linked Lists
* Comes with an Interpreter
    * Makes defining customisable data interchange languages easier and more consistent
* I/O
    * Stream Reader/Writer system like Microsoft
    * Processes are object orientated
    * String Iterators
* Object Relational Mapping (ORM)
    * Columns and OneToMany/OneToAny relationships in PHP attributes
    * Depth limiting/ordering
    * Single plan with multiple relationships splits into multiple queries for you

## Documentation

[Documentation](https://linktodocumentation)

## Routes

In order to get the full context of a Route, a controller and action must be made for the target to store information about eg. what http method it should accept.

The reason is twofold;

* It removes a lot of unnecessary information like the http method, which is only needed when a match is successful
* It keeps the route file as a brief overview of the project, so it isnt overwhelming to look at

### Simple Routing

`UserController.php`

``` php
namespace App\Controller {
    use Slate\Mvc\Controller;

    class UserController extends Controller {
        #[Route(methods: "get")]
        public function getUser(HttpRequest $request, HttpResponse $response): string {
            return "Get User";
        }
    }
}
```

`routes.php`

``` php
use Slate\Mvc\Router;
use App\Controller\UserController;

Router::add("/api/user", [UserController::class, "getUser"])->named("api.user.get");
```

This is all well until you have a rest api that requires the use of the same path. This is where compound matching comes into place.

### Compound Routing

Modifying `TestController.php` to add a new action;

``` php
#[Route(methods: "post")]
public function createUser(HttpRequest $request, HttpResponse $response): string {
    return "Create User";
}

#[Route(methods: "patch")]
public function updateUser(HttpRequest $request, HttpResponse $response): string {
    return "Create User";
}
```

Now, we can either add two routes of the same path which points to different actions or we use the `Router::many` function.

`routes.php`

``` php
Router::add("/api/user/{id}", [UserController::class, "updateUser"]);
Router::add("/api/user/{id}", [UserController::class, "createUser"]);
```

is equivalent to

``` php
Router::many(
    "/api/user/{id}",
    [
        "api.user.create" => [UserController::class, "createUser"],
        "api.user.update" => [UserController::class, "updateUser"]
    ]
);
```

This is because if it doesn't match, it continues to the next route with the same amount of minimum slashes (see optimisations).