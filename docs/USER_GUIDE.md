![CrowdSec Logo](images/logo_crowdsec.png)
# CrowdSec CAPI PHP client

## User Guide


<!-- START doctoc generated TOC please keep comment here to allow auto update -->
<!-- DON'T EDIT THIS SECTION, INSTEAD RE-RUN doctoc TO UPDATE -->
**Table of Contents**

- [Description](#description)
- [Features](#features)
- [Quick start](#quick-start)
  - [Installation](#installation)
  - [Watcher instantiation](#watcher-instantiation)
    - [CAPI calls](#capi-calls)
      - [Register your watcher](#register-your-watcher)
      - [Login](#login)
      - [Push signals/alerts](#push-signalsalerts)
      - [Get Decisions stream list](#get-decisions-stream-list)
- [Override the curl request handler](#override-the-curl-request-handler)
  - [Custom implementation](#custom-implementation)
  - [Ready to use `file_get_contents` implementation](#ready-to-use-file_get_contents-implementation)
- [Example scripts](#example-scripts)
  - [Register a watcher](#register-a-watcher)
  - [Login as watcher](#login-as-watcher)
  - [Get decisions stream](#get-decisions-stream)
  - [Push signals](#push-signals)

<!-- END doctoc generated TOC please keep comment here to allow auto update -->


## Description

This client allows you to interact with the CrowdSec CAPI.


## Features

- CrowdSec CAPI available endpoints
  - Register a watcher
  - Login as a watcher
  - Push signals
  - Retrieve decisions stream list
- Overridable request handler (`curl` by default, `file_get_contents` also available)
- Large PHP matrix compatibility: 5.3, 5.4, 5.5, 5.6, 7.0, 7.1, 7.2, 7.3, 7.4, 8.0 and 8.1


## Quick start

### Installation

First, install CrowdSec CAPI PHP Client via the [composer](https://getcomposer.org/) package manager:
```bash
composer require crowdsec/capi-client
```

Please see the [Installation Guide](./INSTALLATION_GUIDE.md) for mor details.

### Watcher instantiation

To instantiate a watcher, you have to know its `machine_id` and `password`:

```php
use CrowdSec\CapiClient\Watcher;

$configs = array('machine_id' => '<YOUR_MACHINE_ID>', 'password' => '<MACHINE_PASSWORD>');
$client = new Watcher($configs);

````

By default, a watcher will use the CrowdSec development url. If you are ready to use the CrowdSec production 
environment, you have to add the key `prod` with value `true` in the `$configs` array: 
```php
$configs = array(
        'machine_id' => '<MACHINE_ID>', 
        'password' => '<MACHINE_PASSWORD>',
        'prod' => true
);
$client = new WatcherClient($configs);
```

#### CAPI calls

Once your watcher in instantiated, you have to register it before doing more actions:

##### Register your watcher

```php
$client->register();
```

Note that once your watcher has been registered, you don't have to register it a second time.

Then, you will be able to interact with CAPI:


##### Login

Sign in to get a valid token:

```php
$client->login();
```

##### Push signals/alerts

You can push an array of signals to CAPI:

```php
/**
* @see https://crowdsecurity.github.io/api_doc/index.html?urls.primaryName=CAPI#/watchers/post_signals for fields details
 */
$signals = array('...');
$client->pushSignals($signals);
```

##### Get Decisions stream list

To retrieve the list of top decisions, you can do the following call:

```php
$client->getStreamDecisions();
```


## Override the curl request handler

### Custom implementation

By default, the `Watcher` object will do curl requests to call the CAPI. If for some reason, you don't want to 
use curl then you can create your own request handler class and pass it as a second parameter of the `Watcher` 
constructor. 

Your custom request handler class must implement the `RequestHandlerInterface` interface, and you will have to 
explicitly 
write an `handle` method:

```php
<?php

use CrowdSec\CapiClient\HttpMessage\Request;
use CrowdSec\CapiClient\HttpMessage\Response;
use CrowdSec\CapiClient\RequestHandler\RequestHandlerInterface;

class CustomRequestHandler implements RequestHandlerInterface
{
    /**
     * Performs an HTTP request and returns a response.
     *
     * @param Request $request
     * @return Response
     */
    public function handle(Request $request)
    {
        /**
        * Make your own implementation of an HTTP request process.
        * Request object contains a URI, a method, headers (optional) and parameters (optional).
        * Response object contains a json body, a status code and headers (optional).
        */
    }
}

```

Once you have your custom request handler, you can instantiate the watcher that will use it:

```php
use CrowdSec\CapiClient\Watcher;
use CustomRequestHandler;

$configs = array('machine_id' => '<YOUR_MACHINE_ID>', 'password' => '<MACHINE_PASSWORD>');

$requestHandler = new CustomRequestHandler();

$client = new Watcher($configs, $requestHandler);

```

Then, you can make any of the CAPI calls that we have seen above.


### Ready to use `file_get_contents` implementation

This client comes with a `file_get_contents` request handler that you can use instead of the default curl request 
handler. To use it, you should instantiate it and pass the created object as a parameter: 

```php
use CrowdSec\CapiClient\Watcher;
use CrowdSec\CapiClient\RequestHandler\FileGetContents;

$configs = array('machine_id' => '<YOUR_MACHINE_ID>', 'password' => '<MACHINE_PASSWORD>');

$requestHandler = new FileGetContents();

$client = new Watcher($configs, $requestHandler);

```



## Example scripts


You will find some ready-to-use php scripts in the `tests/scripts` folder. These scripts could be usefull to better 
understand what you can do with this client. For each script, you have to pass a `MACHINE_ID` and a `PASSWORD` as 
arguments.

### Register a watcher

```bash
php tests/scripts/watcher/register.php MACHINE_ID PASSWORD
```

### Login as watcher

```bash
php tests/scripts/watcher/login.php MACHINE_ID PASSWORD
```

If you want to see how the request handler override could work, you could run: 

```bash
php tests/scripts/watcher/request-handler-override/login.php MACHINE_ID PASSWORD
```

### Get decisions stream

```bash
php tests/scripts/watcher/decisions-stream.php MACHINE_ID PASSWORD
```

Or, with the `file_get_contents` handler:

```bash
php tests/scripts/watcher/request-handler-override/decisions-stream.php MACHINE_ID PASSWORD
```

### Push signals

As the `pushSignals` method need an array as parameter, we use a json to test this command line script: 

```bash
php tests/scripts/watcher/signals.php MACHINE_ID PASSWORD "[{\"machine_id\":\"MACHINE_ID\",\"message\":\"Ip 1.1.1.1 performed 'crowdsecurity\/http-path-traversal-probing' (6 events over 29.992437958s) at 2020-11-06 20:14:11.189255784 +0000 UTC m=+52.785061338\",\"scenario\":\"crowdsecurity\/http-path-traversal-probing\",\"scenario_hash\":\"\",\"scenario_version\":\"\",\"source\":{\"id\":1,\"as_name\":\"TEST\",\"cn\":\"FR\",\"ip\":\"1.1.1.1\",\"latitude\":48.9917,\"longitude\":1.9097,\"range\":\"1.1.1.1\/32\",\"scope\":\"test\",\"value\":\"1.1.1.1\"},\"start_at\":\"2020-11-06T20:13:41.196817737Z\",\"stop_at\":\"2020-11-06T20:14:11.189252228Z\"},{\"machine_id\":\"MACHINE_ID\",\"message\":\"Ip 2.2.2.2 performed 'crowdsecurity\/http-probing' (6 events over 29.992437958s) at 2020-11-06 20:14:11.189255784 +0000 UTC m=+52.785061338\",\"scenario\":\"crowdsecurity\/http-probing\",\"scenario_hash\":\"\",\"scenario_version\":\"\",\"source\":{\"id\":2,\"as_name\":\"TEST\",\"cn\":\"FR\",\"ip\":\"2.2.2.2\",\"latitude\":48.9917,\"longitude\":1.9097,\"range\":\"2.2.2.2\/32\",\"scope\":\"test\",\"value\":\"2.2.2.2\"},\"start_at\":\"2020-11-06T20:13:41.196817737Z\",\"stop_at\":\"2020-11-06T20:14:11.189252228Z\"}]"
```
