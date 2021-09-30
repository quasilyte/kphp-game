<?php

namespace KPHPGame\Scene\GameScene;

class Player extends Unit {
    public int $mp;

    public function __construct() {
        $this->hp = 100;
        $this->mp = 200;
        $this->name = "Mage";
    }
}
