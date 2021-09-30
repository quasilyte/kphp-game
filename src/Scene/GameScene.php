<?php

namespace KPHPGame\Scene;

use KPHPGame\AssetsManager;
use KPHPGame\GlobalConfig;
use KPHPGame\Logger;
use KPHPGame\Scene\GameScene\Colors;
use KPHPGame\Scene\GameScene\Direction;
use KPHPGame\Scene\GameScene\Enemy;
use KPHPGame\Scene\GameScene\InfoPanel\Events\AttackWorldEvent;
use KPHPGame\Scene\GameScene\InfoPanel\Events\DieWorldEvent;
use KPHPGame\Scene\GameScene\InfoPanel\Events\LevelUpWorldEvent;
use KPHPGame\Scene\GameScene\InfoPanel\Events\SpellCastWorldEvent;
use KPHPGame\Scene\GameScene\InfoPanel\StatusRenderer;
use KPHPGame\Scene\GameScene\InfoPanel\WorldEventLogRenderer;
use KPHPGame\Scene\GameScene\MapTile;
use KPHPGame\Scene\GameScene\Player;
use KPHPGame\Scene\GameScene\PlayerAction;
use KPHPGame\Scene\GameScene\AlertWindowRenderer;
use KPHPGame\Scene\GameScene\Spell;
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
    private WorldEventLogRenderer $world_event_log_renderer;
    private StatusRenderer $status_renderer;
    private AlertWindowRenderer $text_block_renderer;
    /** $var ffi_cdata<sdl_ttf, struct TTF_Font*> */
    private $font;
    private Colors $colors;
    /** @var ffi_cdata<sdl, struct SDL_Texture*> */
    private $tileset_texture;
    /** @var ffi_cdata<sdl, struct SDL_Texture*> */
    private $portal_texture;
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
        $this->colors = new Colors();
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
        $draw       = new Renderer($this->sdl, $this->sdl_renderer);
        $this->draw = $draw;

        Logger::info('generating world');
        $this->initWorld();
        $this->world_event_log_renderer = new WorldEventLogRenderer($this->sdl, $this->sdl_renderer, $draw, $this->font, $this->colors->white);
        $this->status_renderer          = new StatusRenderer($this->sdl, $this->sdl_renderer, $draw, $this->font, $this->colors->white);
        $this->text_block_renderer      = new AlertWindowRenderer($this->sdl, $this->draw, $this->sdl_renderer);

        Logger::info('rendering world');

        $this->loadTextures();

        Logger::info('starting GameScene event loop');
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

            if ($this->world->tileIsPortal()) {
                Logger::info('i am on portal');
                $stage = $this->world->stage;
                $text = ["You find entrance to $stage stage", 'Do you want to go now?[y/n]'];
                $this->text_block_renderer->render($text, $this->font, $this->colors->white, $this->colors->black);
            }

            $this->player_action = PlayerAction::NONE;

            $this->sdl->delay(GlobalConfig::FRAME_DELAY);
        }

        // TODO: SDL_DestroyWindow
    }

    private function initWorld(?Player $player = null, int $stage = 1) {
        if ($player === null) {
            $player = new Player();
        }
        $this->world = new World($player, $stage);
        WorldGenerator::generate($this->world);
        $this->defeat = false;
        $this->onPlayerMoved();
    }

    private function loadTextures(): void {
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

        $surface = $this->sdl->imgLoad(AssetsManager::tile("portal.png"));
        if (\FFI::isNull($surface)) {
            throw new RuntimeException($this->sdl->getError());
        }
        $this->portal_texture = $this->sdl->createTextureFromSurface($this->sdl_renderer, $surface);
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
                    if ($scancode === Scancode::ESCAPE || ($scancode === Scancode::N && $this->defeat)) {
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
                    } elseif ($scancode === Scancode::Y) {
                        $this->initWorld();
                        $this->renderAll($this->draw);
                    } else if ($scancode === Scancode::Q) {
                        $this->player_action = PlayerAction::MAGIC_FIREBALL;
                    } else if ($scancode === Scancode::W) {
                        $this->player_action = PlayerAction::MAGIC_TORNADO;
                    } else if ($scancode === Scancode::E) {
                        $this->player_action = PlayerAction::MAGIC_THUNDER;
                    }
                    break;
            }
        }
    }

    private function castThunder(): void {
        foreach ($this->world->enemies as $enemy) {
            

        }
    }

    private function processPlayerAction(): bool {
        if ($this->defeat) {
            return false;
        }

        $player = $this->world->player;
        $tile   = $this->world->getPlayerTile();

        /** @var Spell $spell */
        $spell = null;
        if ($this->player_action === PlayerAction::MAGIC_FIREBALL) {
            $spell = $player->spellbook->fireball;
        } else if ($this->player_action === PlayerAction::MAGIC_TORNADO) {
            $spell = $player->spellbook->tornado;
        } else if ($this->player_action === PlayerAction::MAGIC_THUNDER) {
            $spell = $player->spellbook->thunder;
        }
        if ($spell !== null) {
            if ($player->mp < $spell->mp_cost) {
                return false; // Not enough mana
            }
            $player->mp -= $spell->mp_cost;
            $this->world_event_log_renderer->add_event(SpellCastWorldEvent::create($spell));
            if ($spell === $player->spellbook->thunder) {
                $this->castThunder();
            }
            return true;
        }

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
            $new_tile          = $this->world->calculateStepTile($tile, $player->direction);
            if ($new_tile->pos !== $tile->pos) {
                if ($this->world->tileIsFree($new_tile)) {
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
        $this->world_event_log_renderer->add_event(AttackWorldEvent::create($attacker, $this->world->player, $damage_roll));
        $this->world->player->hp -= $damage_roll;
        $this->onPlayerDamageTaken();
    }

    private function onPlayerDamageTaken() {
//        $this->world->player->lvlUp();
//        $this->world_event_log_renderer->add_event(LevelUpWorldEvent::create($this->world->player));

        if ($this->world->player->hp <= 0) {
            $this->world_event_log_renderer->add_event(DieWorldEvent::create($this->world->player));
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
                $enemy->direction = $this->world->tiles[$enemy->pos]->directionTo($player_tile);
            } else {
                // 10% chance to stand still.
                if (rand(0, 99) < 10) {
                    continue;
                }
                // 20% chance to a random step, 80% to make a step towards the player
                if (rand(0, 99) < 20) {
                    $dir = Direction::random();
                } else {
                    $dir = $this->world->tiles[$enemy->pos]->directionTo($player_tile);
                }
                $new_tile = $this->world->calculateStepTile($enemy_tile, $dir);
                if (!$this->world->tileIsFree($new_tile)) {
                    $new_dir = Direction::random();
                    [$row, $col] = $this->world->calculateStep($enemy_tile, $new_dir);
                    if ($this->world->hasTileAt($row, $col)) {
                        $new_tile = $this->world->getTile($row, $col);
                        $dir      = $new_dir;
                    }
                }
                if ($this->world->tileIsFree($new_tile)) {
                    $enemy->direction = $dir;
                    $enemy->pos       = $new_tile->pos;
                }
            }
        }
    }

    private function renderAll(Renderer $draw): void {
        $draw->clear();
        $this->renderTiles($draw);
        $this->renderPortal();
        $this->renderPlayer($draw);
        $this->renderEnemies($draw);
        $this->world_event_log_renderer->render();
        $this->status_renderer->render($this->world->player, $this->world->stage);
        if ($this->defeat) {
            $this->renderRestartMenu();
        }
        $draw->present();
    }

    /**
     * @param ffi_cdata<sdl, struct SDL_Texture*> $texture
     */
    private function renderOneTile($texture, int $row, int $col, int $tile_offset_x = 0, int $tile_offset_y = 0) {
        $draw_rect    = $this->sdl->newRect();
        $draw_rect->w = GlobalConfig::TILE_WIDTH;
        $draw_rect->h = GlobalConfig::TILE_HEIGHT;
        $draw_rect->x = $tile_offset_x * GlobalConfig::TILE_WIDTH;
        $draw_rect->y = $tile_offset_y * GlobalConfig::TILE_HEIGHT;

        $tile = $this->world->getTile($row, $col);
        $draw_pos    = $this->sdl->newRect();
        $draw_pos->w = $draw_rect->w;
        $draw_pos->h = $draw_rect->h;
        $draw_pos->x = $tile->col * GlobalConfig::TILE_WIDTH;
        $draw_pos->y = $tile->row * GlobalConfig::TILE_HEIGHT;

        if (!$this->draw->copy($texture, \FFI::addr($draw_rect), \FFI::addr($draw_pos))) {
            throw new RuntimeException($this->sdl->getError());
        }
    }

    private function renderPortal(): void {
        $tile = $this->world->tiles[$this->world->portal_pos];
        if ($tile->revealed) {
            $this->renderOneTile($this->portal_texture, $tile->row, $tile->col);
        }
        if ($this->world->getTile($tile->row, $tile->col+1)->revealed) {
            $this->renderOneTile($this->portal_texture, $tile->row, $tile->col+1, 1);
        }
        if ($this->world->getTile($tile->row+1, $tile->col)->revealed) {
            $this->renderOneTile($this->portal_texture, $tile->row+1, $tile->col, 0, 1);
        }
        if ($this->world->getTile($tile->row+1, $tile->col+1)->revealed) {
            $this->renderOneTile($this->portal_texture, $tile->row+1, $tile->col+1, 1, 1);
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
            if (!$tile->revealed) {
                continue;
            }
            if ($tile->kind === MapTile::PORTAL || $tile->kind === MapTile::EMPTY) {
                $tile_rect->x = ($tile->tileset_index * $tile_rect->w);
                $tile_rect->y = 0;
            } elseif ($tile->kind === MapTile::WALL) {
                $tile_rect->x = 0;
                $tile_rect->y = $tile_rect->h * 1;
            } elseif ($tile->kind === MapTile::ROCK) {
                $tile_rect->x = ($tile->tileset_index * $tile_rect->w);
                $tile_rect->y = $tile_rect->h * 2;
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
            if ($this->world->tiles[$unit->pos]->revealed) {
                $this->renderUnit($draw, $unit);
            }
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

    private function renderRestartMenu() {
        $text       = [
            "Game Over",
            "Restart? [y/n]",
        ];
        $this->text_block_renderer->render($text, $this->font, $this->colors->red, $this->colors->black);
    }
}
