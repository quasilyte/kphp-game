<?php

namespace KPHPGame;

use KPHPGame\Scene\GameScene;
use Quasilyte\SDLite\SDL;
use RuntimeException;

class Game {
    /** @var SDL */
    private $sdl;

    /**
     * @throws RuntimeException
     */
    public function __construct() {
        SDL::loadCoreLib();
        SDL::loadImageLib();
        SDL::loadMixerLib();
        SDL::loadTTFLib();
        Logger::info("SDL load OK");

        $this->sdl = new SDL();
        if ($this->sdl->init() && $this->sdl->initTTF()) {
            Logger::info("SDL init OK");
        } else {
            throw new RuntimeException("init SDL: " . $this->sdl->getError());
        }
    }

    public function run(): void {
        $game_scene = new GameScene($this->sdl);
        $game_scene->run();
    }
}
