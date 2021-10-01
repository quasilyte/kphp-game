<?php

namespace KPHPGame\Scene\GameScene;

class Enemy extends Unit {
    public bool $triggered = false;

    public int $min_damage;
    public int $max_damage;

    public int $exp_reward;

    public static function newGoblin(): Enemy {
        return new Enemy('Goblin');
    }

    public static function newOrc(): Enemy {
        return new Enemy('Orc');
    }

    public static function newOgre(): Enemy {
        return new Enemy('Ogre');
    }

    public static function newBoss(): Enemy {
        return new Enemy('*BOSS*');
    }

    private function __construct(string $name) {
        $this->name = $name;

        if ($name === 'Goblin') {
            $this->hp         = 50;
            $this->min_damage = 1;
            $this->max_damage = 10;
            $this->level      = 1;
            $this->exp_reward = 4;
        } elseif ($name === 'Orc') {
            $this->hp         = 95;
            $this->min_damage = 3;
            $this->max_damage = 10;
            $this->level      = 2;
            $this->exp_reward = 7;
        } elseif ($name === 'Ogre') {
            $this->hp         = 150;
            $this->min_damage = 12;
            $this->max_damage = 20;
            $this->level      = 3;
            $this->exp_reward = 15;
        } elseif ($name === '*BOSS*') {
            $this->hp         = 300;
            $this->min_damage = 30;
            $this->max_damage = 50;
            $this->level      = 99;
            $this->exp_reward = 100;
        } else {
            throw new \RuntimeException("creating invalid enemy: $name");
        }
    }
}
