<?php

namespace KPHPGame\Scene\GameScene;

class Player extends Unit {
    public int $mp;
    public int $max_mp;
    public int $exp;
    public int $next_level_exp;
    public SpellBook $spellbook;

    public function __construct() {
        $this->hp             = 50;
        $this->max_hp         = 50;
        $this->mp             = 200;
        $this->max_mp         = 200;
        $this->name           = "Player";
        $this->exp            = 0;
        $this->level          = 1;
        $this->next_level_exp = (1 << $this->level + 2);

        $this->spellbook = new SpellBook();
    }

    public function lvlUp() {
        $this->level  += 1;
        $this->max_hp += 10;
        $this->hp     = $this->max_hp;

        $this->max_mp += 10;
        $this->mp     = $this->max_mp;

        $this->next_level_exp = 50 * $this->level;
    }
}
