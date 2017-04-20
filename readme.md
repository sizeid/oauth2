# SizeID Business API OAuth2

[![Build Status](https://api.travis-ci.org/sizeid/oauth2.svg?branch=master)](https://travis-ci.org/sizeid/oauth2)
[![Coverage Status](https://coveralls.io/repos/github/sizeid/oauth2/badge.svg?branch=php5.4)](https://coveralls.io/github/sizeid/oauth2?branch=php5.4)

Package for simple communication with [SizeID Business API](https://api.business.sizeid.com/). 
For more information see [sizeid/oauth2 documentation](http://oauth2.sizeid.com/) and [SizeID Business API documentation](https://business.sizeid.com/integration.business-api/).

## Installation into existing project

1. Get the code
```
composer require sizeid/oauth2
```
2. Get `clientId` and `clientSecret` from your [SizeID for Business account](https://business.sizeid.com/integration.settings/). Free tariff available.

3. Initialize communication objects

- for **client endpoints** calls see [examples/clientApi.php](examples/clientApi.php)
- for **user endpoints** calls see [examples/userApi.php](examples/userApi.php)
- for **login with sizeid** using popup see [examples/popupLogin.php](examples/popupLogin.php)


## Examples

1. Get the code
```
composer create-project sizeid/oauth2
```
2. Get `clientId` and `clientSecret` from your [SizeID for Business account](https://business.sizeid.com/integration.settings/). Free tariff available.

3. Navigate to `examples` directory, copy `config.example.php` to `config.php`, change constants `CLIENT_ID` and `CLIENT_SECRET`, run example file with webserver.




