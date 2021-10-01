<?php

namespace KPHPGame\Scene\GameScene;

class IceShard {
    public int $pos;
    public int $direction;
    public int $dist;

    public function __construct(int $pos, int $direction) {
        $this->pos = $pos;
        $this->direction = $direction;
        $this->dist = 5;
    }
}
