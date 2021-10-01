<?php

namespace KPHPGame\Scene\GameScene\InfoPanel\Events;

class StageClearedWorldEvent implements WorldEvent {

    private function __construct() {
    }

    public static function create(): StageClearedWorldEvent {
        return new StageClearedWorldEvent();
    }

    public function __toString(): string {
        return 'Stage cleared!';
    }
}
