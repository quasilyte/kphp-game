<?php

namespace KPHPGame\Scene\GameScene\Events;

use KPHPGame\Person;

class DieWorldEvent implements WorldEvent {

    private Person $person;

    public function __construct(Person $who) {
        $this->person = $who;
    }

    public static function create(Person $person): WorldEvent {
        return new DieWorldEvent($person);
    }

    public function __toString(): string {
        return $this->person->getName() . " died";
    }
}