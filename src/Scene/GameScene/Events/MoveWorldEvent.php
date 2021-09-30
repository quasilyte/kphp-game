<?php

namespace KPHPGame\Scene\GameScene\Events;

use KPHPGame\Person;

class MoveWorldEvent implements WorldEvent {

    private Person $person;

    private string $direction;

    private function __construct(Person $person, string $direction) {
        $this->person    = $person;
        $this->direction = $direction;
    }

    public static function create(Person $person, string $direction): WorldEvent {
        return new MoveWorldEvent($person, $direction);
    }

    public function __toString(): string {
        return $this->person->getName() . " moved to $this->direction";
    }
}

