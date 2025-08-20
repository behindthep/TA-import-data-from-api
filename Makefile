worker:
#	php artisan queue:work
	php artisan horizon

import:
	php artisan import:api-data

start:
	php artisan serve --host 0.0.0.0 --port 8000

setup:
	composer install
	cp -n .env.example .env

migrate:
	php artisan migrate

console:
	php artisan tinker

log:
	tail -f storage/logs/laravel.log

test:
	php artisan test

lint:
	composer exec --verbose phpcs -- app tests

lint-fix:
	composer exec --verbose phpcbf -- app tests
