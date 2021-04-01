install-local:
	composer install

build:
	docker build -t finance-ia .

test:
	docker run -it finance-ia

test-local:
	./vendor/bin/phpunit

docs:
	docker run --rm -v ${PWD}:/data phpdoc/phpdoc:3 run -d src -t phpdoc
