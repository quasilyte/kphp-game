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

    /** @var ffi_cdata<sdl, struct SDL_Rect> */
    private $rect;

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

        if (!$this->sdl->openAudio(22050, SDL::AUDIO_S16LSB, 2, 4096)) {
            throw new RuntimeException("init audio: " . $this->sdl->getError());
        }

        $this->colors     = new Colors();
        $this->font       = $this->sdl->openFont(AssetsManager::font('FreeMono'), GlobalConfig::FONT_SIZE);
        $this->sdl_window = $this->sdl->createWindow(
            GlobalConfig::GAME_NAME,
            SDL::WINDOWPOS_CENTERED,
            SDL::WINDOWPOS_CENTERED,
            GlobalConfig::WINDOW_WIDTH,
            GlobalConfig::WINDOW_HEIGHT
        );
        if (\FFI::isNull($this->sdl_window)) {
            throw new RuntimeException($this->sdl->getError());
        }

        $this->sdl_renderer = $this->sdl->createRenderer($this->sdl_window);
        $this->draw         = new Renderer($this->sdl, $this->sdl_renderer);
        $this->rect         = $this->sdl->newRect();
    }

    public function runSplashScreen(): void {
        Logger::info('starting splash screen');
        $this->draw->clear();
        var_dump($this->sdl->getError());
        $this->rect->x = 0;
        $this->rect->y = 0;
        $this->rect->w = GlobalConfig::WINDOW_WIDTH;
        $this->rect->h = GlobalConfig::WINDOW_HEIGHT;
        $this->draw->setDrawColor($this->colors->black);
        var_dump($this->sdl->getError());
        $this->draw->fillRect($this->rect);
        var_dump($this->sdl->getError());
        $this->draw->present();
        var_dump($this->sdl->getError());
    }

    public function run(): void {
        $game_scene = new GameScene($this->sdl, $this->font, $this->colors, $this->sdl_renderer, $this->draw);
        $game_scene->run();
    }
}
