<?php

namespace KPHPGame\Scene\GameScene;

class Unit {
    public int $hp;
    public int $max_hp;
    public int $level;

    public int $pos = 0;
    public int $direction = Direction::DOWN;
    public string $name;
}
