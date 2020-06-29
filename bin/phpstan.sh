#!/usr/bin/env bash

vendor/bin/phpstan analyse -c phpstan.neon --autoload-file=../../../vendor/autoload.php tests src
