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

## Desired Use

The desired use is for a host application to be able to do something like:

```php
// Host application is darrynten/xero-accounting-php

$config = [
    'key' => CONSUMER_KEY,
    'secret' => CONSUMER_SECRET,
    'applicationType' => APPLICATION_TYPE,
];

$xeroClient = new XeroOauth($config);
$result = $xeroClient->request('GET', 'TaxRates');
```

This will require the package to be able to track state, tokens etc

## Current Use

This is the current basic usage of this package.

TODOs have been placed where work is required.

# Public auth example for RequestHandler

```php
/**
 *
 * TODO this package should figure this all out based on
 * information passed into XeroOauth - use the config
 * factory to do this.
 *
 * This is currently forcing the host application to keep track
 * of all this information.
 *
 * We can use darrynten/any-cache to store tokens
 */

$config [
    'key' => CONSUMER_KEY,
    'secret' => CONSUMER_SECRET,
    /**
     * TODO
     *
     * The host application should only have to pass in the app
     * type and the factory should be used to get the appropriate
     * signing mechanisms etc
     *
     * This applies to as much config as possible
     */
    'sign_with' => 'HMAC-SHA1',
    // other options
]

// Get Request Token
$handler = new RequestHandler($config);

/**
 * TODO
 *
 * The host application should not concern itself with this, it should
 * only have to `$xeroClient->request('GET', 'Currencies')`
 *
 */
try {
    $handler->request('GET', 'Currencies');
} catch (AuthException $e) {
    $authData = $handler->getAuthData();
    // we store oauth_token from $authData and direct user to
    // https://api.xero.com/oauth/Authorize?oauth_token=REQUEST_TOKEN_HERE
}

/**
 * TODO
 *
 * All of the below should be kept and tracked using this package.
 *
 * Host applications that would need redirect urls etc would need
 * to provide this in the config.
 *
 * There is configuration factory functionality in place for the
 * three types of applications (public, partner, private)
 *
 */

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

/**
 * TODO
 *
 * Tokens that come back like this should be tracked internally
 * using the darrynten/any-cache package
 *
 * Expires etc can all be tracked in a cache with that package.
 *
 */
// 'token_verified' is true so we can use ACCESS_TOKEN_HERE to make requests
*/

// Example of real request
$config['token'] = ACCESS_TOKEN_HERE;
$config['token_secret'] = TOKEN_SECRET_HERE;
$config['token_expires_in'] = $datetimeObject; // see above
$config['token_verified'] = true;

/**
 * TODO
 *
 * This should be the only thing the host has to worry about
 */
$handler = new RequestHandler($config);
$response = $handler->request('GET', 'Currencies');

/*
{
  "Id": "3f0d9d3a-6540-4668-87a4-06621ff7eac10",
  "Status": "OK",
  "ProviderName": "Public app",
  "DateTimeUTC": "\/Date(1502804191899)\/",
  "Currencies": [
    {
      "Code": "ZAR",
      "Description": "South African Rand"
    }
  ]
}
*/
```

## Delivery

- [x] Public Application Requests
- [x] Private Application Requests
- [x] Partner Application Requests
- [x] Signing
- [ ] Token Tracking

The deliverable is close :)

### Application base

* Guzzle is used for the communications
* You can use darrynten/any-cache to track tokens
* The library will have 100% test coverage

The client is not 100% complete and is a work in progress, details below.

## Documentation

This should eventually fully mimic the documentation available on the site.
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

Rate limiting should be solved in this oauth client, but is outside of
the initial delivery scope.

## Additional Refactoring Idea

# Please discuss! This is _not_ part of deliverable.

Currently XeroOauth creates a Config object and RequestHandler. So all the work with Tokens and Signing is area of responsibility of the RequestHandler, but this class should only do one thing - be responsible for the request handling.

ApplicationConfig objects are just the source of config parameters.

It might be better to use Applications instead of ApplicationConfigs - example:

* XeroOauth creates Application object
* Application object uses RequestHandler to send requests
* Application object uses SignFactory (new object) to sign requests
* Application object is responsible for Authorization process for it's type

Pseudo code example

```
class BaseApplication
{
    ...
    public function __construct($config = [], RequestHandler $requestHandler, SignFactory $signFactory)
    {
        $this->setEssentials($config);
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
```


## Contributing and Testing

There is currently 100% test coverage in the project, please ensure that
when contributing you update the tests. For more info see CONTRIBUTING.md

We would love help getting decent documentation going, please get in touch
if you have any ideas.

## Additional Documentation

https://developer.xero.com/documentation

## Acknowledgements

* [Fergus Strangways-Dixon](https://github.com/fergusdixon)
* [Mikhail Levanov](https://github.com/leor)
* [Vitaliy Likhachev](https://github.com/make-it-git)
