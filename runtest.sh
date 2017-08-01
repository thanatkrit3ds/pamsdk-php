#!/bin/bash

docker exec -it pamsdkphp_php56_1 composer test tests
docker exec -it pamsdkphp_php70_1 composer test tests