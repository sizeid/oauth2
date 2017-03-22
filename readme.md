# SizeID Businnes API OAuth2


[![Build Status](https://api.travis-ci.org/sizeid/oauth2.svg?branch=master)](https://travis-ci.org/sizeid/oauth2)

Package for simple communication with [SizeID Business API](https://sizeid.com/business).

## Installation into existing project

1. Get the code
```
composer require sizeid/oauth2
```
2. Get `clientId` and `clientSecret` from your [SizeID for Business account](https://business.sizeid.com). Free tariff available.

3. Initialize communication objects

- for **client endpoints** calls see [examples/clientApi.php](examples/clientApi.php)
- for **user endpoints** calls see [examples/userApi.php](examples/userApi.php)


## Examples

1. Get the code
```
composer create-project sizeid/oauth2
```
2. Get `clientId` and `clientSecret` from your [SizeID for Business account](https://business.sizeid.com). Free tariff available.

3. Navigate to `examples` directory, copy `config.example.php` to `config.php`, change constants `CLIENT_ID` and `CLIENT_SECRET`, run example file with webserver.




