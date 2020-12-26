NelmioApiDocBundle
==================

## About our Fork

**Disclaimer**: This is fork of [nelmio/NelmioApiDocBundle](https://github.com/nelmio/NelmioApiDocBundle) created 
for [our](https://nomasolutions.pl) internal use.

First of all we needed to change the mechanism behind model registration so the JMS model hash
won't be created based on groups and FQCN but on fields list.

Let's find out if we can manage to do this :) 

## Introduction

The **NelmioApiDocBundle** bundle allows you to generate a decent documentation
for your APIs.

## Migrate from 2.x to 3.0

[To migrate from 2.x to 3.0, follow our guide.](https://github.com/nelmio/NelmioApiDocBundle/blob/3.x/UPGRADE-3.0.md)

## Installation

Open a command console, enter your project directory and execute the following command to download the latest version of this bundle:

```
composer require nelmio/api-doc-bundle
```

## Documentation

[Read the documentation on symfony.com](https://symfony.com/doc/3.x/bundles/NelmioApiDocBundle/index.html)

## Contributing

See
[CONTRIBUTING](https://github.com/nelmio/NelmioApiDocBundle/blob/3.x/CONTRIBUTING.md)
file.

## Running the Tests

Install the [Composer](http://getcomposer.org/) dependencies:

    git clone https://github.com/nelmio/NelmioApiDocBundle.git
    cd NelmioApiDocBundle
    git checkout 3.x
    composer update

Then run the test suite:

    ./phpunit

## License

This bundle is released under the MIT license.
