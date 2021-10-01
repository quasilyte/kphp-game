<?php

namespace KPHPGame;

// TODO: parametrize the manager so it finds the assets of the
// installed game.

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
//        $target = @ini_get('target');
        return "./../Resources/";
//        switch ($target) {
//            case null:
//            case false:
//                return __DIR__ . "/../assets/";
//            case "macos":
//                return "./../Resources/";
//            case "linux":
//                return "";
//        }
//        return "";
    }
}
