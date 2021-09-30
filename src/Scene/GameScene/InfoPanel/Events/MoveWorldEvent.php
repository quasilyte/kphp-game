<?php

namespace KPHPGame\Scene\GameScene\InfoPanel\Events;

use KPHPGame\Scene\GameScene\Unit;

class MoveWorldEvent implements WorldEvent {

    private Unit $person;

    private string $direction;

    /** @var string[] */
    private array $dir = ["", "up", "down", "left", "right"];

    private function __construct(Unit $person, int $direction) {
        $this->person    = $person;
        $this->direction = $this->dir[$direction];
    }

    public static function create(Unit $person, int $direction): WorldEvent {
        return new MoveWorldEvent($person, $direction);
    }

    public function __toString(): string {
        return $this->person->name . " moved to $this->direction";
    }
}

