# Settings
MAKEFLAGS += --silent --always-make
SHELL := /bin/bash
-include .env

# colors
RED     := $(shell tput -Txterm setaf 1)
GREEN   := $(shell tput -Txterm setaf 2)
YELLOW  := $(shell tput -Txterm setaf 3)
BLUE    := $(shell tput -Txterm setaf 4)
MAGENTA := $(shell tput -Txterm setaf 5)
CYAN    := $(shell tput -Txterm setaf 6)
WHITE   := $(shell tput -Txterm setaf 7)
RESET   := $(shell tput -Txterm sgr0)

# emojis
EMOJI_robot := "🤖️"
EMOJI_ping_pong := "🏓"
EMOJI_face_with_rolling_eyes := "🙄"

COMPOSER_AUTH_JSON := $(shell gh auth token 2>/dev/null | sed 's/.*/{"github-oauth":{"github.com":"&"}}/' || echo '{}')
CURRENT_BRANCH := $(shell git branch --show-current 2>/dev/null || echo develop)
IN2PUBLISH_DEV_VERSION := dev-$(CURRENT_BRANCH)
FOREIGN_ONLY_EMPTY_TABLES_FILE := Tests/Playwright/shared/helpers/foreign-only-empty-tables.txt

## Show this help
help:
	echo "$(EMOJI_interrobang) Makefile help "
	echo ''
	echo 'About this help:'
	echo '  Commands are ${BLUE}blue${RESET}'
	echo '  Targets are ${YELLOW}yellow${RESET}'
	echo '  Descriptions are ${GREEN}green${RESET}'
	echo ''
	echo 'Usage:'
	echo '  ${BLUE}make${RESET} ${YELLOW}<target>${RESET}'
	echo ''
	echo 'Targets:'
	awk '/^[a-zA-Z\-\_0-9]+:/ { \
		helpMessage = match(lastLine, /^## (.*)/); \
		if (helpMessage) { \
			helpCommand = substr($$1, 0, index($$1, ":")+1); \
			helpMessage = substr(lastLine, RSTART + 3, RLENGTH); \
			printf "  ${YELLOW}%-${TARGET_MAX_CHAR_NUM}s${RESET} ${GREEN}%s${RESET}\n", helpCommand, helpMessage; \
		} \
	} \
	{ lastLine = $$0 }' $(MAKEFILE_LIST)

## Choose the right docker compose file for your environment
.link-compose-file:
	echo "$(EMOJI_triangular_ruler) Linking the OS specific compose file"
ifeq ($(shell uname -s), Darwin)
	ln -snf .project/docker/docker-compose.darwin.yaml docker-compose.yaml
else
	ln -snf .project/docker/docker-compose.linux.yaml docker-compose.yaml
endif

stop: .link-compose-file
	docker compose stop
	docker compose down

destroy: stop
	echo "$(EMOJI_litter) Removing the project"
	docker compose down -v --remove-orphans

start: .link-compose-file
	docker compose build --pull
	docker compose up -d

setup: stop destroy .install-packages .create-certificate start .mysql-wait
	@echo "Installing in2publish_core as $(IN2PUBLISH_DEV_VERSION)"
	docker exec -u1000 -e COMPOSER_AUTH='$(COMPOSER_AUTH_JSON)' in2publish_core-local-php-1 composer u -W
	docker exec -u1000 -e COMPOSER_AUTH='$(COMPOSER_AUTH_JSON)' in2publish_core-foreign-php-1 composer u -W
	docker exec -u1000 -e COMPOSER_AUTH='$(COMPOSER_AUTH_JSON)' in2publish_core-local-php-1 vendor/bin/typo3 install:setup --force
	docker exec -u1000 -e COMPOSER_AUTH='$(COMPOSER_AUTH_JSON)' in2publish_core-foreign-php-1 vendor/bin/typo3 install:setup --force
	git checkout Build/local/config/sites/main/config.yaml
	git checkout Build/foreign/config/sites/main/config.yaml
	make restore

## Wait for the mysql container to be fully provisioned
.mysql-wait:
	echo "$(EMOJI_ping_pong) Checking DB up and running"
	while ! docker compose exec -T mysql mysql -uroot -proot local -e "SELECT 1;" &> /dev/null; do \
		echo "$(EMOJI_face_with_rolling_eyes) Waiting for database ..."; \
		sleep 3; \
	done;

