<?php

namespace KPHPGame\Scene;

use KPHPGame\AssetsManager;
use KPHPGame\GlobalConfig;
use KPHPGame\Logger;
use KPHPGame\Person;
use KPHPGame\Scene\GameScene\Direction;
use KPHPGame\Scene\GameScene\Enemy;
use KPHPGame\Scene\GameScene\Events\AttackWorldEvent;
use KPHPGame\Scene\GameScene\Events\DieWorldEvent;
use KPHPGame\Scene\GameScene\Events\MoveWorldEvent;
use KPHPGame\Scene\GameScene\Events\WorldEventLogger;
use KPHPGame\Scene\GameScene\MapTile;
use KPHPGame\Scene\GameScene\PlayerAction;
use KPHPGame\Scene\GameScene\InfoPanel\StatusRenderer;
use KPHPGame\Scene\GameScene\Unit;
use KPHPGame\Scene\GameScene\World;
use KPHPGame\Scene\GameScene\WorldGenerator;
use Quasilyte\SDLite\EventType;
use Quasilyte\SDLite\Renderer;
use Quasilyte\SDLite\Scancode;
use Quasilyte\SDLite\SDL;
use RuntimeException;

// GameScene is a main gameplay scene.
class GameScene {
    /** @var ffi_cdata<sdl, struct SDL_Renderer*> */
    private $sdl_renderer;
    private Renderer $draw;
    private World $world;
    private $player_action = PlayerAction::NONE;
    /** @var ffi_cdata<sdl, struct SDL_Window*> */
    private $sdl_window;
    private SDL $sdl;
    private WorldEventLogger $world_event_logger;
    private StatusRenderer $status_renderer;
    /** $var ffi_cdata<sdl_ttf, struct TTF_Font*> */
    private $font;
    /** @var ffi_cdata<sdl, struct SDL_Texture*> */
    private $tileset_texture;
    /** @var ffi_cdata<sdl, struct SDL_Texture*> */
    private $player_texture;
    /** @var ffi_cdata<sdl, struct SDL_Texture*> */
    private $orc_texture;
    private bool $escape = false;
    private bool $defeat = false;
    private int $turn_num = 0;

    public function __construct(SDL $sdl) {
        $this->sdl  = $sdl;
        $this->font = $sdl->openFont(AssetsManager::font('FreeMono'), GlobalConfig::FONT_SIZE);
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
            throw new RuntimeException($this->sdl->getError());
        }
        $draw = new Renderer($this->sdl, $this->sdl_renderer);
        $this->draw = $draw;

        Logger::info('generating world');
        $this->world = new World();
        WorldGenerator::generate($this->world);
        $this->world_event_logger = new WorldEventLogger($this->sdl, $this->sdl_renderer, $draw, $this->font);
        $this->status_renderer    = new StatusRenderer($this->sdl, $this->sdl_renderer, $draw, $this->font);

        Logger::info('rendering world');

        $this->loadTextures($draw);

        Logger::info('starting GameScene event loop');

        $this->onPlayerMoved();
        $this->renderAll($draw);

        $event = $this->sdl->newEvent();
        while (true) {
            $this->processEvents($event);
            if ($this->escape) {
                break;
            }

            if ($this->processPlayerAction()) {
                $this->onNewTurn();
                $this->renderAll($draw);
            }

            $this->player_action = PlayerAction::NONE;

            $this->sdl->delay(GlobalConfig::FRAME_DELAY);
        }

