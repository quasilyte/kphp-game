<?php

namespace KPHPGame\Scene\GameScene\InfoPanel\Events;

class NotEnoughManaWorldEvent implements WorldEvent {

    private function __construct() {
    }

    public static function create(): NotEnoughManaWorldEvent {
        return new NotEnoughManaWorldEvent();
    }

    public function __toString(): string {
        return 'Not enough mana';
    }
}
