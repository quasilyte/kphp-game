.PHONY: game update-sdlite

update-sdlite:
	mkdir -p packages
	rm -rf packages/kphp-sdlite
	git clone https://github.com/quasilyte/kphp-sdlite.git packages/kphp-sdlite
	rm composer.lock
	composer install

game:
	composer install
	./scripts/build.bash
	mkdir -p bin
	mv kphp_out/cli bin/game
