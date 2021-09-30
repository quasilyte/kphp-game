<?php

namespace KPHPGame\Scene;

use KPHPGame\AssetsManager;
use KPHPGame\GlobalConfig;
use KPHPGame\Logger;
use KPHPGame\Scene\GameScene\Events\WorldEventLogger;
use KPHPGame\Scene\GameScene\MapTile;
use KPHPGame\Scene\GameScene\PlayerAction;
use KPHPGame\Scene\GameScene\World;
use KPHPGame\Scene\GameScene\WorldGenerator;
use Quasilyte\SDLite\EventType;
use Quasilyte\SDLite\Renderer;
use Quasilyte\SDLite\Scancode;
use Quasilyte\SDLite\SDL;

// GameScene is a main gameplay scene.
class GameScene {
    public function __construct(SDL $sdl) {
        $this->sdl  = $sdl;
        $this->font = $sdl->openFont(AssetsManager::font('arbor'), GlobalConfig::FONT_SIZE);
    }

    public function run() {
        $this->sdl_window = $this->sdl->createWindow(
            GlobalConfig::GAME_NAME,
            SDL::WINDOWPOS_CENTERED,
            SDL::WINDOWPOS_CENTERED,
            GlobalConfig::WINDOW_WIDTH,
            GlobalConfig::WINDOW_HEIGHT);

        $this->sdl_renderer = $this->sdl->createRenderer($this->sdl_window);
        if (\FFI::isNull($this->sdl_renderer)) {
            throw new \RuntimeException($this->sdl->getError());
        }
        $draw = new Renderer($this->sdl, $this->sdl_renderer);

        Logger::info('generating world');
        $this->world = new World();
        WorldGenerator::generate($this->world);
        $this->world_event_logger = new WorldEventLogger($this->sdl, $draw, $this->font);
        $test_log_data            = WorldEventLogger::gen_test_data();
        foreach ($test_log_data as $log_event) {
            $this->world_event_logger->add_event($log_event);
        }

        Logger::info('rendering world');

        $this->loadTextures($draw);

        Logger::info('starting GameScene event loop');

        $event = $this->sdl->newEvent();
        while (true) {

            $this->processEvents($event);
            if ($this->escape) {
                break;
            }

            $this->processPlayerAction();

            $this->renderTiles($draw);
            $this->renderPlayer($draw);
            $this->world_event_logger->render();
            $draw->present();

            $this->player_action = PlayerAction::NONE;

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
                    } elseif ($scancode === Scancode::UP) {
                        $this->player_action = PlayerAction::MOVE_UP;
                    } elseif ($scancode === Scancode::DOWN) {
                        $this->player_action = PlayerAction::MOVE_DOWN;
                    } elseif ($scancode === Scancode::LEFT) {
                        $this->player_action = PlayerAction::MOVE_LEFT;
                    } elseif ($scancode === Scancode::RIGHT) {
                        $this->player_action = PlayerAction::MOVE_RIGHT;
                    }
                    break;
            }
        }
    }

    private function processPlayerAction(): void {
        $player = $this->world->player;
        $tile   = $this->world->getPlayerTile();

        $delta_col = 0;
        $delta_row = 0;
        if ($this->player_action === PlayerAction::MOVE_UP) {
            $delta_row = -1;
        } elseif ($this->player_action === PlayerAction::MOVE_DOWN) {
            $delta_row = +1;
        } elseif ($this->player_action === PlayerAction::MOVE_LEFT) {
            $delta_col = -1;
        } elseif ($this->player_action === PlayerAction::MOVE_RIGHT) {
            $delta_col = +1;
        }
        if ($delta_col !== 0 || $delta_row !== 0) {
            if ($this->world->getTile($tile->row+$delta_row, $tile->col+$delta_col)->kind !== MapTile::WALL) {
                $player->pos = $this->world->getTile($tile->row+$delta_row, $tile->col+$delta_col)->pos;
            }
            return;
        }
    }

    private function loadTextures(Renderer $draw): void {
        $surface = $this->sdl->imgLoad(AssetsManager::tile("wasteland_compact.png"));
        if (\FFI::isNull($surface)) {
            throw new \RuntimeException($this->sdl->getError());
        }
        $this->tileset_texture = $this->sdl->createTextureFromSurface($this->sdl_renderer, $surface);
        if (\FFI::isNull($this->tileset_texture)) {
            throw new \RuntimeException($this->sdl->getError());
        }
        $this->sdl->freeSurface($surface);

        $surface = $this->sdl->imgLoad(AssetsManager::unit("mage.png"));
        if (\FFI::isNull($surface)) {
            throw new \RuntimeException($this->sdl->getError());
        }
        $this->player_texture = $this->sdl->createTextureFromSurface($this->sdl_renderer, $surface);
        if (\FFI::isNull($this->player_texture)) {
            throw new \RuntimeException($this->sdl->getError());
        }
        $this->sdl->freeSurface($surface);
    }

    private function renderPlayer(Renderer $draw): void {
        $rect    = $this->sdl->newRect();
        $rect->w = 32;
        $rect->h = 32;
        $tile    = $this->world->getPlayerTile();
        $rect->x = $tile->col * 32;
        $rect->y = $tile->row * 32;
        if (!$draw->copy($this->player_texture, null, \FFI::addr($rect))) {
            throw new \RuntimeException($this->sdl->getError());
        }
    }

    private function renderTiles(Renderer $draw): void {
        $tile_rect    = $this->sdl->newRect();
        $tile_rect->w = GlobalConfig::TILE_WIDTH;
        $tile_rect->h = GlobalConfig::TILE_HEIGHT;

        $tile_pos    = $this->sdl->newRect();
        $tile_pos->w = $tile_rect->w;
        $tile_pos->h = $tile_rect->h;

        foreach ($this->world->tiles as $tile) {
            if ($tile->kind === MapTile::EMPTY) {
                $tile_rect->x = ($tile->tileset_index * $tile_rect->w);
                $tile_rect->y = 0;
            } elseif ($tile->kind === MapTile::WALL) {
                $tile_rect->x = 0;
                $tile_rect->y = $tile_rect->h * 1;
            }
            $tile_pos->x = $tile->col * $tile_rect->w;
            $tile_pos->y = $tile->row * $tile_rect->h;
            if (!$draw->copy($this->tileset_texture, \FFI::addr($tile_rect), \FFI::addr($tile_pos))) {
                throw new \RuntimeException($this->sdl->getError());
            }
        }
    }

    /** @var ffi_cdata<sdl, struct SDL_Renderer*> */
    private $sdl_renderer;

    private World $world;
    private $player_action = PlayerAction::NONE;

    /** @var ffi_cdata<sdl, struct SDL_Window*> */
    private $sdl_window;

    private SDL $sdl;

    private WorldEventLogger $world_event_logger;

    /** $var ffi_cdata<sdl_ttf, struct TTF_Font*> */
    private $font;
    /** @var ffi_cdata<sdl, struct SDL_Texture*> */
    private $tileset_texture;
    /** @var ffi_cdata<sdl, struct SDL_Texture*> */
    private $player_texture;

    private bool $escape = false;
}
