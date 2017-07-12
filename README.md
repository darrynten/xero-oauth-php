# xero-oauth-php

![Travis Build Status](https://travis-ci.org/darrynten/xero-oauth-php.svg?branch=dev)
![StyleCI Status](https://styleci.io/repos/xx/shield?branch=dev)
[![codecov](https://codecov.io/gh/darrynten/xero-oauth-php/branch/dev/graph/badge.svg)](https://codecov.io/gh/darrynten/xero-oauth-php)
[![Codacy Badge](https://api.codacy.com/project/badge/Grade/xx)](https://www.codacy.com/app/darrynten/xero-oauth-php?utm_source=github.com&amp;utm_medium=referral&amp;utm_content=darrynten/xero-oauth-php&amp;utm_campaign=Badge_Grade)
[![Code Climate](https://codeclimate.com/github/darrynten/xero-oauth-php/badges/gpa.svg)](https://codeclimate.com/github/darrynten/xero-oauth-php)
[![Issue Count](https://codeclimate.com/github/darrynten/xero-oauth-php/badges/issue_count.svg)](https://codeclimate.com/github/darrynten/xero-oauth-php)
![Packagist Version](https://img.shields.io/packagist/v/darrynten/xero-oauth-php.svg)
![MIT License](https://img.shields.io/github/license/darrynten/xero-oauth-php.svg)

[Xero Oauth]() client for PHP

This is a 100% fully unit tested and (mostly) fully featured unofficial 
PHP Oauth client for Xero

This is NOT An SDK, this is _purely_ an oauth client and request handler
that follows Xeros rules and conventions.

PHP 7.0+

## Basic use

# TODO

### Definitions

# TODO

## Features

This is the basic outline of the project and is a work in progress.

## Usage Examples

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
