Etherpad-Lite PHP API
=====================

## What is it?

PHP Client for Etherpad-Lite API. Incomplete and unstable for now.

### Prerequisites

You MUST use Etherpad-Lite >= 1.2.1.

### Install Composer

1. In the root directory, run:

		$ curl -s http://getcomposer.org/installer | php -- --install-dir=bin
	
2. At first, in order to populate vendor directory with third-party bundles, run:

		$ php bin/composer.phar install

## Testing

In order to run the test suite, you need to install phpunit:

	$ php bin/composer.phar install --dev

Afterwards you can run the test suite:

	$ php vendor/phpunit/phpunit/phpunit.php
