<?php

namespace KPHPGame\Scene\GameScene;

class Enemy extends Unit {
    public bool $triggered = false;

    public int $min_damage;
    public int $max_damage;

    public function __construct(string $name) {
        $this->name = $name;

        if ($name === 'Orc') {
            $this->hp = 50;
            $this->min_damage = 1;
            $this->max_damage = 10;
        } else {
            throw new \RuntimeException("creating invalid enemy: $name");
        }
    }
}
