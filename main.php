<?php

require_once __DIR__ . '/vendor/autoload.php';

use KPHPGame\Game;

function main() {
  try {
    $game = new Game();
    $game->run();
  } catch (Exception $e) {
    echo "UNHANDLED EXCEPTION: {$e->getFile()}:{$e->getLine()}: {$e->getMessage()}\n";
  }
}

main();
