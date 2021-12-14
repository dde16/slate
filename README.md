# Slate

Slate is a PHP 8+ backend development framework.

## Features

- Extensive use of PHP 8+ Attributes
    - Matalang class manages all Attribute instances with a simple interface
    - No stringent conventions
    - Plethora of utilities; Getters and Setters to name a few
    - Its easy to make your own by hooking into the magic methods

- Sysv Memory Utilities
    - Object Orientated
    - Various datastructures including Queues, Hashmaps and Doubly Linked Lists
    - Features memory table with indexing, searching and optional load/unload to a sql table

- Interpreter
    - Comes with a recursive descent parser 
    - Makes defining data interchange languages easy and efficient
    - Inject token definitions at runtime using LateTokens
    - Supports a bundled [CsvParser](src/Slate/Lang/CsvParser.php)

- Data and I/O
    - StreamReader/StreamWriter system
    - Object Orientated File and Process classes with additional helpers
    - Additional iterators such as Array Associative Iterators and String Iterators
    - Cache options including SysvMemoryTables

- Object Relational Mapping (ORM)
    - Columns as attributes
    - OneToMany, OneToOne relationships
    - Declarative queries

- Extensibility
    - Classes use Singletons, Facades, Factories and Macros where possible
    - Use of Service Providers

## Routes

### Views

Views are bound to paths through the `Router::view` function.

```php
use Slate\Facade\Router;

Router::view("/hello-world", "/hello-world");
```

Or you can just leave the second argument blank 

```php
Router::view("/hello-world");
```

As outlined in the @dde16/slate repo, the `mvc.view.path` is where all views are held and resolved to.

### Simple Routing

`UserController.php`
```php
namespace App\Controller {
    use Slate\Mvc\Controller;

    class UserController extends Controller {
        #[Route(methods: "post")]
        public function createUser(HttpRequest $request, HttpResponse $response): string {
            return "Create User";
        }
    }
}
```

`routes.php`
```php
use App\Controller\UserController;

Router::add("/api/user", [UserController::class, "createUser"])->named("api.user.create");
```

In this context you define the method in the route attribute or you can define it using `Route::post`.

### Parameters

Continuing on with the scenario, if we want to get a user we add an action and the route something like;

```php
#[Route(methods: "get")]
public function getUser(HttpRequest $request, HttpResponse $response): string {
    return "Get User";
}
```

```php
Router::get("/api/user/{id}", [UserController::class, "getUser"])->named("api.user.get");
```

This is all well until you have a rest api that requires the use of the same path, this is where compound matching comes in.

### Same-segment Parameters

There is also support for optional parameters which are not exclusive a path segment, but have a few prerequisites. 

```php
Router::get("/api/image/preview/{height}x{width}", [ImageController::class, "preview"]);
```

Here, this is allowed because there is a character between the two parameters. If not, you would need to define a pattern for each.

### Compound Routing

Abiding by the REST convention of managing resources, two paths can collide with different methods - such as getting and updating a user.

`TestController.php`
```php
#[Route(methods: "delete")]
public function updateUser(HttpRequest $request, HttpResponse $response): string {
    return "Update User";
}
```

Now, we can either add two routes of the same path which points to different actions, or we use the `Router::many` function.

`routes.php`
```php
Router::add("/api/user/{id}", [UserController::class, "getUser"])->named("api.user.get");
Router::add("/api/user/{id}", [UserController::class, "updateUser"])->named("api.user.update");
```

is equivalent to

```php
Router::many(
    "/api/user/{id}",
    [
        "api.user.get"    => [UserController::class, "getUser"],
        "api.user.create" => [UserController::class, "updateUser"]
    ]
);
```

This is because if it doesn't match, it continues to the next route with the same amount of minimum slashes (see optimisations).

### Accessing the current route

Inside of the controller action you can access the current route through the request.

```php

public function index(HttpRequest $request) {
    $request->route;
}

```

### Route Groups

To make writing routes faster, we can use Route groups combine and modify individual routes. This is useful if you have multiple different vhosts pointing to one backend.

#### Prefix Group

This group will prepend routes' path declared inside of it with the one provided to the group.

