<?php

namespace KPHPGame;

use KPHPGame\Scene\GameScene;
use KPHPGame\Scene\GameScene\Colors;
use Quasilyte\SDLite\Renderer;
use Quasilyte\SDLite\SDL;
use RuntimeException;

class Game {
    /** @var SDL */
    private $sdl;

    /** @var ffi_cdata<sdl, struct SDL_Window*> */
    private $sdl_window;

    /** @var ffi_cdata<sdl, struct SDL_Renderer*> */
    private $sdl_renderer;

    private Renderer $draw;

    private GameScene\Colors $colors;

    /** @var ffi_cdata<sdl_ttf, struct TTF_Font*> */
    private $font;

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

        $this->colors     = new Colors();
        $this->font       = $this->sdl->openFont(AssetsManager::font('FreeMono'), GlobalConfig::FONT_SIZE);
        $this->sdl_window = $this->sdl->createWindow(
            GlobalConfig::GAME_NAME,
            SDL::WINDOWPOS_CENTERED,
            SDL::WINDOWPOS_CENTERED,
            GlobalConfig::WINDOW_WIDTH,
            GlobalConfig::WINDOW_HEIGHT);

        $this->sdl_renderer = $this->sdl->createRenderer($this->sdl_window);
        $this->draw = new Renderer($this->sdl, $this->sdl_renderer);
    }

    public function runSplashScreen(): void {
        $this->draw->clear();
        $rect = $this->sdl->newRect();
//        $rect.x = 0;
//        $rect.y = 0;
//        $rect.w = 0;
//        $rect.h = 0;
        $this->draw->present();
        Logger::info('test?');
        sleep(1);
    }

    public function run(): void {
        $game_scene = new GameScene($this->sdl, $this->font, $this->colors, $this->sdl_renderer, $this->draw);
        $game_scene->run();
    }
}
