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

.PHONY: init
init: up install hooks db ## First-time project setup: start containers, install deps, configure git hooks, run migrations
	@echo ""
	@echo "✅ Joblog is ready!"
	@echo ""
	@echo "  Frontend → http://joblog.dev"
	@echo "  API      → http://api.joblog.dev"
	@echo ""
	@if ! grep -q "joblog.dev" /etc/hosts 2>/dev/null; then \
		echo "⚠️  Add the following line to /etc/hosts:"; \
		echo ""; \
		echo "  127.0.0.1 joblog.dev api.joblog.dev"; \
		echo ""; \
		echo "  Run: sudo sh -c 'echo \"127.0.0.1 joblog.dev api.joblog.dev\" >> /etc/hosts'"; \
	fi

.PHONY: hosts
hosts: ## Add joblog.dev domains to /etc/hosts (requires sudo)
	@if grep -q "joblog.dev" /etc/hosts 2>/dev/null; then \
		echo "✅ joblog.dev already in /etc/hosts"; \
	else \
		sudo sh -c 'echo "127.0.0.1 joblog.dev api.joblog.dev" >> /etc/hosts'; \
		echo "✅ Added joblog.dev and api.joblog.dev to /etc/hosts"; \
	fi

.PHONY: hooks
hooks: ## Install git hooks for pre-push checks
	git config core.hooksPath .githooks

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
test-behat: ## Run Behat acceptance tests (excluding fixtures)
	$(API) vendor/bin/behat --tags='~@fixtures'

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

.PHONY: lint-frontend-fix
lint-frontend-fix: ## Auto-fix frontend lint and format issues
	$(FRONTEND) npx eslint . --fix
	$(FRONTEND) npm run format

.PHONY: phpstan
phpstan: ## Run PHPStan static analysis
	@if [ -z "$$(docker compose exec -T api find src -name '*.php' -not -name 'Kernel.php' 2>/dev/null)" ]; then \
		echo "No PHP files to analyse (skipping PHPStan)"; \
	else \
		$(API) vendor/bin/phpstan analyse --no-progress; \
	fi

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

.PHONY: db
db: ## Drop, create, migrate the database, load fixtures, and take snapshot
	$(API) php bin/console doctrine:database:drop --if-exists --force --no-interaction
	$(API) php bin/console doctrine:database:create --no-interaction
	$(API) php bin/console doctrine:migrations:migrate --no-interaction
	$(API) rm -f var/snapshot.dump
	$(API) vendor/bin/behat --tags=fixtures

.PHONY: migrate
migrate: ## Apply database migrations
	$(API) php bin/console doctrine:migrations:migrate --no-interaction

.PHONY: migrate-diff
migrate-diff: ## Generate a migration from entity changes
	$(API) php bin/console doctrine:migrations:diff

.PHONY: migrate-status
migrate-status: ## Show database migration status
	$(API) php bin/console doctrine:migrations:status

# -----------------------------------------------------------------------------
# Debugging
# -----------------------------------------------------------------------------

.PHONY: xdebug-on
xdebug-on: ## Enable Xdebug step debugging (restarts the api container)
	XDEBUG_MODE=debug $(DOCKER_COMPOSE) up -d api

.PHONY: xdebug-off
xdebug-off: ## Disable Xdebug (restarts the api container)
	XDEBUG_MODE=off $(DOCKER_COMPOSE) up -d api

.PHONY: debug-frontend
debug-frontend: ## Start frontend with Node.js inspector on port 9229
	FRONTEND_SCRIPT=debug $(DOCKER_COMPOSE) up -d frontend

.PHONY: debug-on
debug-on: xdebug-on debug-frontend ## Enable both backend and frontend debugging
