#!/bin/bash -e

# -- ONLY INTENDED TO BE RUN WITHIN TRAVIS --
# Kickstarts the test process on Travis

cd /app
./vendor/bin/phpunit

exit $? # IMPORTANT: test runner needs the result code from this process

