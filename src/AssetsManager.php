<?php

namespace KPHPGame;

class AssetsManager {
    public static function sound(string $name): string {
        return self::getRootByTarget() . "sounds/$name";
    }

    public static function unit(string $name): string {
        return self::getRootByTarget() . "units/$name";
    }

    public static function tile(string $name): string {
        return self::getRootByTarget() . "tiles/$name";
    }

    public static function magic(string $name): string {
        return self::getRootByTarget() . "magic/$name";
    }

    public static function font(string $name): string {
        return self::getRootByTarget() . "fonts/$name.ttf";
    }

    private static function getRootByTarget(): string {
        $target = $_ENV['KPHP_GAME_ASSETS_PATH'] ? strval($_ENV['KPHP_GAME_ASSETS_PATH']) : '';
        if (!$target) {
            throw new \RuntimeException("Should specify the assets path environment variable.\r\n  for example: `KPHP_GAME_ASSETS_PATH=~/kphp-project/kphp-game/assets/ ./bin/game`\r\n");
        }
        if (!is_dir($target)) {
            throw new \RuntimeException("KPHP_GAME_ASSETS_PATH is not a valid directory\r\n");
        }
        return $target;
    }
}
