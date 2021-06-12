.DEFAULT_GOAL:= help
.PHONY: phpstan tests coverage view-coverage cs

phpstan: ## Performs a static analysis
	vendor/bin/phpstan

tests: phpstan ## Executes the test suite
	vendor/bin/phpunit

coverage: ## Executes the test suite and generates code coverage reports
	php -dxdebug.mode=coverage vendor/bin/phpunit -v --coverage-html=build/coverage

view-coverage: ## Shows the code coverage report
	php -S localhost:1337 -t build/coverage

cs: ## Fixes coding standard problems
	vendor/bin/php-cs-fixer fix

help:
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-16s\033[0m %s\n", $$1, $$2}'