```php
Router::prefix("/api", function() {
    Router::add("/user", [UserController::class, "createUser"])->named("api.user.get");
});
```

#### Domain Group

This group will limit any routes declared within to a specific domain. 

```php
Router::domain("www.my-domain.co.uk", function() {
    Router::prefix("/api", function() {
        Router::add("/user", [UserController::class, "createUser"])->named("api.user.get");
    });
});
```

#### Name Group

This group is very alike the prefix group except it prepends the name instead of the path.

```php
Router::domain("www.my-domain.co.uk", function() {
    Router::name("api.", function() {
        Router::prefix("/api", function() {
            Router::add("/user", [UserController::class, "createUser"])->named("user.get");
        });
    }); 
});

```

Where the `/api/user` route's name resolves to `api.user.get`.

#### Combining the groups 

There is a way to combine groups with the `Router::group` function.

```php
Router::group(["prefix" => "/api", "name" => "api."], function() {
    Router::add("/user", [UserController::class, "createUser"])->named("user.get");
});
```

#### Extending the RouteGroup class

Currently all of the options are held together in one class for simplicity. So if you want to add your own options, you need to inherit the `Slate\Mvc\RouteGroup` class and since the `Router` class is a facade - you can override the `group` method to use your class. 

```php
use Slate\Mvc\RouteGroup;

class MyRouteGroup extends RouteGroup {
    protected ?int $port = null;

    public function __construct(array $options, ?Closure $callback = null) {
        parent::__construct($options, $callback);

        $this->port = @$options["port"];
    }

    public function influence(Route $route): Route {
        $route = parent::influence($route);

        if($this->port !== null)
            $route->uri->setPort($this->port);

        return $route;
    }
}

Router::macro("group", function(array $options, Closure $group): MyRouteGroup {
    $this->jit->push($group = new MyRouteGroup($options, $group));

    return $group;
});

Router::group(["port" => 8080], function() {
    Router::view("/index");
});
```

In this example, it will modify the port to which the `/index` route is viewable.

### Redirct routes

If you need to send a client somewhere, use the redirect route.

```php
Router::redirect("/some/old/page", "/404");
```

### Fallback

If none of your routes match in a given namespace, such as a domain or a path you can specify a fallback.

```php
Router::domain("www.my-domain.co.uk", function() {
    Router::view("/", "index")->named("index");

    Router::fallback(fn() => "domain matches but no route matches");
});

Router::fallback(fn() => "domain doesn't match");
```

Fallbacks, if they don't exist within the current namespace, fallback to the upper level. This applies to only;
- Schemes
- Domains
- Ports
- Path prefixes

## Getting request variables

To get request variables you can use the `HttpRequest::var()` function, where you can specify fallbacks, converters, validators and casts.

```php
function index(HttpRequest $request, HttpResponse $response) {
    $userID = $request->var("user_id")->fallback(null)->string()->get();
}
```

This var function sources these variables in order of
- Route parameters
- Get query parameters
- Post parameters

All the options for this class can be found at `Slate\Data\Field`.

## Results

Controller actions can return any value and Slate will convert it to a string. But for more complex returns, like files, there are dedicated functions/classes for it.

All result classes are in the `Slate\Mvc\Result` namespace.

### Returning data

As said, you can return normal values, but the `data` function allows you to bypass postprocessors.

```php
return data("test-string", bypass: true);
```

### Returning a file

To return a file you use the `contents` function, where the argument is the path.

```php
file_put_contents("/tmp/test.txt", "test-file");

return contents("/tmp/test.txt");
```

### Redirecting

Temporary redirect

```php
return redirect("/path/to/redirect", mode: "temporary");
```

Permanent redirect

```php
return redirect("/path/to/redirect", mode: "permanent");
```

### Status Codes

```php
use Slate\Http\HttpCode;

return code(HttpCode::BAD_REQUEST);
```

### Views

```php
return view("/some-view");
```

Views must be relative to the `mvc.view.path` otherwise an error is raised.

## Middleware

Middleware, currently, only works on Controllers - with support for standalone middleware intended.

### Preprocessor (Middleware)

Creating middleware is simple as defining a trait, or define it in the controller, and using the attribute.

