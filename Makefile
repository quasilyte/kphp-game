.PHONY: game update-sdlite run-with-php

game:
	composer install
	./scripts/build.bash
	mkdir -p bin
	mv kphp_out/cli bin/game

run-with-php:
	php8.0 -d variables_order=EGPCS -d opcache.enable=1 -d opcache.enable_cli=1 -d opcache.preload=./php_preload.php -f ./main.php

macos-bundle:
	rm -rf Game.app
	cp -r ./scripts/macos/Game.app ./
	cp ./bin/game ./Game.app/Contents/MacOS
	strip ./Game.app/Contents/MacOS/game
	mkdir -p ./Game.app/Contents/MacOS
	mkdir -p ./Game.app/Contents/Resources
	mkdir -p ./Game.app/Contents/Library
	cp /usr/local/opt/sdl2/lib/libSDL2-2.0.0.dylib ./Game.app/Contents/Library/libSDL2.dylib
	cp /usr/local/opt/sdl2_image/lib/libSDL2_image-2.0.0.dylib ./Game.app/Contents/Library/libSDL2_image.dylib
	cp /usr/local/opt/sdl2_mixer/lib/libSDL2_mixer-2.0.0.dylib ./Game.app/Contents/Library/libSDL2_mixer.dylib
	cp /usr/local/opt/sdl2_ttf/lib/libSDL2_ttf-2.0.0.dylib ./Game.app/Contents/Library/libSDL2_ttf.dylib
	cp -r ./assets/* ./Game.app/Contents/Resources
	cp ./assets/icons/AppIcon.icns ./Game.app/Contents/Resources

linux-bundle:
	cp -r ./scripts/linux/Game.AppDir ./
	cp ./bin/game ./Game.AppDir/AppRun
	mkdir -p ./Game.AppDir/usr/bin
	cp ./bin/game ./Game.AppDir/usr/bin/game
	cp ./assets/icons/AppIcon.png ./Game.AppDir
	appimagetool ./Game.AppDir