.install-packages:
	if [[ "$$OSTYPE" == "linux-gnu" ]]; then \
		if [[ "$$(command -v certutil > /dev/null; echo $$?)" -ne 0 ]]; then sudo apt install libnss3-tools; fi; \
		if [[ "$$(command -v mkcert > /dev/null; echo $$?)" -ne 0 ]]; then sudo curl -L https://github.com/FiloSottile/mkcert/releases/download/v1.4.1/mkcert-v1.4.1-linux-amd64 -o /usr/local/bin/mkcert; sudo chmod +x /usr/local/bin/mkcert; fi; \
	elif [[ "$$OSTYPE" == "darwin"* ]]; then \
	    BREW_LIST=$$(brew ls --formula); \
		if [[ ! $$BREW_LIST == *"mkcert"* ]]; then brew install mkcert; fi; \
		if [[ ! $$BREW_LIST == *"nss"* ]]; then brew install nss; fi; \
	fi;
	mkcert -install > /dev/null

.create-certificate:
	echo "$(EMOJI_secure) Creating SSL certificates for dinghy http proxy"
	mkdir -p $(HOME)/.dinghy/certs/
	PROJECT=$$(echo "$${PWD##*/}" | tr -d '.'); \
	if [[ ! -f $(HOME)/.dinghy/certs/$$PROJECT.docker.key ]]; then mkcert -cert-file $(HOME)/.dinghy/certs/$$PROJECT.docker.crt -key-file $(HOME)/.dinghy/certs/$$PROJECT.docker.key "*.$$PROJECT.docker"; fi;
	if [[ ! -f $(HOME)/.dinghy/certs/${HOST_LOCAL}.key ]]; then mkcert -cert-file $(HOME)/.dinghy/certs/${HOST_LOCAL}.crt -key-file $(HOME)/.dinghy/certs/${HOST_LOCAL}.key ${HOST_LOCAL}; fi;
	if [[ ! -f $(HOME)/.dinghy/certs/${HOST_FOREIGN}.key ]]; then mkcert -cert-file $(HOME)/.dinghy/certs/${HOST_FOREIGN}.crt -key-file $(HOME)/.dinghy/certs/${HOST_FOREIGN}.key ${HOST_FOREIGN}; fi;
	if [[ ! -f $(HOME)/.dinghy/certs/${MAIL_HOST}.key ]]; then mkcert -cert-file $(HOME)/.dinghy/certs/${MAIL_HOST}.crt -key-file $(HOME)/.dinghy/certs/${MAIL_HOST}.key ${MAIL_HOST}; fi;

restore: mysql-restore ensure-foreign-empty-tables fileadmin-restore

define with_playwright_lock
	lockdir=".playwright.lock"; \
	if ! mkdir "$$lockdir" 2>/dev/null; then \
		if [ -f "$$lockdir/pid" ] && kill -0 "$$(cat "$$lockdir/pid")" 2>/dev/null; then \
			echo "Another Playwright task is already running for in2publish_core."; \
			exit 1; \
		fi; \
		rm -f "$$lockdir/pid"; \
		rmdir "$$lockdir" 2>/dev/null || true; \
		mkdir "$$lockdir" || { echo "Could not acquire Playwright lock."; exit 1; }; \
	fi; \
	echo $$$$ > "$$lockdir/pid"; \
	trap 'rm -f "$$lockdir/pid"; rmdir "$$lockdir" 2>/dev/null || true' EXIT; \
	$(1)
endef

define ensure_playwright_stack
	$(MAKE) .link-compose-file; \
	docker compose up -d >/dev/null
endef

