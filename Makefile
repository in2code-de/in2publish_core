#############################################################################################
# TESTING
#############################################################################################

test-unit:
	docker compose exec local-php vendor/bin/phpunit -c /app/phpunit.unit.xml

## Run a single unit test class or method: make unit-test name=TestClassName [method=testMethodName]
unit-test:
	@if [ -z "$(name)" ]; then \
		echo "Usage: make unit-test name=TestClassName [method=testMethodName]"; \
		echo "Example: make unit-test name=FlushFrontendPageCacheTaskTest"; \
		echo "Example: make unit-test name=FlushFrontendPageCacheTaskTest method=testTaskFailsWithoutFlushingAnyCacheIfNoCommandIsConfigured"; \
		exit 1; \
	fi
	@if [ -n "$(method)" ]; then \
		echo "Running test method $(method) in $(name)"; \
		docker compose exec local-php vendor/bin/phpunit -c /app/phpunit.unit.xml --filter "$(name)::$(method)"; \
	else \
		echo "Running all test methods in $(name)"; \
		docker compose exec local-php vendor/bin/phpunit -c /app/phpunit.unit.xml --filter "$(name)"; \
	fi

test-functional:
	docker compose exec local-php vendor/bin/phpunit -c /app/phpunit.functional.xml

## Run all Playwright tests in the isolated core test stack. Use FILE= for individual tests.
test-playwright:
	$(call with_playwright_lock,$(call ensure_playwright_stack); docker compose run --rm -e PLAYWRIGHT_HTML_OPEN=never playwright sh -lc "npm install --silent && npx playwright test $(FILE)")

## Open Playwright UI mode in the isolated core test stack. Use FILE= to filter tests.
playwright-ui:
	$(call with_playwright_lock,$(call ensure_playwright_stack); echo "Open Playwright UI at http://localhost:$(PLAYWRIGHT_UI_PORT)"; docker compose run --rm --service-ports playwright sh -lc "npm install --silent && npx playwright test --ui --ui-host=0.0.0.0 --ui-port=9323 $(FILE)")

## Show the last Playwright HTML report from the isolated core test stack
playwright-report:
	$(call with_playwright_lock,$(call ensure_playwright_stack); echo "Open Playwright report at http://localhost:$(PLAYWRIGHT_UI_PORT)"; docker compose run --rm --service-ports playwright sh -lc "npm install --silent && npx playwright show-report --host=0.0.0.0 --port=9323")

## Stop all Playwright tasks for the isolated core test stack
playwright-stop:
	$(call stop_playwright_tasks)


#############################################################################################
# RESTORING DATA
#############################################################################################

restore: restore-db fileadmin-restore typo3-clear-pagecache

## Restore only the databases (no fileadmin) - used by Playwright specs that don't touch files
restore-db: mysql-restore .ensure-foreign-empty-tables typo3-comparedb

## Prepare a pristine environment once before a Playwright run
playwright-prepare: playwright-reset fileadmin-restore typo3-comparedb typo3-clear-pagecache

## Reset database state between Playwright tests without booting TYPO3
playwright-reset: mysql-restore .ensure-foreign-empty-tables playwright-clear-runtime-state

## Reset database and fileadmin state for Playwright tests that modify files
playwright-reset-files: playwright-reset fileadmin-restore

## Fail early when the extension-local TYPO3 instances are not provisioned yet
.ensure-provisioned:
	if [ ! -f Build/local/vendor/bin/mysql-loader ] || [ ! -f Build/foreign/vendor/bin/typo3 ]; then \
		echo "$(EMOJI_face_with_rolling_eyes) The extension-local TYPO3 instances are not provisioned yet (Build/*/vendor is missing)."; \
		echo "Run 'make setup' in packages/in2publish_core once, then re-run this target."; \
		exit 1; \
	fi

## Restores the database from the dump files in DUMPS_DIR
mysql-restore: .ensure-provisioned .mysql-wait
	echo "$(EMOJI_robot) Restoring the local database"
	docker compose exec local-php /app/Build/local/vendor/bin/mysql-loader import -Hmysql -uroot -proot -Dlocal -f/$(DUMPS_DIR)/local/
	echo "$(EMOJI_robot) Restoring the foreign database"
	docker compose exec local-php /app/Build/local/vendor/bin/mysql-loader import -Hmysql -uroot -proot -Dforeign -f/$(DUMPS_DIR)/foreign/

