<?php

namespace KPHPGame\Scene\GameScene\InfoPanel\Events;

class AltarVisitedWorldEvent implements WorldEvent {

    private string $message ;

    private function __construct(string $message) {
        $this->message = $message;
    }

    public static function create(string $message): AltarVisitedWorldEvent {
        return new AltarVisitedWorldEvent($message);
    }

    public function __toString(): string {
        return 'Altar grants ' . $this->message;
    }
}
