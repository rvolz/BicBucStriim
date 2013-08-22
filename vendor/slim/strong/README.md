# Strong Authentication Package

[![Build Status](https://travis-ci.org/silentworks/Strong.png?branch=master)](https://travis-ci.org/silentworks/Strong)

## Changes
- pdo driver needs pdo object instead of a connection string
- namespaces
- hashPassword method available directly from Strong class instead of calling getProvider method first
- password hash is now using [password_compat](https://github.com/ircmaxell/password_compat) dependency
- providers can now be class instance rather than just a string

## Installing with Composer

Install Composer in your project:

    curl -s https://getcomposer.org/installer | php

Create a composer.json file in your project root:

    {
        "require": {
            "slim/strong": "dev-master"
        }
    }

Install via composer:

    php composer.phar install

## Author
[Andrew Smith](https://github.com/silentworks)

## Contributors
[Jakub Westfalewski](https://github.com/jwest)

## ToDo
- Example code
- More Providers
