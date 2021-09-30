<?php

namespace KPHPGame\Scene\GameScene;

class Enemy extends Unit {
    public function __construct(string $name) {
        $this->name = $name;

        if ($name === 'Orc') {
            $this->hp = 50;
        } else {
            throw new \RuntimeException("creating invalid enemy: $name");
        }
    }
}
