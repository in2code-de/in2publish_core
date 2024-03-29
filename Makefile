# Settings
MAKEFLAGS += --silent --always-make
SHELL := /bin/bash

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
	docker compose up -d

setup: stop destroy start .mysql-wait
	docker compose exec local-php composer i
	docker exec -u1000 in2publish_core-foreign-php-1 composer i
	docker compose exec local-php vendor/bin/typo3 install:setup --force
	docker exec -u1000 in2publish_core-foreign-php-1 vendor/bin/typo3 install:setup --force
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

restore: mysql-restore fileadmin-restore

## Restores the database from the backup files in SQLDUMPSDIR
mysql-restore: .mysql-wait
	echo "$(EMOJI_robot) Restoring the local database"
	docker compose exec local-php vendor/bin/mysql-loader import -Hmysql -uroot -proot -Dlocal -f/.project/data/dumps/local/
	echo "$(EMOJI_robot) Restoring the foreign database"
	docker compose exec local-php vendor/bin/mysql-loader import -Hmysql -uroot -proot -Dforeign -f/.project/data/dumps/foreign/

## Restores the fileadmin from .project/data/fileadmin
fileadmin-restore:
	echo "$(EMOJI_robot) Restoring the fileadmin"
	rsync -a --delete .project/data/fileadmin/local/ Build/local/public/fileadmin/
	rsync -a --delete .project/data/fileadmin/foreign/ Build/foreign/public/fileadmin/

## Create dumps of local and foreign database in dir .project/data/dumps
dump-dbs: dump-local-database dump-foreign-database

dump-local-database: .mysql-wait
	echo "$(EMOJI_robot) Dumping the local database"
	docker compose exec local-php vendor/bin/mysql-loader dump -r -Hmysql -uroot -proot -Dlocal -f/.project/data/dumps/local/ -xcache_ -xindex_ -xtx_styleguide_ -xbackend_layout -xbe_dashboards -xbe_sessions -xfe_sessions -xsys_file_processedfile -xsys_history -xsys_http_report -xsys_lockedrecords -xsys_log -xsys_messenger_messages -xsys_refindex -xtx_in2code_ -xtx_in2publish_notification -xtx_in2publish_wfpn_demand -xtx_in2publishcore_ -xtx_solr_ -Q"sys_registry:entry_namespace != 'core' AND entry_key != 'formProtectionSessionToken'"

dump-foreign-database: .mysql-wait
	echo "$(EMOJI_robot) Dumping the foreign database"
	docker compose exec local-php vendor/bin/mysql-loader dump -r -Hmysql -uroot -proot -Dforeign -f/.project/data/dumps/foreign/ -xcache_ -xindex_ -xtx_styleguide_ -xbackend_layout -xbe_dashboards -xbe_sessions -xfe_sessions -xsys_file_processedfile -xsys_history -xsys_http_report -xsys_lockedrecords -xsys_log -xsys_messenger_messages -xsys_refindex -xtx_in2code_ -xtx_in2publish_notification -xtx_in2publish_wfpn_demand -xtx_in2publishcore_ -xtx_solr_ -Q"sys_registry:entry_namespace != 'core' AND entry_key != 'formProtectionSessionToken'"

unit:
	docker compose exec local-php vendor/bin/phpunit -c /app/phpunit.unit.xml

functional:
	docker compose exec local-php vendor/bin/phpunit -c /app/phpunit.functional.xml

acceptance:
	docker compose exec local-php vendor/bin/phpunit -c /app/phpunit.browser.xml

setup-qa:
	docker run --rm -w "$$PWD" -v "$$PWD":"$$PWD" -v "$$HOME"/.phive/:/tmp/phive/ in2code/php:7.4-fpm phive install

qa: qa-php-cs-fixer qa-php-code-sniffer qa-php-mess-detector

qa-php-cs-fixer:
	docker run --rm -w "$$PWD" -v "$$PWD":"$$PWD" -v "$$HOME"/.phive/:/tmp/phive/ in2code/php:7.4-fpm .project/phars/php-cs-fixer check --config=.project/qa/php-cs-fixer.php --diff

fix-php-cs-fixer:
	docker run --rm -w "$$PWD" -v "$$PWD":"$$PWD" -v "$$HOME"/.phive/:/tmp/phive/ in2code/php:7.4-fpm .project/phars/php-cs-fixer fix --config=.project/qa/php-cs-fixer.php --diff

qa-php-code-sniffer:
	docker run --rm -w "$$PWD" -v "$$PWD":"$$PWD" -v "$$HOME"/.phive/:/tmp/phive/ in2code/php:7.4-fpm .project/phars/phpcs --basepath="$$PWD" --standard=.project/qa/phpcs.xml -s

fix-php-code-sniffer:
	docker run --rm -w "$$PWD" -v "$$PWD":"$$PWD" -v "$$HOME"/.phive/:/tmp/phive/ in2code/php:7.4-fpm .project/phars/phpcbf --basepath="$$PWD" --standard=.project/qa/phpcs.xml

qa-php-mess-detector:
	docker run --rm -w "$$PWD" -v "$$PWD":"$$PWD" -v "$$HOME"/.phive/:/tmp/phive/ in2code/php:7.4-fpm .project/phars/phpmd Classes ansi .project/qa/phpmd.xml
