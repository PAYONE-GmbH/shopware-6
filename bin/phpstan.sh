#!/usr/bin/env bash

vendor/bin/phpstan analyse -l 6 -c phpstan.neon --autoload-file=../../../vendor/autoload.php tests src
