<?php

namespace KPHPGame\Scene\GameScene;

class Player extends Unit {
    public int $mp;
    public int $exp;
    public int $next_level_exp;

    public function __construct() {
        $this->hp = 100;
        $this->mp = 200;
        $this->name = "Player";
        $this->exp = 0;
        $this->level = 1;
        $this->next_level_exp = (1  << $this->level + 2);
    }

    public function lvlUp() {
        $this->level += 1;
        $this->next_level_exp = (1  << $this->level + 2);
    }
}
