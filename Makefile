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

macos-bundle:
	cp -r ./scripts/macos/Game.app ./
	cp ./bin/game ./Game.app/Contents/MacOS
	cp ./assets/icons/AppIcon.icns ./Game.app/Contents/Resources

linux-bundle:
	cp -r ./scripts/linux/Game.AppDir ./
	cp ./bin/game ./Game.AppDir/AppRun
	cp ./bin/game ./Game.AppDir/usr/bin/game
	cp ./assets/icons/AppIcon.png ./Game.AppDir
