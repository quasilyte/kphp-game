<?php

namespace KPHPGame\Scene;

use KPHPGame\GlobalConfig;
use KPHPGame\Logger;
use KPHPGame\Scene\GameScene\World;
use KPHPGame\Scene\GameScene\WorldGenerator;
use Quasilyte\SDLite\EventType;
use Quasilyte\SDLite\Renderer;
use Quasilyte\SDLite\Scancode;
use Quasilyte\SDLite\SDL;

// GameScene is a main gameplay scene.
class GameScene {
  public function __construct(SDL $sdl) {
    $this->sdl = $sdl;
  }

  public function run() {
    $this->sdl_window = $this->sdl->createWindow(
      GlobalConfig::GAME_NAME,
      SDL::WINDOWPOS_CENTERED,
      SDL::WINDOWPOS_CENTERED,
      GlobalConfig::WINDOW_WIDTH,
      GlobalConfig::WINDOW_HEIGHT);

    $this->sdl_renderer = $this->sdl->createRenderer($this->sdl_window, -1);
    $draw = new Renderer($this->sdl, $this->sdl_renderer);

    $this->world = WorldGenerator::generate();

    Logger::info("starting GameScene event loop");

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
  private function processEvents($event): void {
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
  private $sdl_window;

  /** @var ffi_cdata<sdl, struct SDL_Renderer*> */
  private $sdl_renderer;

  /** @var World */
  private $world;

  private $escape = false;
}