## Clear volatile state excluded from fixture dumps without booting TYPO3
playwright-clear-runtime-state: .mysql-wait
	echo "$(EMOJI_broom) Clearing Playwright runtime state"
	for database in local foreign; do \
		tables="$$(docker compose exec -T $(MYSQL_ROOT_ENV) mysql mysql -uroot -N -B -e \
			"SELECT table_name FROM information_schema.tables \
			 WHERE table_schema = '$$database' \
			 AND table_name IN ('be_sessions', 'fe_sessions', 'sys_lockedrecords', 'sys_messenger_messages')")"; \
		sql="SET FOREIGN_KEY_CHECKS=0;"; \
		for table in $$tables; do sql="$$sql DELETE FROM \`$$table\`;"; done; \
		docker compose exec -T $(MYSQL_ROOT_ENV) mysql mysql -uroot "$$database" \
			-e "$$sql SET FOREIGN_KEY_CHECKS=1;"; \
	done

## Restores the fileadmin from FILEADMIN_DIR
fileadmin-restore:
	echo "$(EMOJI_robot) Restoring the fileadmin"
	docker compose exec local-php rsync -a --delete /$(FILEADMIN_DIR)/local/ /app/Build/local/public/fileadmin/
	docker compose exec local-php rsync -a --delete /$(FILEADMIN_DIR)/foreign/ /app/Build/foreign/public/fileadmin/

## Create dumps of the local and foreign database in DUMPS_DIR
dump-dbs: dump-local-database dump-foreign-database

dump-local-database: .ensure-provisioned .mysql-wait
	echo "$(EMOJI_robot) Dumping the local database"
	docker compose exec local-php /app/Build/local/vendor/bin/mysql-loader dump -r -Hmysql -uroot -proot -Dlocal -f/$(DUMPS_DIR)/local/ $(DUMP_EXCLUDES)

dump-foreign-database: .ensure-provisioned .mysql-wait
	echo "$(EMOJI_robot) Dumping the foreign database"
	docker compose exec local-php /app/Build/local/vendor/bin/mysql-loader dump -r -Hmysql -uroot -proot -Dforeign -f/$(DUMPS_DIR)/foreign/ $(DUMP_EXCLUDES)



#############################################################################################
# QA
#############################################################################################

qa: qa-php-cs-fixer qa-php-code-sniffer qa-php-mess-detector

qa-php-cs-fixer:
	docker run --rm -w "$$PWD" -v "$$PWD":"$$PWD" -v "$$HOME"/.phive/:/tmp/phive/ in2code/php:8.2-fpm .project/phars/php-cs-fixer check --config=.project/qa/php-cs-fixer.php --diff

fix-php-cs-fixer:
	docker run --rm -w "$$PWD" -v "$$PWD":"$$PWD" -v "$$HOME"/.phive/:/tmp/phive/ in2code/php:8.2-fpm .project/phars/php-cs-fixer fix -vvv --config=.project/qa/php-cs-fixer.php --diff

qa-php-code-sniffer:
	docker run --rm -w "$$PWD" -v "$$PWD":"$$PWD" -v "$$HOME"/.phive/:/tmp/phive/ in2code/php:8.2-fpm .project/phars/phpcs --basepath="$$PWD" --standard=.project/qa/phpcs.xml -s

fix-php-code-sniffer:
	docker run --rm -w "$$PWD" -v "$$PWD":"$$PWD" -v "$$HOME"/.phive/:/tmp/phive/ in2code/php:8.2-fpm .project/phars/phpcbf --basepath="$$PWD" --standard=.project/qa/phpcs.xml

qa-php-mess-detector:
	docker run --rm -w "$$PWD" -v "$$PWD":"$$PWD" -v "$$HOME"/.phive/:/tmp/phive/ in2code/php:8.2-fpm .project/phars/phpmd Classes ansi .project/qa/phpmd.xml

qa-setup:
	docker run --rm -w "$$PWD" -v "$$PWD":"$$PWD" -v "$$HOME"/.phive/:/tmp/phive/ in2code/php:8.2-fpm phive install --trust-gpg-keys $(PHIVE_TRUST_KEYS)


#############################################################################################
# COMPOSER HELPER
#############################################################################################

## Starts composer-update
composer-update:
	echo "$(EMOJI_package) updating composer dependencies"
	docker compose exec -u1000 $(COMPOSER_AUTH_OPT) local-php composer u -W
	docker compose exec -u1000 $(COMPOSER_AUTH_OPT) foreign-php composer u -W

## Starts composer-install
composer-install:
	echo "$(EMOJI_package) Installing composer dependencies"
	docker compose exec -u1000 $(COMPOSER_AUTH_OPT) local-php composer install
	docker compose exec -u1000 $(COMPOSER_AUTH_OPT) foreign-php composer install



#############################################################################################
# TYPO3 HELPER
#############################################################################################

## Clears TYPO3 caches via typo3-console
typo3-clearcache:
	echo "$(EMOJI_broom) Clearing TYPO3 caches"
	docker compose exec -u app local-php ./vendor/bin/typo3 cache:flush
	docker compose exec -u app foreign-php ./vendor/bin/typo3 cache:flush

