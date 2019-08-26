.DEFAULT_GOAL:= help
.PHONY: phpstan tests coverage view-coverage cs docs view-docs tag

phpstan: ## Performs a static analysis
	vendor/bin/phpstan analyse src tests/JWT -c phpstan.neon --level=max --no-progress -vvv

tests: phpstan ## Executes the test suite
	vendor/bin/phpunit

coverage: ## Executes the test suite and generates code coverage reports
	@vendor/bin/phpunit --coverage-html=build/coverage

view-coverage: ## Shows the code coverage report
	open build/coverage/index.html

cs: ## Fixes coding standard problems
	@vendor/bin/php-cs-fixer fix || true

tag: ## Creates a new signed git tag
	$(if $(TAG),,$(error TAG is not defined. Pass via "make tag TAG=2.0.1"))
	@echo Tagging $(TAG)
	chag update $(TAG)
	git add --all
	git commit -m 'Release $(TAG)'
	git tag -s $(TAG) -m 'Release $(TAG)'

help:
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-16s\033[0m %s\n", $$1, $$2}'
