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
        $target = $_ENV['KPHP_GAME_TARGET'] ?? '';
        if ($target === 'linux') {
            return "./assets/";
        }
        if ($target === 'macos') {
            return "./../Resources/";
        }
        // Otherwise, it's dev-mode.
        return __DIR__ . "/../assets/";
    }
}
