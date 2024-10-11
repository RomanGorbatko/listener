UNAME_S := $(shell uname -s)
# because you cannot escape colon in Makefiles
colon := :
$(colon) := :

# Executables (local)
DOCKER_COMP = docker-compose

# Docker containers
PHP_CONTAINER_EXEC = $(DOCKER_COMP) exec cli
PHP_CONTAINER_EXEC_NO_DEBUG = $(DOCKER_COMP) exec -e XDEBUG_MODE=off cli

# Executables
PHP      = $(PHP_CONTAINER_EXEC) php
COMPOSER = $(PHP_CONTAINER_EXEC_NO_DEBUG) composer
SYMFONY  = $(PHP_CONTAINER_EXEC) bin/console

# Misc
.DEFAULT_GOAL = help
.PHONY        : help build up start down logs sh composer vendor sf cc

DOCKER_COMPOSE = docker-compose

## —— 🎵 🐳 The Symfony Docker Makefile 🐳 🎵 ——————————————————————————————————
help: ## Outputs this help screen
	@grep -E '(^[a-zA-Z0-9\./_-]+:.*?##.*$$)|(^##)' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}{printf "\033[32m%-30s\033[0m %s\n", $$1, $$2}' | sed -e 's/\[32m##/[33m/'

## —— Composer 🧙‍♂️ ———————————————————————————————————————————————————————————————
composer-install: ## Install composer dependencies
	@$(COMPOSER) install

composer-update: ## Update composer dependencies
	@$(COMPOSER) update

composer-require: ## Install new composer dependency. Pass the parameter "package=" to install a given package, example: make composer-require package=doctrine/doctrine-bundle
	@$(COMPOSER) require $(package)

composer-remove: ## Remove composer dependency. Pass the parameter "package=" to remove a given package, example: make composer-remove package=doctrine/doctrine-bundle
	@$(COMPOSER) remove $(package)

composer-require-dev: ## Install new composer dev dependency. Pass the parameter "package=" to install a given package, example: make composer-require-dev package=symfony/maker-bundle
	@$(COMPOSER) require --dev $(package)

## —— Docker 🐳 ————————————————————————————————————————————————————————————————
build: ## Builds the Docker images
	$(info ************  Build docker images ************)
	@$(DOCKER_COMP) build --pull
	@$(DOCKER_COMP) build --pull cli

up: ## Start the docker hub in detached mode (no logs)
	@$(DOCKER_COMP) up --detach

start: build up ## Build and start the containers

down: ## Stop the docker hub
	@$(DOCKER_COMP) down --remove-orphans

reboot: ## equivalent to "make down" & "make start"
	@$(DOCKER_COMP) down up

restart-app: ## docker-compose restart app
	@$(DOCKER_COMP) restart app

logs: ## Show live logs
	@$(DOCKER_COMP) logs --tail=0 --follow

logs-app: ## Show live logs of container "app"
	@$(DOCKER_COMP) logs app --tail=0 --follow

sh: ## Connect to the PHP CLI container
	@$(PHP_CONTAINER_EXEC) sh

sh-app: ## Connect to the PHP Roadrunner container
	$(DOCKER_COMP) exec app sh

ps: ## process status
	@$(DOCKER_COMP) ps

run: ## Run command with "run --rm cli". Pass the parameter "c="
	@$(eval c ?=)
	@$(PHP_CONTAINER_EXEC) $(c)

## —— Symfony 🎵 ———————————————————————————————————————————————————————————————
sf: ## List all Symfony commands or pass the parameter "c=" to run a given command, example: make sf c=about
	@$(eval c ?=)
	@$(SYMFONY) $(c)

cc: c=c:c ## Clear the cache
cc: sf

db-migrate: ## Execute migrations
	$(info ************  Execute migrations ************)
	@$(SYMFONY) doctrine$(:)migrations$(:)migrate --no-interaction

db-migrate-down: ## Execute migrations
	@$(SYMFONY) doctrine$(:)migrations$(:)migrate prev --no-interaction

db-migration-diff: ## Execute migrations
	@$(SYMFONY) doctrine$(:)migrations$(:)diff --no-interaction

## —— Checks & fixers 📝🛠️ ——————————————————————————————————————————————————————————————
cs-fixer: ## Run PHP CS Fixer
	@$(PHP_CONTAINER_EXEC_NO_DEBUG) ./vendor/bin/php-cs-fixer fix --diff --verbose

all-checks: cs-fixer-check  phpstan phpunit composer-license-check osv-scan ## Run ALL checks

cs-fixer-check: ## Run PHP CS Fixer in dry-run mode
	$(info ************  Start code style checks (php-cs-fixer) ************)
	@$(PHP_CONTAINER_EXEC_NO_DEBUG) ./vendor/bin/php-cs-fixer fix --dry-run --diff --verbose

phpstan: ## Run PHPStan
	$(info ************  Start code static analys (phpstan) ************)
	@$(PHP_CONTAINER_EXEC_NO_DEBUG) php -d memory_limit=2G vendor/bin/phpstan

phpunit: ## PHPUnit
	$(info ************  Start tests (PHPUnit) ************)
	@$(PHP_CONTAINER_EXEC_NO_DEBUG) bin/phpunit

phpunit-testdox: ## PHPUnit
	$(info ************  Start tests (PHPUnit) ************)
	@$(PHP_CONTAINER_EXEC_NO_DEBUG) bin/phpunit --testdox

recreate-test-database:
	$(info ************  Start drop & create new database for test env ************)
	@$(PHP_CONTAINER_EXEC_NO_DEBUG) bin/console doctrine:database:drop --env=test --force
	@$(PHP_CONTAINER_EXEC_NO_DEBUG) bin/console doctrine:database:create --env=test
	@$(PHP_CONTAINER_EXEC_NO_DEBUG) bin/console doctrine:query:sql "CREATE EXTENSION postgis;" --env=test
	@$(PHP_CONTAINER_EXEC_NO_DEBUG) bin/console doctrine:migration:migrate --env=test --no-interaction

recreate-dev-database:
	$(info ************  Start drop & create new database for dev env ************)
	@$(PHP_CONTAINER_EXEC_NO_DEBUG) bin/console doctrine:database:drop --env=dev --force
	@$(PHP_CONTAINER_EXEC_NO_DEBUG) bin/console doctrine:database:create --env=dev
	@$(PHP_CONTAINER_EXEC_NO_DEBUG) bin/console doctrine:query:sql "CREATE EXTENSION postgis;" --env=dev
	@$(PHP_CONTAINER_EXEC_NO_DEBUG) bin/console doctrine:migration:migrate --env=dev --no-interaction

composer-license-check: ## Scan all vendor dependencies for license issues
	$(info ************  Start scan all vendor dependencies for license issues (composer-license-checker) ************)
	@$(PHP_CONTAINER_EXEC_NO_DEBUG) vendor/bin/composer-license-checker check \
										--allowlist MIT \
                                        --allowlist Apache-2.0 \
										--allowlist BSD-2-Clause \
										--allowlist BSD-3-Clause \
										--allowlist WTFPL \
										--blocklist GPL \
										--allow dominikb/composer-license-checker

osv-scan:
	$(info ************  Start scan all vendor dependencies on known vulnerabilities (osv-scanner) ************)
	@$(PHP_CONTAINER_EXEC_NO_DEBUG) osv-scanner --lockfile composer.lock
