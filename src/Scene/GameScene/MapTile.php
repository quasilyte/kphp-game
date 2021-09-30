<?php

namespace KPHPGame\Scene\GameScene;

class MapTile {
    public const EMPTY = 0;
    public const WALL  = 1;

    public $kind = self::EMPTY;
    public $tileset_index = 0;
    public $pos = 0;
    public $col = 0;
    public $row = 0;
}