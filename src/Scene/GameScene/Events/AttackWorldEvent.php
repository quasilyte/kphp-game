<?php

namespace KPHPGame\Scene\GameScene\Events;

use KPHPGame\Person;
use KPHPGame\Scene\GameScene\Unit;

class AttackWorldEvent implements WorldEvent {

    private Unit $who;
    private Unit $whom;
    private int $damage;

    private function __construct(Unit $who, Unit $whom, int $damage) {
        $this->who    = $who;
        $this->whom   = $whom;
        $this->damage = $damage;
    }

    public static function create(Unit $who, Unit $whom, int $damage): WorldEvent {
        return new AttackWorldEvent($who, $whom, $damage);
    }

    public function __toString(): string {
        return $this->who->name . " deals $this->damage damage\nto the " . $this->whom->name;
    }
}