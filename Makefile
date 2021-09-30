.PHONY: game

game:
	composer install
	./scripts/build.bash
	mkdir -p bin
	mv kphp_out/cli bin/game
