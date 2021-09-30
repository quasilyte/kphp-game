<?php

namespace KPHPGame\Scene\GameScene;

// World contains the game scene state.
// Player data, enemies, map information.
class World {
  /** @var int[][] */
  public $tiles;

  public $map_rows = 0;
  public $map_cols = 0;
}
