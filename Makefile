PROJECT_NAME=zek
up: build
	docker network create web || true
	docker-compose -p ${PROJECT_NAME} -f environment/development.yml up -d --force-recreate
build:
	docker-compose -p ${PROJECT_NAME} -f environment/development.yml build
down:
	docker-compose -p ${PROJECT_NAME} -f environment/development.yml down
	docker network remove web || true
migrate:
	docker exec web-php-fpm bash -c "php bin/console doctrine:database:create || true"
	docker exec web-php-fpm bash -c "php bin/console doctrine:schema:update --force"
install-composer:
	docker exec web-php-fpm bash -c "php composer.phar install"
test:
	docker exec web-php-fpm bash -c "./vendor/bin/simple-phpunit"
