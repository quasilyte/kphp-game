<?php

namespace KPHPGame\Scene;

use KPHPGame\GlobalConfig;
use Quasilyte\SDLite\EventType;
use Quasilyte\SDLite\Renderer;
use Quasilyte\SDLite\Scancode;
use Quasilyte\SDLite\SDL;

class GameScene {
  public function __construct(SDL $sdl) {
    $this->sdl = $sdl;
  }

  public function run() {
    $this->window = $this->sdl->createWindow(
      GlobalConfig::GAME_NAME,
      SDL::WINDOWPOS_CENTERED,
      SDL::WINDOWPOS_CENTERED,
      GlobalConfig::WINDOW_WIDTH,
      GlobalConfig::WINDOW_HEIGHT);

    $this->sdl_renderer = $this->sdl->createRenderer($this->window, -1);
    $draw = new Renderer($this->sdl, $this->sdl_renderer);

    $event = $this->sdl->newEvent();
    while (true) {
      $this->processEvents($event);
      if ($this->escape) {
        break;
      }

      $draw->present();

      $this->sdl->delay(GlobalConfig::FRAME_DELAY);
    }

    // TODO: SDL_DestroyWindow
  }

  /** @param ffi_cdata<sdl, struct SDL_Event> $event */
  public function processEvents($event): void {
    while ($this->sdl->pollEvent($event)) {
      switch ($event->type) {
        case EventType::KEYUP:
          $scancode = $event->key->keysym->scancode;
          if ($scancode === Scancode::ESCAPE) {
            $this->escape = true;
            break;
          }
      }
    }
  }

  /** @var SDL */
  private $sdl;

  /** @var ffi_cdata<sdl, struct SDL_Window*> */
  private $window;

  /** @var ffi_cdata<sdl, struct SDL_Renderer*> */
  private $sdl_renderer;

  private $escape = false;
}
