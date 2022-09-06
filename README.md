# bitgo-laravel | Library to integrate bitgo wallet api.

[![Latest Version](https://img.shields.io/github/release/syedbacchu/bitgo-laravel.svg?style=flat-square)](https://github.com/syedbacchu/bitgo-laravel/releases)
[![Issues](https://img.shields.io/github/issues/syedbacchu/bitgo-laravel.svg?style=flat-square)](https://github.com/syedbacchu/bitgo-laravel)
[![Stars](https://img.shields.io/github/stars/syedbacchu/bitgo-laravel.svg?style=social)](https://github.com/syedbacchu/bitgo-laravel)
[![Stars](https://img.shields.io/github/forks/syedbacchu/bitgo-laravel?style=flat-square)](https://github.com/syedbacchu/bitgo-laravel)
[![Total Downloads](https://img.shields.io/packagist/dt/sdtech/bitgo-laravel.svg?style=flat-square)](https://packagist.org/packages/sdtech/bitgo-laravel)

- [About](#about)
- [Requirements](#requirements)
- [Installation](#installation)
- [Configuration](#configuration)
- [Uses](#Uses)

## About

A simple library that help you to integrate bitgo wallet api like create wallet address, deposit, withdrawal, webhook etc.
The current features are :

- User login, user profile.
- Wallet list, also single wallet.
- Generate new wallet address, address list.
- Build transaction, initiate transaction, send transaction.
- Withdrawal / send coins.
- All transaction / approval list.
- Webhook.

## Requirements

* [Laravel 5.5+](https://laravel.com/docs/installation)
* [PHP ^7.4](https://laravel.com/docs/installation)

## Installation
1. From your projects root folder in terminal run:

```bash
    composer require sdtech/bitgo-laravel
```
2. Publish the packages views, config file, assets, and language files by running the following from your projects root folder:

```bash
    php artisan vendor:publish --tag=bitgolaravelapi
```

## configuration
1. Go to your config folder, then open "bitgolaravelapi.php" file
2. here you must add that info or add the info to your .env file .
3.
 ``` bash
    'BITGO_API_BASE_URL' => env('BITGO_API_BASE_URL') ?? "",
    'BITGO_API_ACCESS_TOKEN' => env('BITGO_API_ACCESS_TOKEN') ?? '',
    'BITGO_API_EXPRESS_URL' => env('BITGO_API_EXPRESS_URL') ?? '',
    'BITGO_ENV' => env('BITGO_ENV') ?? 'test',
   ```

## Uses
1. We provide a sample code of functionality that will help you to integrate easily

