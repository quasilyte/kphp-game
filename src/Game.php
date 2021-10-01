<?php

namespace KPHPGame;

use KPHPGame\Scene\GameScene;
use KPHPGame\Scene\GameScene\Colors;
use Quasilyte\SDLite\EventType;
use Quasilyte\SDLite\Renderer;
use Quasilyte\SDLite\Scancode;
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
        $this->rect->x = 0;
        $this->rect->y = 0;
        $this->rect->w = GlobalConfig::WINDOW_WIDTH;
        $this->rect->h = GlobalConfig::WINDOW_HEIGHT;
        $this->draw->setDrawColor($this->colors->black);
        $this->draw->fillRect($this->rect);

        $text_surface  = $this->sdl->renderUTF8Blended($this->font, GlobalConfig::SPLASH_SCREEN_TEAM_NAME, $this->colors->white);
        $text_sizes    = $this->sdl->sizeUTF8($this->font, GlobalConfig::SPLASH_SCREEN_TEAM_NAME);
        $text_texture  = $this->sdl->createTextureFromSurface($this->sdl_renderer, $text_surface);
        $this->rect->x = (GlobalConfig::WINDOW_WIDTH >> 1) - ($text_sizes[0] >> 1);
        $this->rect->y = 80;
        $this->rect->w = $text_sizes[0];
        $this->rect->h = $text_sizes[1];
        $this->sdl->freeSurface($text_surface);
        if (!$this->draw->copy($text_texture, null, \FFI::addr($this->rect))) {
            throw new \RuntimeException($this->sdl->getError());
        }
        $this->sdl->destroyTexture($text_texture);

        $text_surface  = $this->sdl->renderUTF8Blended($this->font, GlobalConfig::SPLASH_SCREEN_PRESENTS_NAME, $this->colors->white);
        $text_sizes    = $this->sdl->sizeUTF8($this->font, GlobalConfig::SPLASH_SCREEN_PRESENTS_NAME);
        $text_texture  = $this->sdl->createTextureFromSurface($this->sdl_renderer, $text_surface);
        $this->rect->x = (GlobalConfig::WINDOW_WIDTH >> 1) - ($text_sizes[0] >> 1);
        $this->rect->y = 80 + $text_sizes[1] * 2;
        $this->rect->w = $text_sizes[0];
        $this->rect->h = $text_sizes[1];
        $this->sdl->freeSurface($text_surface);
        if (!$this->draw->copy($text_texture, null, \FFI::addr($this->rect))) {
            throw new \RuntimeException($this->sdl->getError());
        }
        $this->sdl->destroyTexture($text_texture);

        $text_surface  = $this->sdl->renderUTF8Blended($this->font, GlobalConfig::GAME_NAME, $this->colors->white);
        $text_sizes    = $this->sdl->sizeUTF8($this->font, GlobalConfig::GAME_NAME);
        $text_texture  = $this->sdl->createTextureFromSurface($this->sdl_renderer, $text_surface);
        $this->rect->x = (GlobalConfig::WINDOW_WIDTH >> 1) - ($text_sizes[0] >> 1);
        $this->rect->y = 80 + $text_sizes[1] * 4;
        $this->rect->w = $text_sizes[0];
        $this->rect->h = $text_sizes[1];
        $this->sdl->freeSurface($text_surface);
        if (!$this->draw->copy($text_texture, null, \FFI::addr($this->rect))) {
            throw new \RuntimeException($this->sdl->getError());
        }
        $this->sdl->destroyTexture($text_texture);

        $big_font      = $this->sdl->openFont(AssetsManager::font('FreeMono'), GlobalConfig::BIG_FONT_SIZE);
        $text_surface  = $this->sdl->renderUTF8Blended($big_font, GlobalConfig::SPLASH_SCREEN_PRESS_ENTER, $this->colors->white);
        $text_sizes    = $this->sdl->sizeUTF8($big_font, GlobalConfig::SPLASH_SCREEN_PRESS_ENTER);
        $text_texture  = $this->sdl->createTextureFromSurface($this->sdl_renderer, $text_surface);
        $this->rect->x = (GlobalConfig::WINDOW_WIDTH >> 1) - ($text_sizes[0] >> 1);
        $this->rect->y = (GlobalConfig::WINDOW_HEIGHT >> 1) - ($text_sizes[1] >> 1);
        $this->rect->w = $text_sizes[0];
        $this->rect->h = $text_sizes[1];
        $this->sdl->freeSurface($text_surface);
        if (!$this->draw->copy($text_texture, null, \FFI::addr($this->rect))) {
            throw new \RuntimeException($this->sdl->getError());
        }
        $this->sdl->destroyTexture($text_texture);

        $text_surface  = $this->sdl->renderUTF8Blended($this->font, GlobalConfig::SPLASH_SCREEN_POWERED_BY_KPHP, $this->colors->blue);
        $text_sizes    = $this->sdl->sizeUTF8($this->font, GlobalConfig::SPLASH_SCREEN_POWERED_BY_KPHP);
        $text_texture  = $this->sdl->createTextureFromSurface($this->sdl_renderer, $text_surface);
        $this->rect->x = (GlobalConfig::WINDOW_WIDTH >> 1) - ($text_sizes[0] >> 1);
        $this->rect->y = GlobalConfig::WINDOW_HEIGHT - $text_sizes[1] - GlobalConfig::TEXT_MARGIN;
        $this->rect->w = $text_sizes[0];
        $this->rect->h = $text_sizes[1];
        $this->sdl->freeSurface($text_surface);
        if (!$this->draw->copy($text_texture, null, \FFI::addr($this->rect))) {
            throw new \RuntimeException($this->sdl->getError());
        }
        $this->sdl->destroyTexture($text_texture);

        $this->draw->present();
        $event = $this->sdl->newEvent();
        $this->sdl->pollEvent($event);
        while (true) {
            if ($event->type === EventType::KEYUP) {
                $scancode = $event->key->keysym->scancode;
                if ($scancode === Scancode::RETURN) {
                    break;
                } elseif ($scancode === Scancode::ESCAPE) {
                    exit(0);
                }
            }
            $this->sdl->pollEvent($event);
        }
    }

    public function run(): void {
        $game_scene = new GameScene($this->sdl, $this->font, $this->colors, $this->sdl_renderer, $this->draw);
        $game_scene->run();
    }
}
