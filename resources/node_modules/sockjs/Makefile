.PHONY: all serve clean

COFFEE:=./node_modules/.bin/coffee

#### General

all: build

build: src/*coffee
	@$(COFFEE) -v > /dev/null
	$(COFFEE) -o lib/ -c src/*.coffee

clean:
	rm -f lib/*.js


#### Testing

test_server: build
	node tests/test_server/server.js

serve:
	@if [ -e .pidfile.pid ]; then		\
		kill `cat .pidfile.pid`;	\
		rm .pidfile.pid;		\
	fi

	@while [ 1 ]; do				\
		make build;				\
		echo " [*] Running http server";	\
		make test_server &			\
		SRVPID=$$!;				\
		echo $$SRVPID > .pidfile.pid;		\
		echo " [*] Server pid: $$SRVPID";	\
		inotifywait -r -q -e modify .;		\
		kill `cat .pidfile.pid`;		\
		rm -f .pidfile.pid;			\
		sleep 0.1;				\
	done

#### Release process
#   1) commit everything
#   2) amend version in package.json
#   3) run 'make tag' and run suggested 'git push' variants
#   4) run 'npm publish'

RVER:=$(shell grep "version" package.json|tr '\t"' ' \t'|cut -f 4)
VER:=$(shell ./VERSION-GEN)

.PHONY: tag
tag: all
	git commit $(TAG_OPTS) package.json Changelog -m "Release $(RVER)"
	git tag v$(RVER) -m "Release $(RVER)"
	@echo ' [*] Now run'
	@echo 'git push; git push --tag'