define stop_playwright_tasks
	$(MAKE) .link-compose-file; \
	lockdir=".playwright.lock"; \
	if [ -f "$$lockdir/pid" ]; then \
		pid="$$(cat "$$lockdir/pid")"; \
		if kill -0 "$$pid" 2>/dev/null; then \
			kill "$$pid" 2>/dev/null || true; \
			sleep 1; \
			kill -9 "$$pid" 2>/dev/null || true; \
		fi; \
	fi; \
	run_containers="$$(docker compose ps -a --format '{{.Name}}\t{{.Service}}' | awk '$$2 == "playwright" && $$1 ~ /-run-/ { print $$1 }')"; \
	if [ -n "$$run_containers" ]; then \
		docker rm -f $$run_containers >/dev/null 2>&1 || true; \
	fi; \
	docker compose stop playwright >/dev/null 2>&1 || true; \
	rm -f "$$lockdir/pid"; \
	rmdir "$$lockdir" 2>/dev/null || true
endef

## Install Playwright npm dependencies in the isolated core test stack
playwright-install:
	$(call with_playwright_lock,$(call ensure_playwright_stack); docker compose run --rm playwright npm install --silent)

## Prepare Playwright npm dependencies in the isolated core test stack
setup-tests: playwright-install

## Run all Playwright tests in the isolated core test stack. Use FILE= for individual tests.
playwright:
	$(call with_playwright_lock,$(call ensure_playwright_stack); $(MAKE) restore; $(MAKE) typo3-comparedb; $(MAKE) typo3-clearcache; docker compose run --rm -e PLAYWRIGHT_HTML_OPEN=never playwright sh -lc "npm install --silent && npx playwright test $(FILE)")

## Open Playwright UI mode in the isolated core test stack. Use FILE= to filter tests.
playwright-ui:
	$(call with_playwright_lock,$(call ensure_playwright_stack); $(MAKE) restore; $(MAKE) typo3-comparedb; $(MAKE) typo3-clearcache; docker compose stop playwright >/dev/null 2>&1 || true; echo "Open Playwright UI at http://localhost:$(PLAYWRIGHT_UI_PORT)"; docker compose run --rm --service-ports playwright sh -lc "npm install --silent && npx playwright test --ui --ui-host=0.0.0.0 --ui-port=9323 $(FILE)")

## Show the last Playwright HTML report from the isolated core test stack
playwright-report:
	$(call with_playwright_lock,$(call ensure_playwright_stack); docker compose stop playwright >/dev/null 2>&1 || true; echo "Open Playwright report at http://localhost:$(PLAYWRIGHT_UI_PORT)"; docker compose run --rm --service-ports playwright sh -lc "npx playwright show-report --host=0.0.0.0 --port=9323")

## Stop all Playwright tasks for the isolated core test stack
stop-playwright:
	$(call stop_playwright_tasks)

## Create dumps of local and foreign database in dir DUMPS_DIR using mysql-loader
dump-dbs: dump-local-database dump-foreign-database

dump-local-database: .mysql-wait
	echo "$(EMOJI_robot) Dumping the local database to $(DUMPS_DIR)/local"
	docker compose exec local-php /app/Build/local/vendor/bin/mysql-loader dump -r -Hmysql -uroot -proot -Dlocal -f/$(DUMPS_DIR)/local/ -xcache_ -xindex_ -xbackend_layout -xbe_dashboards -xbe_sessions -xfe_sessions -xsys_file_processedfile -xsys_history -xsys_http_report -xsys_lockedrecords -xsys_log -xsys_messenger_messages -xsys_refindex -xtx_in2code_ -xtx_in2publish_notification -xtx_in2publish_wfpn_demand -xtx_in2publishcore_ -xtx_solr_ -Q"sys_registry:entry_namespace != 'core' AND entry_key != 'formProtectionSessionToken'"

dump-foreign-database: .mysql-wait
	echo "$(EMOJI_robot) Dumping the foreign database to $(DUMPS_DIR)/foreign"
	docker compose exec local-php /app/Build/local/vendor/bin/mysql-loader dump -r -Hmysql -uroot -proot -Dforeign -f/$(DUMPS_DIR)/foreign/ -xcache_ -xindex_ -xbackend_layout -xbe_dashboards -xbe_sessions -xfe_sessions -xsys_file_processedfile -xsys_history -xsys_http_report -xsys_lockedrecords -xsys_log -xsys_messenger_messages -xsys_refindex -xtx_in2code_ -xtx_in2publish_notification -xtx_in2publish_wfpn_demand -xtx_in2publishcore_ -xtx_solr_ -Q"sys_registry:entry_namespace != 'core' AND entry_key != 'formProtectionSessionToken'"

