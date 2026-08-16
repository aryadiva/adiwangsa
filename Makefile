APP ?= laravel.test
COMPOSE ?= docker compose

.PHONY: up build down logs ps restart fresh test pint artisan

# Clone → run. Builds the app image and starts everything (migrations, seed,
# MinIO bucket provisioning all happen automatically in the container entrypoint).
up:
	@test -f .env || { cp .env.example .env; echo "==> created .env from .env.example"; }
	$(COMPOSE) up -d --build
	@echo ""
	@echo "Dashboard:    http://localhost:$${APP_PORT:-80}/admin"
	@echo "MinIO console: http://localhost:$${FORWARD_MINIO_CONSOLE_PORT:-8900}"
	@echo "Mailpit UI:   http://localhost:$${FORWARD_MAILPIT_DASHBOARD_PORT:-8025}"

build:
	$(COMPOSE) build

down:
	$(COMPOSE) down

logs:
	$(COMPOSE) logs -f --tail=100

ps:
	$(COMPOSE) ps

restart:
	$(COMPOSE) restart

artisan:
	$(COMPOSE) exec $(APP) php artisan $(cmd)

fresh:
	$(COMPOSE) exec $(APP) php artisan migrate:fresh --seed --force

test:
	$(COMPOSE) exec $(APP) php artisan test

pint:
	$(COMPOSE) exec $(APP) ./vendor/bin/pint