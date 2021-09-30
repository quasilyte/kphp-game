<?php

namespace KPHPGame\Scene\GameScene\Events;

use KPHPGame\Scene\GameScene\Unit;

class DieWorldEvent implements WorldEvent {

    private Unit $person;

    public function __construct(Unit $who) {
        $this->person = $who;
    }

    public static function create(Unit $person): WorldEvent {
        return new DieWorldEvent($person);
    }

    public function __toString(): string {
        return $this->person->name . " just died";
    }
}