DOCKER_COMPOSE ?= docker compose
API            := $(DOCKER_COMPOSE) exec api
FRONTEND       := $(DOCKER_COMPOSE) exec frontend

.DEFAULT_GOAL := help

# -----------------------------------------------------------------------------
# Help
# -----------------------------------------------------------------------------

.PHONY: help
help: ## Show this help
	@grep -hE '^[a-zA-Z0-9_-]+:.*?## .*$$' $(MAKEFILE_LIST) \
		| sort \
		| awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-18s\033[0m %s\n", $$1, $$2}'

# -----------------------------------------------------------------------------
# Environment
# -----------------------------------------------------------------------------

.PHONY: up
up: ## Start the development environment in the background
	$(DOCKER_COMPOSE) up -d

.PHONY: down
down: ## Stop and remove the development environment
	$(DOCKER_COMPOSE) down

.PHONY: restart
restart: ## Restart all services
	$(DOCKER_COMPOSE) restart

.PHONY: build
build: ## Build the Docker images
	$(DOCKER_COMPOSE) build

.PHONY: ps
ps: ## List running services and their status
	$(DOCKER_COMPOSE) ps

.PHONY: logs
logs: ## Tail logs from all services
	$(DOCKER_COMPOSE) logs -f

# -----------------------------------------------------------------------------
# Dependencies
# -----------------------------------------------------------------------------

.PHONY: install
install: install-backend install-frontend ## Install backend and frontend dependencies

.PHONY: install-backend
install-backend: ## Install backend dependencies
	$(API) composer install

.PHONY: install-frontend
install-frontend: ## Install frontend dependencies
	$(FRONTEND) npm install

# -----------------------------------------------------------------------------
# Shells
# -----------------------------------------------------------------------------

.PHONY: shell-api
shell-api: ## Open a shell in the api container
	$(DOCKER_COMPOSE) exec api sh

.PHONY: shell-frontend
shell-frontend: ## Open a shell in the frontend container
	$(DOCKER_COMPOSE) exec frontend sh

# -----------------------------------------------------------------------------
# Testing
# -----------------------------------------------------------------------------

.PHONY: test
test: test-backend test-frontend ## Run backend and frontend test suites

.PHONY: test-backend
test-backend: test-phpspec test-behat ## Run backend test suites

.PHONY: test-phpspec
test-phpspec: ## Run PHPSpec domain/unit tests
	$(API) vendor/bin/phpspec run

.PHONY: test-behat
test-behat: ## Run Behat acceptance tests
	$(API) vendor/bin/behat

.PHONY: test-frontend
test-frontend: ## Run frontend unit tests
	$(FRONTEND) npm run test

.PHONY: test-e2e
test-e2e: ## Run Playwright end-to-end tests
	$(FRONTEND) npm run test:e2e

# -----------------------------------------------------------------------------
# Quality
# -----------------------------------------------------------------------------

.PHONY: lint
lint: lint-backend lint-frontend ## Lint backend and frontend sources

.PHONY: lint-backend
lint-backend: phpstan php-cs-fixer-check ## Run PHPStan and PHP-CS-Fixer (dry-run)

.PHONY: lint-frontend
lint-frontend: ## Run ESLint and Prettier checks on the frontend
	$(FRONTEND) npm run lint
	$(FRONTEND) npm run format:check

.PHONY: phpstan
phpstan: ## Run PHPStan static analysis
	$(API) vendor/bin/phpstan analyse --no-progress

.PHONY: php-cs-fixer-check
php-cs-fixer-check: ## Check PHP code style (dry-run)
	$(API) vendor/bin/php-cs-fixer check --diff --allow-risky=yes

.PHONY: php-cs-fixer-fix
php-cs-fixer-fix: ## Fix PHP code style issues in-place
	$(API) vendor/bin/php-cs-fixer fix --diff --allow-risky=yes

.PHONY: format
format: php-cs-fixer-fix ## Auto-format all sources
	$(FRONTEND) npm run format

.PHONY: typecheck
typecheck: ## Type-check the frontend
	$(FRONTEND) npm run typecheck

.PHONY: check
check: test lint typecheck ## Run tests, lint and type-check

# -----------------------------------------------------------------------------
# Database
# -----------------------------------------------------------------------------

.PHONY: migrate
migrate: ## Apply database migrations
	$(API) php bin/console doctrine:migrations:migrate --no-interaction

.PHONY: migrate-diff
migrate-diff: ## Generate a migration from entity changes
	$(API) php bin/console doctrine:migrations:diff

.PHONY: migrate-status
migrate-status: ## Show migration status
	$(API) php bin/console doctrine:migrations:status