        // TODO: SDL_DestroyWindow
    }

    private function loadTextures(Renderer $draw): void {
        $surface = $this->sdl->imgLoad(AssetsManager::tile("wasteland_compact.png"));
        if (\FFI::isNull($surface)) {
            throw new RuntimeException($this->sdl->getError());
        }
        $this->tileset_texture = $this->sdl->createTextureFromSurface($this->sdl_renderer, $surface);
        if (\FFI::isNull($this->tileset_texture)) {
            throw new RuntimeException($this->sdl->getError());
        }
        $this->sdl->freeSurface($surface);

        $surface = $this->sdl->imgLoad(AssetsManager::unit("Player.png"));
        if (\FFI::isNull($surface)) {
            throw new RuntimeException($this->sdl->getError());
        }
        $this->player_texture = $this->sdl->createTextureFromSurface($this->sdl_renderer, $surface);
        if (\FFI::isNull($this->player_texture)) {
            throw new RuntimeException($this->sdl->getError());
        }
        $this->sdl->freeSurface($surface);

        $surface = $this->sdl->imgLoad(AssetsManager::unit("Orc.png"));
        if (\FFI::isNull($surface)) {
            throw new RuntimeException($this->sdl->getError());
        }
        $this->orc_texture = $this->sdl->createTextureFromSurface($this->sdl_renderer, $surface);
        if (\FFI::isNull($this->player_texture)) {
            throw new RuntimeException($this->sdl->getError());
        }
        $this->sdl->freeSurface($surface);
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
                    } elseif ($scancode === Scancode::SPACE) {
                        $this->player_action = PlayerAction::ATTACK;
                    }
                    break;
            }
        }
    }

    private function calculateStep(Unit $unit, int $dir): MapTile {
        $tile = $this->world->tiles[$unit->pos];
        $delta_col = 0;
        $delta_row = 0;
        if ($dir === Direction::UP) {
            $delta_row = -1;
        } elseif ($dir === Direction::DOWN) {
            $delta_row = +1;
        } elseif ($dir === Direction::LEFT) {
            $delta_col = -1;
        } elseif ($dir === Direction::RIGHT) {
            $delta_col = +1;
        }
        return $this->world->getTile($tile->row + $delta_row, $tile->col + $delta_col);
    }

    private function tileIsFree(MapTile $tile): bool {
        if ($tile->kind === MapTile::WALL) {
            return false;
        }
        foreach ($this->world->enemies as $enemy) {
            if ($enemy->pos === $tile->pos) {
                return false;
            }
        }
        return true;
    }

    private function processPlayerAction(): bool {
        if ($this->defeat) {
            return false;
        }

        $player = $this->world->player;
        $tile   = $this->world->getPlayerTile();

        $dir = Direction::NONE;
        if ($this->player_action === PlayerAction::MOVE_UP) {
            $dir = Direction::UP;
        } elseif ($this->player_action === PlayerAction::MOVE_DOWN) {
            $dir = Direction::DOWN;
        } elseif ($this->player_action === PlayerAction::MOVE_LEFT) {
            $dir = Direction::LEFT;
        } elseif ($this->player_action === PlayerAction::MOVE_RIGHT) {
            $dir = Direction::RIGHT;
        }
        if ($dir !== Direction::NONE) {
            $player->direction = $dir;
            $new_tile = $this->calculateStep($player, $player->direction);
            if ($new_tile->pos !== $tile->pos) {
                if ($this->tileIsFree($new_tile)) {
                    $player->pos = $new_tile->pos;
                }
                $this->onPlayerMoved();
                return true;
            }
        }

        return false;
    }

    private function onPlayerMoved(): void {
        $tile = $this->world->getPlayerTile();

        foreach ($this->world->enemies as $enemy) {
            if (!$enemy->triggered && $tile->distTo($this->world->tiles[$enemy->pos]) <= 2) {
                $enemy->triggered = true;
            }
        }

        // Update fog of war.
        for ($delta_row = -2; $delta_row <= 2; $delta_row++) {
            for ($delta_col = -2; $delta_col <= 2; $delta_col++) {
                if ($delta_row === -2 && $delta_col === -2) {
                    continue;
                }
                if ($delta_row === -2 && $delta_col === +2) {
                    continue;
                }
                if ($delta_row === +2 && $delta_col === -2) {
                    continue;
                }
                if ($delta_row === +2 && $delta_col === +2) {
                    continue;
                }
                $row = $tile->row + $delta_row;
                $col = $tile->col + $delta_col;
                if ($col >= 0 && $row >= 0 && $col < $this->world->map_cols && $row < $this->world->map_rows) {
                    $this->world->getTile($row, $col)->revealed = true;
                }
            }
        }
    }

    private function attackPlayer(Enemy $attacker) {
        $damage_roll = rand($attacker->min_damage, $attacker->max_damage);
        $this->world_event_logger->add_event(AttackWorldEvent::create($attacker, $this->world->player, $damage_roll));
        $this->world->player->hp -= $damage_roll;
        $this->onPlayerDamageTaken();
    }

    private function onPlayerDamageTaken() {
        if ($this->world->player->hp <= 0) {
            $this->world_event_logger->add_event(DieWorldEvent::create($this->world->player));
            $this->onDefeat();
        }
    }

    private function onDefeat() {
        $this->defeat = true;
        $this->renderAll($this->draw);
    }

    private function onNewTurn(): void {
        $this->turn_num++;

        $player_tile = $this->world->getPlayerTile();

        // Enemies try to come closer.
        foreach ($this->world->enemies as $enemy) {
            if (!$enemy->triggered) {
                continue;
            }
            $enemy_tile = $this->world->tiles[$enemy->pos];
            if ($enemy_tile->distTo($player_tile) <= 1) {
                $this->attackPlayer($enemy);
            } else {
                $dir = $this->world->tiles[$enemy->pos]->rotationTo($player_tile);
                $new_tile = $this->calculateStep($enemy, $dir);
                $enemy->direction = $dir;
                $enemy->pos = $new_tile->pos;
            }
        }
    }

    private function renderAll(Renderer $draw): void {
        $draw->clear();
        $this->renderTiles($draw);
        $this->renderPlayer($draw);
        $this->renderEnemies($draw);
        $this->world_event_logger->render();
        $this->status_renderer->render($this->world->player);
        $draw->present();
    }

    private function renderTiles(Renderer $draw): void {
        $tile_rect    = $this->sdl->newRect();
        $tile_rect->w = GlobalConfig::TILE_WIDTH;
        $tile_rect->h = GlobalConfig::TILE_HEIGHT;

        $tile_pos    = $this->sdl->newRect();
        $tile_pos->w = $tile_rect->w;
        $tile_pos->h = $tile_rect->h;

        foreach ($this->world->tiles as $tile) {
            if (!$tile->revealed) {
                continue;
            }
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
                throw new RuntimeException($this->sdl->getError());
            }
        }
    }

    private function renderPlayer(Renderer $draw): void {
        $this->renderUnit($draw, $this->world->player);
    }

    private function renderEnemies(Renderer $draw) {
        foreach ($this->world->enemies as $unit) {
            $this->renderUnit($draw, $unit);
        }
    }

    private function renderUnit(Renderer $draw, Unit $unit) {
        $tile_rect    = $this->sdl->newRect();
        $tile_rect->w = GlobalConfig::TILE_WIDTH;
        $tile_rect->h = 48;
        if ($unit->hp > 0) {
            $tile_rect->x = 32 * $unit->direction;
        } else {
            $tile_rect->x = 32 * 4;
        }

        $tile          = $this->world->tiles[$unit->pos];
        $render_pos    = $this->sdl->newRect();
        $render_pos->w = 32;
        $render_pos->h = 48;
        $render_pos->x = $tile->col * 32;
        $render_pos->y = ($tile->row * 32) - 16;

        $texture = $this->player_texture;
        if ($unit->name === 'Orc') {
            $texture = $this->orc_texture;
        }

        if (!$draw->copy($texture, \FFI::addr($tile_rect), \FFI::addr($render_pos))) {
            throw new RuntimeException($this->sdl->getError());
        }
    }
}
