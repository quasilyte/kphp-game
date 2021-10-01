<?php

namespace KPHPGame\Scene\GameScene;

class Player extends Unit {
    public int $mp;
    public int $max_mp;
    public int $exp;
    public int $next_level_exp;
    public SpellBook $spellbook;
    public bool $on_portal;

    public function __construct() {
        $this->hp             = 50;
        $this->max_hp         = 50;
        $this->mp             = 200;
        $this->max_mp         = 200;
        $this->name           = "Player";
        $this->exp            = 0;
        $this->level          = 1;
        $this->next_level_exp = 10;
        $this->on_portal      = false;

        $this->spellbook = new SpellBook();
    }

    public function rollSpellDamage(Spell $spell): int {
        $base_damage = rand($spell->min_damage, $spell->max_damage);
        $bonus_damage = rand($this->level-1, ($this->level-1) * 3);
        return $base_damage + $bonus_damage;
    }

    public function addExp(int $exp): int {
        $gained_levels = 0;
        $this->exp += $exp;
        while ($this->exp >= $this->next_level_exp) {
            $gained_levels++;
            $this->lvlUp();
        }
        return $gained_levels;
    }

    private function lvlUp() {
        $this->level++;

        $this->max_hp += 10;
        $this->hp     = $this->max_hp;

        $this->max_mp += 20;
        $this->mp     = $this->max_mp;

        $this->next_level_exp = ($this->next_level_exp * 2) + 5;
    }
}
