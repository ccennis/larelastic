all: init

init: composer-install vendor-install

composer-install:
	curl -s http://getcomposer.org/installer | php -- --install-dir=bin

vendor-install:
	php bin/composer.phar install

vendor-update:
	php bin/composer.phar update

phpunit-install:
	curl https://phar.phpunit.de/phpunit-5.7.9.phar -s -L -o bin/phpunit.phar

phpunit:
	bin/phpunit.phar

test: phpunit-install phpunit

clean: clear-composer

clear-composer:
	rm -rf vendor
	rm composer.lock
