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

    public int $stage;

    public int $portal_pos = 0;

    /** @var Enemy[] */
    public $enemies;

    public function __construct(Player $player, int $stage) {
        $this->player = $player;
        $this->stage  = $stage;
    }

    public function hasTileAt(int $row, int $col): bool {
        return $col >= 0 && $row >= 0 && $col < $this->map_cols && $row < $this->map_rows;
    }

    public function getTile(int $row, int $col): MapTile {
        return $this->tiles[$row * $this->map_cols + $col];
    }

    public function getPlayerTile(): MapTile {
        return $this->tiles[$this->player->pos];
    }

    /** @return tuple(int, int) */
    public function calculateStep(MapTile $tile, int $dir) {
        $delta_col = 0;
        $delta_row = 0;
        if ($dir === Direction::UP) {
            $delta_row = -1;
        } elseif ($dir === Direction::DOWN) {
            $delta_row = +1;
        } elseif ($dir === Direction::LEFT) {
            $delta_col = -1;
        } elseif ($dir === Direction::RIGHT) {
            $delta_col = +1;
        }
        return tuple($tile->row + $delta_row, $tile->col + $delta_col);
    }

    public function calculateStepTile(MapTile $tile, int $dir): MapTile {
        [$row, $col] = $this->calculateStep($tile, $dir);
        return $this->getTile($row, $col);
    }

    public function tileIsFree(MapTile $tile): bool {
        if ($tile->kind === MapTile::WALL || $tile->kind === MapTile::ROCK) {
            return false;
        }
        if ($this->player->pos === $tile->pos) {
            return false;
        }
        foreach ($this->enemies as $enemy) {
            if ($enemy->pos === $tile->pos) {
                return false;
            }
        }
        return true;
    }

    public function tileIsPortal(): bool {
        return false;
    }
}
