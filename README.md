Github WebHook PSR-7 / PSR-15 middleware
==================

[![Build
Status](https://secure.travis-ci.org/Swop/github-webhook-middleware.png?branch=master)](http://travis-ci.org/Swop/github-webhook-middleware)

This library offers a PSR-7 style & PSR-15 middleware which will verify if the incoming GitHub web hook request is correctly signed.

The provided PSR-7 request will have its `X-Hub-Signature` header checked in order to see if the request was originally performed by GitHub using the correct secret to sign the request.

If the request signature validation fails, a `401` JSON response will be send back.

Installation
------------

The recommended way to install this library is through [Composer](https://getcomposer.org/):

```
composer require "swop/github-webhook-middleware"
```

Usage
------------

### Ex: PSR-7 style middleware using Zend Diactoros Server

```php
<?php

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

use Swop\GitHubWebHook\Security\SignatureValidator;
use Swop\GitHubWebHookMiddleware\GithubWebHook;

$request = \Zend\Diactoros\ServerRequestFactory::fromGlobals();

$middleware = new GithubWebHook(new SignatureValidator(), 'my_secret');

$next = function (RequestInterface $request, ResponseInterface $response) {
    // The security has been check.
    // Do some stuff with the web hook...
    return new \Zend\Diactoros\Response\JsonResponse(['status' => 'ok']);
};

$server = \Zend\Diactoros\Server::createServerFromRequest($middleware, $request);

$server->listen($next);
````

### Ex: PSR-15 middleware using Zend Stratigility

````php
<?php

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

use Zend\Stratigility\MiddlewarePipe;
use Zend\Stratigility\NoopFinalHandler;

use Zend\Diactoros\Server;
use Zend\Diactoros\Response\JsonResponse;

use Swop\GitHubWebHook\Security\SignatureValidator;
use Swop\GitHubWebHookMiddleware\GithubWebHook;

$app = (new MiddlewarePipe())
    ->pipe(new GithubWebHook(new SignatureValidator(), 'my_secret'))
    ->pipe('/', function (RequestInterface $request, ResponseInterface $response) {
        // The security has been check.
        // Do some stuff with the web hook...
        return new JsonResponse(['status' => 'OK']);
    });

$request = \Zend\Diactoros\ServerRequestFactory::fromGlobals();

Server::createServerFromRequest($app, $request)
    ->listen(new NoopFinalHandler())
;
````

Contributing
------------

See [CONTRIBUTING](https://github.com/Swop/github-webhook-middleware/blob/master/CONTRIBUTING.md) file.

Original Credits
------------

* [Sylvain MAUDUIT](https://github.com/Swop) ([@Swop](https://twitter.com/Swop)) as main author.


License
------------

This library is released under the MIT license. See the complete license in the bundled [LICENSE](https://github.com/Swop/github-webhook-middleware/blob/master/LICENSE) file.
