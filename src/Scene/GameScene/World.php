<?php

namespace KPHPGame\Scene\GameScene;

// World contains the game scene state.
// Player data, enemies, map information.
class World {
    /** @var MapTile[] */
    public $tiles;

    public $map_rows = 0;
    public $map_cols = 0;

    public Player $player;

    /** @var Enemy[] */
    public $enemies;

    public function __construct() {
        $this->player = new Player();
    }

    public function getTile(int $row, int $col): MapTile {
        return $this->tiles[$row * $this->map_cols + $col];
    }

    public function getPlayerTile(): MapTile {
        return $this->tiles[$this->player->pos];
    }
}
