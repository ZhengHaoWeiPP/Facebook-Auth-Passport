

## Install

Install with composer...  `composer require panelplace/laravel-facebook-auth-passport`

### Versions

* Laravel 5.4 and Passport 2.0 only supported at this time

### SETUP
To set the Expiry Token and Refresh Token Expiry add this 2 Line code into your Laravel `.env` file
```php

    TOKEN_EXPIRY_IN = 7
    REFRESH_TOKEN_EXPIRY_IN = 8

```

## Dependencies:
* `"laravel/passport": "^4.0"`
* `"facebook/graph-sdk": "^5.6"`

## Credits:
* https://github.com/danjdewhurst/Laravel-Passport-Facebook-Login
* https://github.com/mirkco/Laravel-Passport-Facebook-Login
* https://github.com/mikemclin/passport-custom-request-grant
