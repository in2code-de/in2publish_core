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

.prepare:
	mkdir -p $$HOME/.composer/cache
	[ -f $$HOME/.composer/auth.json ] || echo "{}" > $$HOME/.composer/auth.json
	mkdir -p $$HOME/.phive

.prepare-tests:
	if [[ ! -d .Build ]]; then ./Build/Scripts/runTests.sh -s composerInstall; fi

## Initialize the project and install phive and composer dependencies
install: .prepare
	chmod +x .project/githooks/*
	git config core.hooksPath .project/githooks/
	docker run --rm -it -u$$(id -u):$$(id -g) -v $$PWD:$$PWD -w $$PWD -v $$HOME/.phive:/tmp/phive in2code/php:7.4-fpm phive install
	docker run --rm -it -u$$(id -u):$$(id -g) -v $$PWD:$$PWD -w $$PWD -v $$HOME/.composer/cache:/tmp/composer/cache -v $$HOME/.composer/auth.json:/tmp/composer/auth.json in2code/php:7.4-fpm composer install

## Run grumphp which will run all code quality tools concurrently
qa-code-quality:
	docker run --rm -it -u$$(id -u):$$(id -g) -v $$PWD:$$PWD -w $$PWD -v $$HOME/.phive:/tmp/phive in2code/php:7.4-fpm .project/phars/grumphp -c .project/qa/grumphp.yml run

## Run all automated tests in the in2publish_core test suite
qa-tests: .prepare-tests qa-tests-unit qa-tests-functional

## Run all unit tests
qa-tests-unit:
	./Build/Scripts/runTests.sh

## Run all functional tests
qa-tests-functional:
	./Build/Scripts/runTests.sh -s functional
