<?php

namespace KPHPGame\Scene\GameScene\InfoPanel\Events;

use KPHPGame\Scene\GameScene\Player;

class LevelUpWorldEvent implements WorldEvent {

    private Player $player;

    private function __construct(Player $player) {
        $this->player = $player;
    }

    public static function create(Player $player): LevelUpWorldEvent {
        return new LevelUpWorldEvent($player);
    }

    public function __toString(): string {
        return "Level up! " . $this->player->name . " is " . $this->player->level . "\n level now.";
    }
}