## Restores the database from the dump files in DUMPS_DIR
mysql-restore: .mysql-wait
	echo "$(EMOJI_robot) Restoring the local database"
	docker compose exec local-php /app/Build/local/vendor/bin/mysql-loader import -Hmysql -uroot -proot -Dlocal -f/$(DUMPS_DIR)/local/
	echo "$(EMOJI_robot) Restoring the foreign database"
	docker compose exec local-php /app/Build/local/vendor/bin/mysql-loader import -Hmysql -uroot -proot -Dforeign -f/$(DUMPS_DIR)/foreign/

## Ensure empty tables omitted from the foreign dump still exist on foreign
ensure-foreign-empty-tables: .mysql-wait
	echo "$(EMOJI_robot) Ensuring foreign-only empty tables exist on foreign"
	sql=""; \
	while IFS= read -r table; do \
		[ -n "$$table" ] || continue; \
		case "$$table" in \#*) continue ;; esac; \
		sql="$$sql CREATE TABLE IF NOT EXISTS foreign.$$table LIKE local.$$table; TRUNCATE TABLE foreign.$$table;"; \
	done < "$(FOREIGN_ONLY_EMPTY_TABLES_FILE)"; \
	docker compose exec -T mysql mysql -uroot -proot -e "$$sql"

## Restores the fileadmin from FILEADMIN_DIR
fileadmin-restore:
	echo "$(EMOJI_robot) Restoring the fileadmin"
	docker compose exec local-php rsync -a --delete /$(FILEADMIN_DIR)/local/ /app/Build/local/public/fileadmin/
	docker compose exec local-php rsync -a --delete /$(FILEADMIN_DIR)/foreign/ /app/Build/foreign/public/fileadmin/

## Set all workflow states to "Ready to Publish" (state=1) for test environments
workflow-ready:
	echo "$(EMOJI_robot) Setting workflow states to 'Ready to Publish'"
	docker compose exec -T mysql mysql -uroot -proot local -e "UPDATE tx_in2publish_workflow_state SET state_identifier = 1"

unit:
	docker compose exec local-php vendor/bin/phpunit -c /app/phpunit.unit.xml

functional:
	docker compose exec local-php vendor/bin/phpunit -c /app/phpunit.functional.xml

acceptance: typo3-clearcache typo3-rebuild-caches
	docker compose exec local-php vendor/bin/phpunit -c /app/phpunit.browser.xml

## Run single acceptance test
acceptance-test:
	@if [ -z "$(name)" ]; then \
		echo "Usage: make acceptance-test name=TestClassName [method=testMethodName]"; \
		echo "Example: make acceptance-test name=PublishFilesModuleTest"; \
		echo "Example: make acceptance-test name=PublishFilesModuleTest method=testNewlyUploadedFileCanBePublished"; \
		exit 1; \
	fi
	@if [ -n "$(method)" ]; then \
		echo "Running test method $(method) in $(name)"; \
		docker compose exec local-php vendor/bin/phpunit -c /app/phpunit.browser.xml --filter "$(name)::$(method)"; \
	else \
		echo "Running all test methods in $(name)"; \
		docker compose exec local-php vendor/bin/phpunit -c /app/phpunit.browser.xml --filter "$(name)"; \
	fi

setup-qa:
	docker run --rm -w "$$PWD" -v "$$PWD":"$$PWD" -v "$$HOME"/.phive/:/tmp/phive/ in2code/php:8.3-fpm phive install

qa: qa-php-cs-fixer qa-php-code-sniffer qa-php-mess-detector

qa-php-cs-fixer:
	docker run --rm -w "$$PWD" -v "$$PWD":"$$PWD" -v "$$HOME"/.phive/:/tmp/phive/ in2code/php:8.3-fpm .project/phars/php-cs-fixer check --config=.project/qa/php-cs-fixer.php --diff

fix-php-cs-fixer:
	docker run --rm -w "$$PWD" -v "$$PWD":"$$PWD" -v "$$HOME"/.phive/:/tmp/phive/ in2code/php:8.3-fpm .project/phars/php-cs-fixer fix -vvv --config=.project/qa/php-cs-fixer.php --diff

