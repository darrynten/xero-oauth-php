# xero-oauth-php

![Travis Build Status](https://travis-ci.org/darrynten/xero-oauth-php.svg?branch=dev)
![StyleCI Status](https://styleci.io/repos/97003793/shield?branch=dev)
[![codecov](https://codecov.io/gh/darrynten/xero-oauth-php/branch/dev/graph/badge.svg)](https://codecov.io/gh/darrynten/xero-oauth-php)
[![Codacy Badge](https://api.codacy.com/project/badge/Grade/e4ff0345d1424fc680c9e3c71b169e12)](https://www.codacy.com/app/darrynten/xero-oauth-php?utm_source=github.com&amp;utm_medium=referral&amp;utm_content=darrynten/xero-oauth-php&amp;utm_campaign=Badge_Grade)
[![Code Climate](https://codeclimate.com/github/darrynten/xero-oauth-php/badges/gpa.svg)](https://codeclimate.com/github/darrynten/xero-oauth-php)
[![Issue Count](https://codeclimate.com/github/darrynten/xero-oauth-php/badges/issue_count.svg)](https://codeclimate.com/github/darrynten/xero-oauth-php)
![Packagist Version](https://img.shields.io/packagist/v/darrynten/xero-oauth-php.svg)
![MIT License](https://img.shields.io/github/license/darrynten/xero-oauth-php.svg)

[Xero](https://developer.xero.com) OAuth Client for PHP

# This is work in progress software

This will be a 100% fully unit tested and fully featured unofficial 
PHP Oauth client for Xero.

This is NOT An SDK, this is _purely_ an oauth client and request handler
that follows Xeros rules and conventions.

PHP 7.0+

## Basic use

```php
$config = [
    'key' => CONSUMER_KEY, 
    'secret' => CONSUMER_SECRET,
    'applicationType' => APPLICATION_TYPE
];

$xeroClient = new XeroOauth($config);
$result = $xeroClient->request('GET', 'TaxRates');
```

### Definitions

# TODO

## Features

This is the basic outline of the project and is a work in progress.

## Usage Examples

# Public auth example for RequestHandler
```php
$config [
    'key' => CONSUMER_KEY,
    'secret' => CONSUMER_SECRET,
    'sign_with' => 'HMAC-SHA1',
    // other options
]

// Get Request Token
$handler = new RequestHandler($config);
try {
    $handler->request('GET', 'Currencies');
} catch (AuthException $e) {
    $authData = $handler->getAuthData();
    // we store oauth_token from $authData and direct user to
    // https://api.xero.com/oauth/Authorize?oauth_token=REQUEST_TOKEN_HERE
}

// Get Access Token
// We assume that user granted access by visiting this url
// https://app.xero.com/oauth/APIAuthorise?oauth_token=REQUEST_TOKEN_HERE
$config['token'] = REQUEST_TOKEN_HERE;
$config['token_secret'] = $authData['oauth_token_secret']; // see above
$config['oauth_verifier'] = '563499'; // we collect after user grants permission
$handler = new RequestHandler($config);
$handler->request('GET', 'Currencies');
$authData = $handler->getAuthData();

/*
Array
(
    [oauth_token] => ACCESS_TOKEN_HERE
    [oauth_token_secret] => TOKEN_SECRET_HERE
    [oauth_expires_in] => DateTime Object
        (
            [date] => 2017-08-15 14:03:58.079112
            [timezone_type] => 3
            [timezone] => UTC
        )

    [oauth_verifier] => 563499
    [token_verified] => true
)
// 'token_verified' is true so we can use ACCESS_TOKEN_HERE to make requests
*/

// Example of real request
$config['token'] = ACCESS_TOKEN_HERE;
$config['token_secret'] = TOKEN_SECRET_HERE;
$config['token_expires_in'] = $datetimeObject; // see above
$config['token_verified'] = true;
$handler = new RequestHandler($config);
$response = $handler->request('GET', 'Currencies');

/*
{
  "Id": "4f2d7d6a-6531-4674-87a4-06630ff7eac6",
  "Status": "OK",
  "ProviderName": "Testing public app",
  "DateTimeUTC": "\/Date(1502804191938)\/",
  "Currencies": [
    {
      "Code": "RUB",
      "Description": "Russian Ruble"
    }
  ]
}
*/
```

### Application base

* Guzzle is used for the communications (I think we should replace?)
* The library has 100% test coverage
* etc etc

The client is not 100% complete and is a work in progress, details below.

## Documentation

This will eventually fully mimic the documentation available on the site.
https://developer.xero.com/documentation

Each section must have a short explaination and some example code like on
the API docs page.

Checked off bits are complete.

## Different application types

Xero has 3 different types of applications whose auth is handled
differently between them.

### Private Application
https://developer.xero.com/documentation/auth-and-limits/private-applications

### Public Application
https://developer.xero.com/documentation/auth-and-limits/public-applications

### Partner Application
https://developer.xero.com/documentation/auth-and-limits/partner-applications

**Please note that refreshing token in partner application is not tested so when access token expires things can go strange.**

# Notes

### Request Limits

All Xero companies have a request limit of 5000 API requests per day. A maximum of 100 results will be returned for list methods, regardless of the parameter sent through.

Rate limiting must be solved in this oauth client

## Contributing and Testing

There is currently 100% test coverage in the project, please ensure that
when contributing you update the tests. For more info see CONTRIBUTING.md

We would love help getting decent documentation going, please get in touch
if you have any ideas.

## Additional Documentation

https://developer.xero.com/documentation

## Acknowledgements

* [Mikhail Levanov](https://github.com/leor)

## ToDo

* Implement XeroOauth class request method
* Implement work with full 3 legged authentication process (currently there is no implementation for Autentication stage)
* Refactor clas structure and architecture

## Refactoring Idea

Current schema works like XeroOauth creates a Config object and RequestHandler. 
So all the work with Tokens and Signing is area of responsibility of the RequestHandler.
But basically his class must do only 1 thing - be responsible for the request handling. So it must send requests and handles responses.
And the ApplicationConfig objects are just the source of config parameters.

Personally, the better implementation is to use not ApplicationConfigs but Applications.
So workflow would be like:
* XeroOauth creates Application object
* Application object uses RequestHandler to send requests
* Application object uses SignFactory (new object) to sign requests
* Application object is responsible for Authorization process for it's type

Pseudo code example
```php
class BaseApplication
{
    ...
    public function __construct($config = [], RequestHandler $requestHandler, SignFactory $signFactory)
    {
        $this->setEssentians($config);
        $this->setOverrides($config);
        
        $this->requestHandler = $requestHandler;
        $this->requestHandler->init($this->getHandlerOptions);
        
        $this->signFactory = $signFactory;
    }
    
    public function request($method, $service, $parameters)
    {
        if(!$this->token || $this->isTokenExpired()) {
            // request a token and provide upper level with all the data it will need
            // like link to authorisation and current token data
        }
        
        $requestData = $this->combineRequest($service, $parameters);
        $signedRequest = $this->sign($requestData);
        
        $this->requestHandler->request($method, $service, $signedRequest);
    }
    
    private function sign($requestData)
    {
        return $this->signFactory->sign(
            $requestData, $this->getSignOptions()
        );
    }
}

Maybe it'll be useful to create a Token class to store all the Token data and simple token methods
```