## Clears database-dependent caches without invalidating the expensive DI cache
typo3-clear-pagecache:
	echo "$(EMOJI_broom) Clearing TYPO3 page caches"
	docker compose exec -u app local-php ./vendor/bin/typo3 cache:flush --group pages
	docker compose exec -u app foreign-php ./vendor/bin/typo3 cache:flush --group pages

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



#############################################################################################
# GENERIC COMMANDS
#############################################################################################

setup: playwright-stop stop destroy .install-packages .create-certificate start .mysql-wait
	@echo "Installing in2publish_core as $(IN2PUBLISH_DEV_VERSION)"
	docker compose exec -u1000 $(COMPOSER_AUTH_OPT) local-php composer u -W
	docker compose exec -u1000 $(COMPOSER_AUTH_OPT) foreign-php composer u -W
	docker compose exec -u1000 local-php vendor/bin/typo3 install:setup --force
	docker compose exec -u1000 foreign-php vendor/bin/typo3 install:setup --force
	git checkout Build/local/config/sites/main/config.yaml
	git checkout Build/foreign/config/sites/main/config.yaml
	make restore

start: .link-compose-file
	docker compose build --pull
	docker compose up -d

stop: .link-compose-file
	docker compose stop
	docker compose down

destroy: stop
	echo "$(EMOJI_litter) Removing the project"
	docker compose down -v --remove-orphans


hosts:
	if grep -qF "$(HOST_LOCAL)" /etc/hosts; then \
		echo "$(EMOJI_robot) Hosts entry for $(HOST_LOCAL) already present"; \
	else \
		echo "$(EMOJI_robot) Adding development hostnames to /etc/hosts (sudo required)"; \
		printf '\n127.0.0.1 $(HOST_LOCAL) $(HOST_FOREIGN) $(MAIL_HOST) $(SOLR_LOCAL) $(SOLR_FOREIGN)\n' | sudo tee -a /etc/hosts > /dev/null; \
	fi

## Print Project URIs
urls:
	echo "$(EMOJI_telescope) Project URLs:"; \
	echo ''; \
	printf "  %-17s %s\n" "Local Frontend:" "https://$(HOST_LOCAL)/"; \
	printf "  %-17s %s\n" "Local Backend:" "https://$(HOST_LOCAL)/typo3/"; \
	printf "  %-17s %s\n" "Foreign Frontend:" "https://$(HOST_FOREIGN)/"; \
	printf "  %-17s %s\n" "Foreign Backend:" "https://$(HOST_FOREIGN)/typo3/";

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


#############################################################################################
# PRIVATE HELPER
#############################################################################################

## Install all phars required with phive
.phive-install:
	mkdir -p ~/.phive/
	docker run --rm -it -u1000:1000 -v "$$PWD":/app -v $$HOME/.phive/:/tmp/phive/ -e PHIVE_HOME=/tmp/phive/ in2code/php:8.2-fpm phive install --trust-gpg-keys $(PHIVE_TRUST_KEYS)

.phive-update:
	mkdir -p ~/.phive/
	docker run --rm -it -u1000:1000 -v "$$PWD":/app -v $$HOME/.phive/:/tmp/phive/ -e PHIVE_HOME=/tmp/phive/ in2code/php:8.2-fpm phive update --trust-gpg-keys $(PHIVE_TRUST_KEYS)


## Wait for the mysql container to be fully provisioned
.mysql-wait:
	echo "$(EMOJI_ping_pong) Waiting for the database (initialising a fresh volume takes about 30 seconds)"
	attempt=0; \
	while ! error="$$(docker compose exec -T $(MYSQL_ROOT_ENV) mysql mysql -uroot local -e "SELECT 1;" 2>&1 >/dev/null)"; do \
		attempt=$$((attempt + 1)); \
		if [ "$$attempt" -ge $(MYSQL_WAIT_ATTEMPTS) ]; then \
			echo ""; \
			echo "$(EMOJI_face_with_rolling_eyes) Database is not reachable after $$attempt attempts, giving up. Last error:"; \
			echo "$$error"; \
			exit 1; \
		fi; \
		printf "."; \
		sleep $(MYSQL_WAIT_INTERVAL); \
	done; \
	echo ""; \
	echo "$(EMOJI_robot) Database is up and running"

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