qa-php-code-sniffer:
	docker run --rm -w "$$PWD" -v "$$PWD":"$$PWD" -v "$$HOME"/.phive/:/tmp/phive/ in2code/php:8.3-fpm .project/phars/phpcs --basepath="$$PWD" --standard=.project/qa/phpcs.xml -s

fix-php-code-sniffer:
	docker run --rm -w "$$PWD" -v "$$PWD":"$$PWD" -v "$$HOME"/.phive/:/tmp/phive/ in2code/php:8.3-fpm .project/phars/phpcbf --basepath="$$PWD" --standard=.project/qa/phpcs.xml

qa-php-mess-detector:
	docker run --rm -w "$$PWD" -v "$$PWD":"$$PWD" -v "$$HOME"/.phive/:/tmp/phive/ in2code/php:8.3-fpm .project/phars/phpmd Classes ansi .project/qa/phpmd.xml

## Clears TYPO3 caches via typo3-console
typo3-clearcache:
	echo "$(EMOJI_broom) Clearing TYPO3 caches"
	docker compose exec -u app local-php ./vendor/bin/typo3 cache:flush
	docker compose exec -u app foreign-php ./vendor/bin/typo3 cache:flush

## Hard-deletes all caches (including DI) and rebuilds them on the fly
typo3-rebuild-caches:
	echo "$(EMOJI_broom) clearing DI cache on local"
	rm -rf Build/local/var/cache/code/
	echo "$(EMOJI_hot_face) rebuilding DI cache on local"
	docker compose exec local-php ./vendor/bin/typo3 help > /dev/null
	echo "$(EMOJI_broom) clearing DI cache on foreign"
	rm -rf Build/foreign/var/cache/code/
	echo "$(EMOJI_hot_face) rebuilding DI cache on foreign"
	docker compose exec foreign-php ./vendor/bin/typo3 help > /dev/null

## Starts the TYPO3 Databasecompare
typo3-comparedb:
	echo "$(EMOJI_leftright) Running database:updateschema"
	docker compose exec -u app local-php ./vendor/bin/typo3 database:updateschema --no-interaction;
	docker compose exec -u app foreign-php ./vendor/bin/typo3 database:updateschema --no-interaction;


## Starts composer-update
composer-update:
	echo "$(EMOJI_package) updating composer dependencies"
	docker exec -u1000 -e COMPOSER_AUTH='$(COMPOSER_AUTH_JSON)' in2publish_core-local-php-1 composer u -W
	docker exec -u1000 -e COMPOSER_AUTH='$(COMPOSER_AUTH_JSON)' in2publish_core-foreign-php-1 composer u -W

## Starts composer-install
composer-install:
	echo "$(EMOJI_package) Installing composer dependencies"
	docker exec -u1000 -e COMPOSER_AUTH='$(COMPOSER_AUTH_JSON)' in2publish_core-local-php-1 composer install
	docker exec -u1000 -e COMPOSER_AUTH='$(COMPOSER_AUTH_JSON)' in2publish_core-foreign-php-1 composer install

## Install all phars required with phive
.phive-install:
	mkdir -p ~/.phive/
	docker run --rm -it -u1000:1000 -v "$$PWD":/app -v $$HOME/.phive/:/tmp/phive/ -e PHIVE_HOME=/tmp/phive/ in2code/php:8.3-fpm phive install

.phive-update:
	mkdir -p ~/.phive/
	docker run --rm -it -u1000:1000 -v "$$PWD":/app -v $$HOME/.phive/:/tmp/phive/ -e PHIVE_HOME=/tmp/phive/ in2code/php:8.3-fpm phive update


## Print Project URIs
urls:
	echo "$(EMOJI_telescope) Project URLs:"; \
	echo ''; \
	printf "  %-17s %s\n" "Local Frontend:" "https://$(HOST_LOCAL)/"; \
	printf "  %-17s %s\n" "Local Backend:" "https://$(HOST_LOCAL)/typo3/"; \
	printf "  %-17s %s\n" "Foreign Frontend:" "https://$(HOST_FOREIGN)/"; \
	printf "  %-17s %s\n" "Foreign Backend:" "https://$(HOST_FOREIGN)/typo3/"; \

include .env
