#!/bin/bash

docker exec -it pamsdk-php_php56_1 composer test tests
docker exec -it pamsdk-php_php70_1 composer test tests