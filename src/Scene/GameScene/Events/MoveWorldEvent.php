<?php

namespace KPHPGame\Scene\GameScene\Events;

use KPHPGame\Person;

class MoveWorldEvent implements WorldEvent {

    private Person $person;

    private string $direction;

    /** @var string[] */
    private array $dir = ["", "up", "down", "left", "right"];

    private function __construct(Person $person, int $direction) {
        $this->person    = $person;
        $this->direction = $this->dir[$direction];
    }

    public static function create(Person $person, int $direction): WorldEvent {
        return new MoveWorldEvent($person, $direction);
    }

    public function __toString(): string {
        return $this->person->getName() . " moved to $this->direction";
    }
}

