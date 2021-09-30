<?php

namespace KPHPGame\Scene\GameScene;

class Enemy extends Unit {
    public bool $triggered = false;

    public int $min_damage;
    public int $max_damage;

    public static function newGoblin(): Enemy { return new Enemy('Goblin'); }
    public static function newOrc(): Enemy { return new Enemy('Orc'); }

    private function __construct(string $name) {
        $this->name = $name;

        if ($name === 'Goblin') {
            $this->hp = 50;
            $this->min_damage = 1;
            $this->max_damage = 10;
            $this->level = 1;
        } else if ($name === 'Orc') {
            $this->hp = 95;
            $this->min_damage = 3;
            $this->max_damage = 10;
            $this->level = 2;
        } else {
            throw new \RuntimeException("creating invalid enemy: $name");
        }
    }
}