`TRateLimiter.php`
```php
namespace App\Middleware {
    use Slate\Mvc\Attribute\Middleware;

    trait TEnsureTokenIsValid {
        #[Middleware("EnsureTokenIsValid")]
        public function rateLimiterPreprocessor(HttpRequest $request, object $next): mixed {
            if($request->var("token")->get() !== "my-secret-token")
                return redirect("/home");

            return $next();
        }
    }
}
```

When you call next, you do not need to pass the request as an argument as that is injected for you.

Then you include it in your controller

`UserController.php`
```php
namespace App\Controller {
    use Slate\Mvc\Controller;
    use App\Middleware\TEnsureTokenIsValid;

    class UserController extends Controller {
        use TEnsureTokenIsValid;
    }
}
```

Now, if you have multiple middleware, you might want to ensure the order of its execution. Just using traits will execute in the order its declared. But if you want to ensure its order you should specify the `MIDDLEWARE` constant array.

```php
class UserController extends Controller {
    use TEnsureTokenIsValid;
    use TRateLimiter;

    public const MIDDLEWARE = ["RateLimiter", "EnsureTokenIsValid"];
}
```

### AfterMiddleware

The different between Middleware and AfterMiddleware is that AfterMiddleware processes the output of a given action. This can be achieved by calling the next function first ratehr than last in the function.

Taking the example of wrapping an api result in a uniform format:

```json
{
    api: {
        version: 1,
    },
    data: "{action result}"
}
```

Creating the AfterMiddleware

```php
namespace App\Middleware {
    use Slate\Mvc\Attribute\Postprocessor;

    trait TApiFormatter {
        #[Middleware("ApiFormatter")]
        public function apiFormatterPostprocessor(HttpRequest $request, HttpResponse $response, mixed $data, object $next): mixed {
            $data = $next();

            $rest = [
                "api" => [
                    "version" => 1
                ]
            ];

            if($data instanceof \stdClass || is_array($data)) {
                $rest["data"] = $data;
            }

            return $data;
        }
    }
}
```

As can be seen, it checks for an Exception. This is because Slate passes exceptions to postprocessors instead of dedicated handlers.

## Error handlers

Defining error handlers is very much the same as defining Middleware, just use the attribute and call the next function to go onto the next handler.

## Metalang

The Metalang module extends from PHP 8 Attributes by managing Attribute instances where native PHP doesn't. While also providing a way to inject attributes into magic methods (`__get`, `__set`, `__call`, `__callStatic`) without rewriting them every time.

The best way to show this is by implementing a Getter.

### Creating a Getter

First you need to create the attribute, extending it from the default one.

```php
namespace App\Auxiliary\Attribute {
    use Attribute;
    use Slate\Metalang\MetalangAttribute;

    #[Attribute(Attribute::TARGET_METHOD)]
    class Getter extends MetalangAttribute {
        protected string $name;

        public function __construct(string $name) {
            $this->name = $name;
        }

        public function getName(): string {
            return $this->name;
        }

        // Required
        public function getKey(): string {
            return $this->getName();
        }
    }
}
```

Since this is such a common requirement for a single named attribute, it is included as the `Slate\Metalang\Prefab\MetalangNamedAttribute` class.

Now you need to create the injector. These are again, using attributes to implement. There are four attributes for the four magic methods:
- HookGet
- HookSet
- HookCall
- HookCallStatic

For a getter we require `AttributeGet`

```php

trait TGetterAttributeImplementor {
    #[HookGet(Getter::class, [NextAttribute::class])]
    public function getterImplementor(string $name, object $next): array {
        $design = static::design();

        if($design->getAttrInstance(Getter::class, $name) !== null) {
            list($match, $result) = $next($name);

            if(!$match)
                $result = $getter->parent->invokeArgs($this);

            return [true, $result];
        }

        return $next($name);
    }
}

```

To control the flow of execution, you specify the next attributes to be ran - this only being if the current class has any attribute instances of that class. To determine whether an attribute gets ran first in its chain, it requires it not be referenced as the next attribute to any other implementations.

Another peculiarity is that you return an array from the Getter Implementor. This is true for all magic method injections as the first value indicates whether a further injection matched, then we should return that value instead of continuing.

