#!/usr/bin/env bash

export TEST_ENV=local
export PHPUNIT_ENVIRONMENT=true

php ${PHPUNIT_PHAR} --verbose --stderr --report-useless-tests -c tests/unit/phpunit.xml --coverage-clover=./artifacts/coverage.xml
bash <(curl -s https://bolt-devops.s3-us-west-2.amazonaws.com/testing/codecov_uploader) -f ./artifacts/coverage.xml -F $TEST_ENV
