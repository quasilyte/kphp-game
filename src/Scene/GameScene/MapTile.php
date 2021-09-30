<?php

namespace KPHPGame\Scene\GameScene;

class MapTile {
    public const EMPTY = 0;
    public const WALL  = 1;
    public const ROCK  = 2;

    public $kind = self::EMPTY;
    public $tileset_index = 0;
    public $pos = 0;
    public $col = 0;
    public $row = 0;
    public $revealed = false;

    public function distTo(MapTile $other): int {
        $col = $this->col;
        $row = $this->row;
        $dist = 0;
        while ($col !== $other->col) {
            $dist++;
            $col += ($col > $other->col ? -1 : +1);
        }
        while ($row !== $other->row) {
            $dist++;
            $row += ($row > $other->row ? -1 : +1);
        }
        return $dist;
    }

    public function directionTo(MapTile $other): int {
        if ($this->col < $other->col) {
            return Direction::RIGHT;
        }
        if ($this->col > $other->col) {
            return Direction::LEFT;
        }
        if ($this->row < $other->row) {
            return Direction::DOWN;
        }
        if ($this->row > $other->row) {
            return Direction::UP;
        }
        return Direction::NONE;
    }
}