Now you add that to the desired class that derives from `Slate\Metalang\MetalangClass`.

```php
use Slate\Metalang\MetalangClass;

class MyClass extends MetalangClass {
    protected string $someProperty = "default";

    #[Getter("someProperty")]
    public function getSomeProperty(): string {
        return "{$this->default}-x";
    }
}
```

Now when instantiating and trying to get `someProperty` it will return `default-x`.

```php

$myInstance = new MyClass();

echo $myInstance->someProperty;

```

### All Attributes

In reality, Slate already has a getter attribute and many more, including;
- Benchmark
- Object Carrying
- Method Aliasing
- Cache
- Fillable 
- Initialiser
- Getter
- Setter
- Property
- ReadOnly
- Retry
- Class/Object Throttle
- Method Throttle

There is one problem however. Consider the following

```php
class SomeApiClient extends GuzzleClient {
    #[Throttle(0.1)]
    protected function scrapeResource(): array {
        /* perform some http request */
    }
}

$client = new SomeApiClient;

$client->scrapeSubResource();
```

This works fine because the magic methods only call when a method or property cant be found to be public.

But if we call it internally
```php
public function scrapeResources(): array {
    return $this->scrapeResource();
}
```

This will call the function without the throttle because it found an existing function that is available to the current scope. The way to fix this is to prepend an underscore when calling an internal function - metalang will automatically remove it and continue resolving.

```php
public function scrapeResources(): array {
    return $this->_scrapeResource();
}
```

Currently, this is the only way of doing this.

## ORM

The ORM in Slate currently only supports MySQL but is and will be capable of supporting other databases in the future. Regardless, one change is that the `Model` class is called `Entity`. Also, when we talk about Models, we mean a Model is an instance of an Entity but not that class itself.

Defining an Entity is done by inheriting the `Slate\Neat\Entity` class and specifying the `SCHEMA` and `TABLE` constants.

```php

use Slate\Neat\Entity;

class User extends Entity {
    public const SCHEMA = "some_schema";
    public const TABLE  = "user";
    public const CONN   = "some-conn-name";

    #[PrimaryColumn(type: "bigint unsigned", incremental: true)]
    public ?int $id = null;

    #[UniqueColumn(type: "uuid")]
    public string $uid;

    #[Column("user_name", type: "varchar(50)", index: "BTREE")]
    public string $username;

    #[Column(type: "varchar(128)")]
    public string $password;

    #[Column("avatar_uid", type: "uuid")]
    public ?string $avatarUID = null;

    #[OneToOne("avatarUID", [Avatar::class, "uid"])]
    public ?Avatar $avatar = null;
    
    #[OneToMany("uid", [Cookie::class, "accountUID"])]
    public array $cookies = [];
}
```

If you want it to be connection specific, you specify the `CONN` constant or leave it undefined/null to use the default connection.

### Constraints

There are multiple constraints with entites, that;
- They must have a primary column, whether it be incremental or not.
- Incremental primary columns must allow null as it needs to be filled in after inserted.
- There is no need for the not-null specifier as this is inferred through the property nullability. 

### Type Aliasing

To make development easier, Slate offers sql type aliasing. Such as that seen for the `avatar_uid` column, `uuid` will map to `varchar(32)`.

You can specify your pseudotypes in the `orm.type.psuedo-type` config option, as an array in the format:
```php
[
    "*" => [
        "uuid" => "varchar(32)"
    ]
]
```

Or if you want a driver-specific type

```php
[
    "mysql" => [
        "coordinate" => "decimal(11, 8)""       
    ]
]
```

### Simple Queries

You can get models through instantiating a query for the Entity or using one of the quick methods.

Now if the property name is the same as the column, and you're not inside of a multiquery (we talk about this a bit later), then you can use strings. Otherwise you call the property statically to get its EntityRef. This is because in different query modes, the tables will or will not be aliased thus the columns would be ambiguous.

Getting the first model is as simple as:

```php
$firstUser = User::first("username", "some-username");

$firstUser = User::first(User::username(), "some-username");

$firstUser = User::query()->where(User::username(), "some-username")->first();
```

Limiting and ordering is as simple as getting the first model:

```php
$users =
    User::where(User::username(), "LIKE", "%abc%")
        ->orderBy(User::username())
        ->limit(10);

$users =
    User::query()
        ->where(User::username(), "LIKE", "%abc%")
        ->orderBy(User::username())
        ->limit(10)
;
```

### Complex Queries

Complex queries allow the developer to effectively plan the relationships they wish to get, rather than getting them chaining one by one and customise what type of join it is.

Taking the earlier User example;
```php

$users =
    User::plan([
        "avatar" => [
            "@flag" => "?",
        ],
        "cookies" => [
            "@flag"    => "!",
            "@orderBy" => Cookie::createdAt(),
            "@limit"   => 5
        ]
    ])
    ->where(User::username(), "LIKE", "%abc%")
;
```

What this will do is it will break the relationships down so they are separate and add the options, prefixed by an '@' symbol. Any scopes these relationships have will be applied.

Options include:
- Flag: determines what type of join it will be, '!' means inner and '?' means left join
- Order By: Orders by a given property name (which gets mapped into a column)
- Order Direction: Orders by a given direction (ASC|DESC)
- Limit: limits the result set like a normal query
- Offset: same as a normal query

There is one caveat to be aware of. In this case, queries are ran one after another where models cannot repeat because Slate internally indexes them. 

So if a left join relationship resolves (avatar) and the inner join (cookies) doesn't; it will result in the model being there without cookies. This applies regardless of the order of join types. This is one of the drawbacks of this way of querying so do be aware.

### Scopes

Scopes are functions that can add options to a relationship or query. They can be called manually or if they have the same name as a relationship property, will run automatically.

```php
use Slate\Neat\Attribute\OneToMany;
use Slate\Neat\Attribute\Scope;

class ContactList extends Entity {
    #[OneToMany("uid", [Contact::class, "accountUID"])]
    public array $familyContacts = [];

    #[Scope("familyContacts")]
    public static function familyContactsScope($query): void {
        $query->where(Contact::type(), "FAMILY");
    }

    #[OneToMany("uid", [Contact::class, "accountUID"])]
    public array $friendContacts = [];

    #[Scope("friendContacts")]
    public static function friendContactsScope($query): void {
        $query->where(Contact::type(), "FRIEND");
    }
}
```

Getting the contact list with family contacts is as simple as before:

```php
$contactList = ContactList::familyContacts()->first();
```

Note, when we run first; its running to get the first `ContactList`, not th first family contact record. To set limits you do the same thing with complex queries.

```php
$contactList = ContactList::plan([
    "familyContacts" => [
        "@limit" => 5
    ]
])->first();
```

And for a complex query

```
$contactList = ContactList::plan([
    "familyContacts" => [
        "@limit" => 10
    ],
    "friendContacts" => [
        "@limit" => 5
    ]
])->first();
```

### How it works

In order to answer the next question of how performant it is, I must explain how it works on the SQL level. I will give an example output


## Data

Slate adds a lot of Data storage and handling classes such as Repositories, Encryption helpers and more.

### Collections

The `Slate\Data\Collection` class is just an object wrapper around the `\Arr` helper functions. Either it modifies the collection array or it returns a new collection. These can be differentiated between in the `SPL_FUNCTIONS_RETURN` and `SPL_FUNCTIONS_SET` constants.

To initialise a Collection

```php
$myCollection = collect([1, 2, 3, 4]);
```

Example of \Arr function working on the collection

```php
$myCollection->map(fn($v) => 2*$v);

echo json_encode($myCollection->toArray());
```

The result being

```json
[2, 4, 6, 8]
```

#### Permissions

Collections allow these permissions to be set (on top of being readable which cant be changed)
- Writeable
- Appendable
- Deletable

These can be specified when creating the collection.

```php
$collection = new Collection([], Collection::WRITEABLE | Collection::APPENDABLE);
```

In this example you cannot delete an element.

#### Passthru

If you have a series of elements you want to aggregate - so that when you call a function on the collection it calls it on all the elements - you can achieve this through a passthru. To enable it just use the `passthru` method.

```php
$collection->passthru();
```

### Streams

Slate uses the StreamReader/StreamWriter system of handling IO.

#### StreamReader

You can use stream reader to read existing streams