## Ensure empty tables omitted from the foreign dump still exist (empty) on foreign
.ensure-foreign-empty-tables: .mysql-wait
	echo "$(EMOJI_robot) Ensuring foreign-only empty tables exist on foreign"
	local_tables=$$(docker compose exec -T mysql mysql -uroot -proot --batch --skip-column-names -e 'SHOW TABLES IN `local`'); \
	sql=""; \
	for table in $(FOREIGN_ONLY_EMPTY_TABLES); do \
		grep -qxF "$$table" <<<"$$local_tables" || continue; \
		sql+="CREATE TABLE IF NOT EXISTS \`foreign\`.\`$$table\` LIKE \`local\`.\`$$table\`; TRUNCATE TABLE \`foreign\`.\`$$table\`;"; \
	done; \
	[ -z "$$sql" ] || docker compose exec -T mysql mysql -uroot -proot -e "$$sql"

## Choose the right docker compose file for your environment
.link-compose-file:
	echo "$(EMOJI_triangular_ruler) Linking the OS specific compose file"
ifeq ($(shell uname -s), Darwin)
	ln -snf .project/docker/docker-compose.darwin.yaml docker-compose.yaml
else
	ln -snf .project/docker/docker-compose.linux.yaml docker-compose.yaml
endif

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
	$(MAKE) .ensure-provisioned || exit 1; \
	docker compose up -d >/dev/null; \
	$(MAKE) .build-playwright-image
endef

# Rebuild the profile-gated Playwright image only when its pinned base image changed.
.build-playwright-image:
	image="$$(docker compose config --images playwright)"; \
	pinned="$$(sed -n 's~^FROM \(mcr\.microsoft\.com/playwright:[^ ]*\).*~\1~p' $(PLAYWRIGHT_DOCKERFILE))"; \
	built=''; \
	if docker image inspect "$$image" >/dev/null 2>&1; then \
		built="$$(docker run --rm --entrypoint sh "$$image" -c 'cat /ms-playwright/.docker-info' 2>/dev/null \
			| sed -n 's~.*"dockerImageName": *"\([^"]*\)".*~\1~p')"; \
	fi; \
	if [ "$$pinned" != "$$built" ]; then \
		echo "$(EMOJI_robot) Rebuilding the Playwright image ($${built:-not built yet} -> $$pinned)"; \
		docker compose --profile tools build playwright; \
	fi

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
	rm -f "$$lockdir/pid"; \
	rmdir "$$lockdir" 2>/dev/null || true
endef

include .env

# Settings
MAKEFLAGS += --silent --always-make
SHELL := /bin/bash
-include .env

PHIVE_TRUST_KEYS := 0x97B02DD8E5071466,0x31C7E470E2138192,0xE82B2FB314E9906E,0xA4E55EA12C7C085C,0x9093F8B32E4815AA

# Host docker group id, forwarded to the Playwright container so it can access
# the mounted docker socket (execInContainer / execTypo3Command). Empty on hosts
# without a docker group; the compose file then falls back to a default.
export DOCKER_GID := $(shell getent group docker 2>/dev/null | cut -d: -f3)

MYSQL_WAIT_ATTEMPTS := 40
MYSQL_WAIT_INTERVAL := 3
MYSQL_ROOT_ENV := -e MYSQL_PWD=$(MYSQL_ROOT_PASSWORD)

PLAYWRIGHT_DOCKERFILE := .project/docker/playwright/Dockerfile

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

CURRENT_BRANCH := $(shell git branch --show-current 2>/dev/null || echo develop)
IN2PUBLISH_DEV_VERSION := dev-$(CURRENT_BRANCH)
# Tables that exist on local but must stay empty on foreign. They are omitted from
# the dumps by mysql-loader, so the restore recreates and truncates them explicitly.
FOREIGN_ONLY_EMPTY_TABLES := sys_category sys_category_record_mm

# Tables excluded from the fixture dumps: caches, logs, sessions and other volatile
# data that must not be part of the reproducible test state.
DUMP_EXCLUDES := -xcache_ -xindex_ -xtx_styleguide_ -xbackend_layout -xbe_dashboards \
	-xbe_sessions -xfe_sessions -xsys_file_processedfile -xsys_history -xsys_http_report \
	-xsys_lockedrecords -xsys_log -xsys_messenger_messages -xsys_refindex -xtx_in2code_ \
	-xtx_in2publish_notification -xtx_in2publish_wfpn_demand -xtx_in2publishcore_ -xtx_solr_ \
	-Q"sys_registry:entry_namespace != 'core' AND entry_key != 'formProtectionSessionToken'"

# Forward a host composer auth.json into the containers to avoid GitHub API rate limits.
# Falls back to no authentication when no auth.json is present.
COMPOSER_AUTH_FILE := $(firstword $(wildcard $(HOME)/.composer/auth.json $(HOME)/.config/composer/auth.json))
ifneq ($(COMPOSER_AUTH_FILE),)
export COMPOSER_AUTH := $(shell cat $(COMPOSER_AUTH_FILE))
COMPOSER_AUTH_OPT := -e COMPOSER_AUTH
endif
