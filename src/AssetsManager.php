<?php

namespace KPHPGame;

// TODO: parametrize the manager so it finds the assets of the
// installed game.

class AssetsManager {
  public static function tile(string $name): string {
    return __DIR__ . "/../assets/tiles/$name";
  }
}