```php
use Slate\IO\StreamReader;

$stdin = new StreamReader(STDIN);
```

StreamReader provides some useful functions such as buffered reading, hashing, read untils and more.

```php

$hash = $stdin->hash("sha256"); 

$all = $stdin->readAll(bufferSize: 8096);

$firstline = $stdin->readUntil("\n");

$firstchar = $stdin->readChar();

$firstbyte = $stdin->readByte();

$stdin->pipe(STDOUT);

$stdinAsJson = $stdin->json(assert: true);

foreach($stdin as $char) {
    echo $char;
}

$stdout->relseek(-1);

$stdout->close();

```

All the functions can be found by reading the docs for `Slate\IO\StreamReader`.

#### StreamWriter

Stream Writer provides basic functionality for writing to streams.

```php
$stdout = new StreamWriter(STDOUT);

$stdout->writebyte(0x68);

$stdout->write("123");

$stdout->flush();

$stdout->truncate();
```

All the functions can be found by reading the docs for `Slate\IO\StreamWriter`.

### Files

The `Slate\IO\File` class combines all the functions of the StreamReader and StreamWriter with additional helper functions.

You can either initialise it with a strict mode or set the mode when opening the file.

```php
$file = new File("/path/to/file", "r");
$file->open();
```

or 

```php
$file = new File("/path/to/file");
$file->open("r");
```

Files can also be configured to lock and unlock

```php
$file->open("r", LOCK_EX | LOCK_NB);

echo $file->isLocked();

$file->unlock();
$file->close();
```

To get the info of the current path, or use it yourself you can check the `path` property.

```php
echo $file->path->getDirectory();
/path/to
```

FilePathInfo is not being used because it 

### Repositories

Slate has different types of repositories for different storage options
- `Slate\Data\Repository\FileSystemRepository`
- `Slate\Data\Repository\FileSystemEncryptedRepository`
- `Slate\IO\SysvSharedMemoryRepository`

Only the FileSystem repositories are serialisable as it is not required for Sysv Memory Shares as PHP handles that for you.

All the functions are the same so we'll use the FileSystemRepository as an example.

```php
$fsRepo = new FileSystemRepository("/tmp", serializer: "json", autoforget: true);
```

Auto-forget will remove any expired items automatically. To view all serialisers view the `Slate\Data\SerializerFactory`.

#### Putting Values

```php
$fsRepo->put("some-key", "some-value", 60.1);
```

or is equivalent to

```php
$fsRepo->until("some-key", "some-value", microtime(true) + 60.1);
```

To make a value never expire do

```php
$fsRepo->forever("some-key", "some-value");
```

or 

```php
$fsRepo->put("some-key", "some-value", -1);
```

#### Checking values

```php
$fsRepo->has("some-key");
```

Or to check specifically if it has expired

```php
$fsRepo->expired("some-key");
```

#### Pulling values

```php
$value = $fsRepo->pull("some-key", fallback: "fallback-value");
```

#### Forgetting and flushing

```php
$fsRepo->forget("some-key");
$fsRepo->flush();
```

## Interpreter

Another clarification to be made is that the interpreter does NOT support ABNF or any derivative of it.
Slate includes an interpreter which allows writing of non-mainstream data interchange languages like jsonc more dynamic - as opposed to writing an inflexible hard-coded parser or where there is no C php extension. While the name `Interpreter` infers of an executing Translator - you should not use it for that. Interpreting and executing a language, from another Interpreted language is slow. Furthermore, this interpreter doesn't use ABNF or any derivative so it will have to be done manually.

Since this is an advanced topic, I advise the reader to go over the Csv Parser to know where to start.

### CsvParser

There comes an included parser for CSV files, to use it however you need a String Iterator or a file. 

```php
use Slate\Lang\CsvParser;
use Slate\Data\Iterator\StringIterator;

$parser = new CsvParser();

$csvString = <<<EOD
column1,column2
value1,value2
EOD;

$stringIterator = new StringIterator($csvString);

$header = null;

foreach($parser->interpret($stringIterator) as $row) {
    if($header !== null) {
        // do something
    }
    else {
        $header = $row;
    }

    debug($row);
}
```

There is no header reading toggle so this will suffice.

