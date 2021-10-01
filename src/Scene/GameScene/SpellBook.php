<?php

namespace KPHPGame\Scene\GameScene;

class SpellBook {
    public function __construct() {
        $this->fireball = new Spell();
        $this->fireball->name = "fireball";
        $this->fireball->mp_cost = 10;
        $this->fireball->min_damage = 20;
        $this->fireball->max_damage = 30;

        $this->ice_shards = new Spell();
        $this->ice_shards->name = "ice shards";
        $this->ice_shards->mp_cost = 15;
        $this->ice_shards->min_damage = 40;
        $this->ice_shards->max_damage = 60;

        $this->thunder = new Spell();
        $this->thunder->name = "thunder";
        $this->thunder->mp_cost = 20;
        $this->thunder->min_damage = 15;
        $this->thunder->max_damage = 55;
    }

    public Spell $fireball;
    public Spell $ice_shards;
    public Spell $thunder;
}
