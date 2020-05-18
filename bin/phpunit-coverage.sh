#!/usr/bin/env bash

php -d pcov.enabled=1 -d pcov.directory=../../../ vendor/bin/phpunit --configuration phpunit.xml.dist --log-junit phpunit.junit.xml --colors=never --coverage-clover phpunit.clover.xml --coverage-html phpunit-coverage-html --coverage-text
