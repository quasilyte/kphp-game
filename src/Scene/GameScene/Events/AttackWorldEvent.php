<?php

namespace KPHPGame\Scene\GameScene\Events;

use KPHPGame\Person;

class AttackWorldEvent implements WorldEvent {

    private Person $who;
    private Person $whom;
    private int $damage;

    private function __construct(Person $who, Person $whom, int $damage) {
        $this->who    = $who;
        $this->whom   = $whom;
        $this->damage = $damage;
    }

    public static function create(Person $who, Person $whom, int $damage): WorldEvent {
        return new AttackWorldEvent($who, $whom, $damage);
    }

    public function __toString(): string {
        return $this->who->getName() . " deals $this->damage damage\nto the " . $this->whom->getName();
    }
}