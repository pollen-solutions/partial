# Partial Component

[![Latest Stable Version](https://img.shields.io/packagist/v/pollen-solutions/partial.svg?style=for-the-badge)](https://packagist.org/packages/pollen-solutions/partial)
[![MIT Licensed](https://img.shields.io/badge/license-MIT-green?style=for-the-badge)](LICENSE.md)
[![PHP Supported Versions](https://img.shields.io/badge/PHP->=7.4-8892BF?style=for-the-badge&logo=php)](https://www.php.net/supported-versions.php)

Pollen Solutions **Partial** Component provides layer and tools for creating reusable web user interfaces.

## Installation

```bash
composer require pollen-solutions/partial
```

## Basic Usage

### From a callable

```php
use Pollen\Partial\PartialManager;

$partial = new PartialManager();

$partial->register('hello-partial', function () {
    return 'Hello World !';
});

echo $partial->get('hello-partial');
```

### From the default partial tag driver

```php
use Pollen\Partial\PartialManager;

$partial = new PartialManager();

$partial->register('hello-partial');

echo $partial->get('hello-partial', ['content' => 'Hello World !']);
```

### From a custom driver

```php
use Pollen\Partial\PartialDriver;
use Pollen\Partial\PartialManager;

class HelloPartial extends PartialDriver
{
    public function render() : string{
        return 'Hello World !';
    }
}

$partial = new PartialManager();

$partial->register('hello-partial', HelloPartial::class);

echo $partial->get('hello-partial');
```

### Through a PSR-11 depency injection container

```php
use Pollen\Container\Container;
use Pollen\Partial\PartialDriver;
use Pollen\Partial\PartialManager;

$container = new Container();

$partial = new PartialManager();
$partial->setContainer($container);

class HelloPartial extends PartialDriver
{
    public function render() : string{
        return 'Hello World !';
    }
}

$container->add('helloPartialService', HelloPartial::class);

$partial->register('hello-partial', 'helloPartialService');

echo $partial->get('hello-partial');
```

### Shows a partial driver instance with custom parameters

```php
use Pollen\Partial\PartialDriverInterface;
use Pollen\Partial\PartialManager;

$partial = new PartialManager();

$partial->register('hello-partial', function (PartialDriverInterface $driver) {
    return 'Hello '. $driver->get('name') .' !';
});

echo $partial->get('hello-partial', ['name' => 'John Doe']);
```

### Recalls the same partial driver instance with keeped custom parameters

```php
use Pollen\Partial\PartialDriverInterface;
use Pollen\Partial\PartialManager;

$partial = new PartialManager();

$partial->register('hello-partial', function (PartialDriverInterface $driver) {
    return 'Hello '. $driver->get('name') .' !<br>';
});

echo $partial->get('hello-partial', 'HelloJohn', ['name' => 'John Doe']);
echo $partial->get('hello-partial', 'HelloJane', ['name' => 'Jane Doe']);
echo $partial->get('hello-partial', 'HelloJohn');
```

## Partial driver API

### Partial driver call parameters

```php
use Pollen\Partial\PartialDriverInterface;
use Pollen\Partial\PartialManager;

$partial = new PartialManager();

$tag = $partial->get('tag', [
    /** 
     * Common driver parameters.
     * -------------------------------------------------------------------------- 
     */
    /**
     * Main container HTML tag attributes (Common parameters).
     * @var array $attrs
     */
    'attrs'   => [
        'class' => '%s MyAppendedClass '
    ],
    /**
     * Content displayed after the main container.
     * @var string|callable $after
     */
    'after'   => 'content show after',
    /**
     * Content displayed before the main container.
     * @var string|callable $before
     */
    'before'  => function (PartialDriverInterface $driver) {
        return 'content show before'
    },
    /**
     * List of parameters of the template view|View instance.
     * {@internal See below in the View API usage section.}  
     * @var array|ViewInterface $view
     */
    'view'  => [],
    
    /** 
     * Tag partial driver parameters
     * -------------------------------------------------------------------------- 
     */
    /**
     * HTML tag.
     * @var string $tag div|span|a|... default div.
     */
    'tag'       => 'div',
    /**
     * HTML tag content.
     * @var string|callable $content
     */
    'content'   => '',
    /**
     * Enable tag as singleton.
     * {@internal Auto-resolve if null based on list of known singleton tags.}
     * @var bool|null $singleton
     */
    'singleton' => null,
]);

echo $tag;
```

### Partial driver instance methods

```php
use Pollen\Partial\PartialManager;

$partial = new PartialManager();

$partial->register('hello-partial', function () {
    return 'Hello World';
});

if ($hello = $partial->get('hello-partial')) {
    // Gets alias identifier.
    printf('alias: %s <br/>', $hello->getAlias());

    // Gets the base prefix of HTML class.
    printf('base HTML class: %s <br/>', $hello->getBaseClass());

    // Gets the unique identifier.
    printf('identifier: %s <br/>', $hello->getId());

    // Gets the index in related partial manager.
    printf('index: %s <br/>', $hello->getIndex());
}
```

### View API usage

#### Plates view engine

Partial driver used Plates as default template engine.

1. Creates a view file for the partial driver.

```php
// /var/www/html/views/hello-partial/hello.plates.php file
/**
 * \Pollen\Partial\PartialTemplateInterface $this
 */
echo 'Hello World !'
```

2. Creates and call a partial driver with this above file directory as view directory.

```php
use Pollen\Partial\PartialDriver;
use Pollen\Partial\PartialManager;

$partial = new PartialManager();

$partial->register('hello-partial', new class extends PartialDriver{});

echo $partial->get('hello-partial', ['view' => [
    /**
     * View directory absolute path (required).
     * @var string
     */
    'directory' => '/var/www/html/views/hello-partial/',
    /**
     * View override directory absolute path.
     * @var string|null
     */
    'override_dir' => null,
    /**
     * View render main template name. index is used by default if its null.
     * @var string|null
     */
    'template_name' => 'hello'
]]);
```

#### Uses another view engine

Your are free to used your own instance of Pollen\View\ViewInterface as the partial driver view parameter if needed. In
this example Twig engine is used instead Plates.

1. Creates a view file for the partial driver.

```php
// /var/www/html/views/hello-partial/index.html.twig file
/**
 * \Pollen\Partial\PartialTemplateInterface $this
 */
echo 'Hello World !'
```

2. Creates and call a partial driver with this above file directory as view directory.

```php
use Pollen\View\ViewManager;
use Pollen\Partial\PartialDriver;
use Pollen\Partial\PartialManager;

$partial = new PartialManager();

$partial->register('hello-partial', new class extends PartialDriver{});

$viewEngine = (new ViewManager())->createView('twig')->setDirectory('/var/www/html/views/hello-partial/');

echo $partial->get('hello-partial', ['view' => $viewEngine]);
```

### Routing API usage

In some cases, partial driver could do should be able to send a response through a controller, for example to respond
form a rest api call.

Fortunately, all partial driver instance are related to a route stack and have a reponseController method to do that.
The partial driver route stack is created for all known HTTP methods (GET, POST, PATH, OPTIONS, DELETE) and for a
particular api method that works with XHR HTTP request.

1. Creates a partial and gets its route url for the get http method.

```php
use Pollen\Http\Response;
use Pollen\Http\ResponseInterface;
use Pollen\Partial\PartialDriver;
use Pollen\Partial\PartialManager;

$partial = new PartialManager();

$partial->register('hello-partial', new class extends PartialDriver {
    public function responseController(...$args) : ResponseInterface{
        return new Response('Hello World');
    }
});

$hello = $partial->get('hello-partial');

// Gets the route url for get HTTP method
echo $partial->getRouteUrl('hello-partial', null, [], 'get');

```

2. Now you can call the route url in your browser.
[Get the partial response](/_partial/hello-partial/responseController)

Obviously, you are free to use your own routing stack and them controller methods instead.