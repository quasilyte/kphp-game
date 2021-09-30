<?php

namespace KPHPGame\Scene\GameScene\InfoPanel\Events;

use KPHPGame\Scene\GameScene\Spell;

class SpellCastWorldEvent implements WorldEvent {

    private Spell $spell;

    private function __construct(Spell $spell) {
        $this->spell = $spell;
    }

    public static function create(Spell $spell): WorldEvent {
        return new SpellCastWorldEvent($spell);
    }

    public function __toString(): string {
        return "Player casts {$this->spell->name}";
    }
}