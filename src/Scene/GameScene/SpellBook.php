<?php

namespace KPHPGame\Scene\GameScene;

class SpellBook {
    public function __construct() {
        $this->fireball = new Spell();
        $this->fireball->name = "fireball";
        $this->fireball->mp_cost = 10;
        $this->fireball->min_damage = 20;
        $this->fireball->max_damage = 30;

        $this->tornado = new Spell();
        $this->tornado->name = "tornado";
        $this->tornado->mp_cost = 15;
        $this->tornado->min_damage = 40;
        $this->tornado->max_damage = 60;

        $this->thunder = new Spell();
        $this->thunder->name = "thunder";
        $this->thunder->mp_cost = 20;
        $this->thunder->min_damage = 15;
        $this->thunder->max_damage = 55;
    }

    public Spell $fireball;
    public Spell $tornado;
    public Spell $thunder;
}
