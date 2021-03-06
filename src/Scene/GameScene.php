<?php

namespace KPHPGame\Scene;

use KPHPGame\AssetsManager;
use KPHPGame\GlobalConfig;
use KPHPGame\Logger;
use KPHPGame\Scene\GameScene\AlertWindowRenderer;
use KPHPGame\Scene\GameScene\AnimatedTile;
use KPHPGame\Scene\GameScene\Colors;
use KPHPGame\Scene\GameScene\Direction;
use KPHPGame\Scene\GameScene\Enemy;
use KPHPGame\Scene\GameScene\IceShard;
use KPHPGame\Scene\GameScene\InfoPanel\Events\AltarVisitedWorldEvent;
use KPHPGame\Scene\GameScene\InfoPanel\Events\AttackWorldEvent;
use KPHPGame\Scene\GameScene\InfoPanel\Events\DieWorldEvent;
use KPHPGame\Scene\GameScene\InfoPanel\Events\LevelUpWorldEvent;
use KPHPGame\Scene\GameScene\InfoPanel\Events\NotEnoughManaWorldEvent;
use KPHPGame\Scene\GameScene\InfoPanel\Events\SpellCastWorldEvent;
use KPHPGame\Scene\GameScene\InfoPanel\Events\StageClearedWorldEvent;
use KPHPGame\Scene\GameScene\InfoPanel\InfoPanelRenderer;
use KPHPGame\Scene\GameScene\MapTile;
use KPHPGame\Scene\GameScene\Player;
use KPHPGame\Scene\GameScene\PlayerAction;
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
    /** @var Enemy[] */
    private $dead_enemies;
    private $player_action = PlayerAction::NONE;
    private SDL $sdl;
    private InfoPanelRenderer $info_panel_renderer;
    private AlertWindowRenderer $text_block_renderer;
    /** @var ffi_cdata<sdl, struct SDL_Rect> */
    private $rect;
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
    /** @var ffi_cdata<sdl, struct SDL_Texture*> */
    private $goblin_texture;
    /** @var ffi_cdata<sdl, struct SDL_Texture*> */
    private $ogre_texture;
    /** @var ffi_cdata<sdl, struct SDL_Texture*> */
    private $boss_texture;
    /** @var ffi_cdata<sdl, struct SDL_Texture*> */
    private $thunder_effect_texture;
    /** @var ffi_cdata<sdl, struct SDL_Texture*> */
    private $fireball_effect_texture;
    /** @var ffi_cdata<sdl, struct SDL_Texture*> */
    private $fireball_trail_effect_texture;
    /** @var ffi_cdata<sdl, struct SDL_Texture*> */
    private $ice_shard_projectile_texture;
    /** @var ffi_cdata<sdl, struct SDL_Texture*> */
    private $ice_shard_effect_texture;
    /** @var ffi_cdata<sdl, struct SDL_Texture*> */
    private $boss_attack_effect_texture;
    /** @var ffi_cdata<sdl_mixer, struct Mix_Chunk*> */
    private $fireball_cast_sound;
    /** @var ffi_cdata<sdl_mixer, struct Mix_Chunk*> */
    private $ice_shard_cast_sound;
    /** @var ffi_cdata<sdl_mixer, struct Mix_Chunk*> */
    private $ice_shard_hit_sound;
    /** @var ffi_cdata<sdl_mixer, struct Mix_Chunk*> */
    private $thunder_cast_sound;
    /** @var ffi_cdata<sdl_mixer, struct Mix_Chunk*> */
    private $level_up_sound;
    private bool $escape = false;
    private bool $defeat = false;
    private bool $is_modal_window = false;
    private int $turn_num = 0;
    /** @var AnimatedTile[] */
    private $animations;
    /** @var IceShard[] */
    private $ice_shards;

    /**
     * @param ffi_cdata<sdl_ttf, struct TTF_Font*> $font
     * @param ffi_cdata<sdl, struct SDL_Renderer*> $sdl_renderer
     */
    public function __construct(SDL $sdl, $font, Colors $colors, $sdl_renderer, Renderer $draw) {
        $this->sdl          = $sdl;
        $this->font         = $font;
        $this->colors       = $colors;
        $this->sdl_renderer = $sdl_renderer;
        $this->draw         = $draw;
        $this->rect         = $this->sdl->newRect();
    }

    public function run() {
        Logger::info('generating world');
        $this->initWorld();
        $this->info_panel_renderer = new InfoPanelRenderer($this->sdl, $this->sdl_renderer, $this->draw, $this->rect, $this->font, $this->colors);
        $this->text_block_renderer = new AlertWindowRenderer($this->sdl, $this->draw, $this->sdl_renderer, $this->rect);

        Logger::info('rendering world');

        $this->loadTextures();
        $this->loadSounds();

        $music = $this->sdl->loadMusic(AssetsManager::sound("music.ogg"));
        if (\FFI::isNull($music)) {
            throw new \RuntimeException($this->sdl->getError());
        }
        if (!$this->sdl->playMusic($music)) {
            throw new \RuntimeException($this->sdl->getError());
        }

        Logger::info('starting GameScene event loop');
        $this->renderAll();

        $event = $this->sdl->newEvent();
        while (true) {
            $this->processEvents($event);
            if ($this->escape) {
                break;
            }

            if ($this->processPlayerAction()) {
                $this->onNewTurn();
                $this->renderAll();
            } elseif (count($this->animations) !== 0 || !$this->info_panel_renderer->is_empty()) {
                $this->renderAll();
            }

            $this->player_action = PlayerAction::NONE;

            $this->sdl->delay(GlobalConfig::FRAME_DELAY);
        }
    }

    private function initWorld(?Player $player = null, int $stage = 1) {
        if ($player === null) {
            $player = new Player();
        }
        $player->mp = $player->max_mp;
        $this->world = new World($player, $stage);
        WorldGenerator::generate($this->world);
        $this->dead_enemies = [];
        $this->animations   = [];
        $this->ice_shards   = [];
        $this->defeat       = false;
        $this->onPlayerMoved();
    }

    /** @return ffi_cdata<sdl, struct SDL_Texture*> */
    private function loadOneTexture(string $asset_path) {
        $surface = $this->sdl->imgLoad($asset_path);
        if (\FFI::isNull($surface)) {
            throw new RuntimeException($this->sdl->getError());
        }
        $texture = $this->sdl->createTextureFromSurface($this->sdl_renderer, $surface);
        if (\FFI::isNull($texture)) {
            throw new RuntimeException($this->sdl->getError());
        }
        $this->sdl->freeSurface($surface);
        return $texture;
    }

    /** @return ffi_cdata<sdl_mixer, struct Mix_Chunk*> */
    private function loadOneSound(string $asset_path) {
        $wav = $this->sdl->loadWAV($asset_path);
        if (\FFI::isNull($wav)) {
            throw new RuntimeException($this->sdl->getError());
        }
        return $wav;
    }

    private function loadSounds(): void {
        $this->fireball_cast_sound = $this->loadOneSound(AssetsManager::sound('fireball_cast.wav'));
        $this->ice_shard_cast_sound = $this->loadOneSound(AssetsManager::sound('ice_shard_cast.wav'));
        $this->ice_shard_hit_sound = $this->loadOneSound(AssetsManager::sound('ice_shard_hit.wav'));
        $this->thunder_cast_sound = $this->loadOneSound(AssetsManager::sound('thunder_cast.wav'));
        $this->level_up_sound = $this->loadOneSound(AssetsManager::sound('level_up.wav'));
    }

    private function loadTextures(): void {
        $this->tileset_texture               = $this->loadOneTexture(AssetsManager::tile("wasteland_compact.png"));
        $this->portal_texture                = $this->loadOneTexture(AssetsManager::tile("portal.png"));
        $this->player_texture                = $this->loadOneTexture(AssetsManager::unit("Player.png"));
        $this->orc_texture                   = $this->loadOneTexture(AssetsManager::unit("Orc.png"));
        $this->goblin_texture                = $this->loadOneTexture(AssetsManager::unit("Goblin.png"));
        $this->ogre_texture                  = $this->loadOneTexture(AssetsManager::unit("Ogre.png"));
        $this->boss_texture                  = $this->loadOneTexture(AssetsManager::unit("BOSS.png"));
        $this->thunder_effect_texture        = $this->loadOneTexture(AssetsManager::magic("thunder_effect.png"));
        $this->fireball_effect_texture       = $this->loadOneTexture(AssetsManager::magic("fireball_effect.png"));
        $this->fireball_trail_effect_texture = $this->loadOneTexture(AssetsManager::magic("fireball_trail_effect.png"));
        $this->ice_shard_projectile_texture  = $this->loadOneTexture(AssetsManager::magic("ice_shard_projectile.png"));
        $this->ice_shard_effect_texture      = $this->loadOneTexture(AssetsManager::magic("ice_shard_effect.png"));
        $this->boss_attack_effect_texture    = $this->loadOneTexture(AssetsManager::magic("boss_attack_effect.png"));
    }

    /** @param ffi_cdata<sdl, union SDL_Event> $event */
    private function processEvents($event): void {
        while ($this->sdl->pollEvent($event)) {
            switch ($event->type) {
                case EventType::QUIT:
                    $this->escape = true;
                    break;

                case EventType::KEYUP:
                    $scancode = $event->key->keysym->scancode;
                    if ($scancode === Scancode::ESCAPE) {
                        $this->escape = true;
                    } elseif ($this->is_modal_window) {
                        if ($scancode === Scancode::Y) {
                            if ($this->defeat) {
                                $this->initWorld();
                                $this->is_modal_window = false;
                            } elseif ($this->world->player->on_portal) {
                                $this->world->player->on_portal = false;
                                $this->is_modal_window          = false;
                                $this->initWorld($this->world->player, $this->world->stage + 1);
                            }
                            $this->renderAll();
                        } elseif ($scancode === Scancode::N) {
                            if ($this->defeat) {
                                $this->escape = true;
                            } elseif ($this->world->player->on_portal) {
                                $this->world->player->on_portal = false;
                                $this->is_modal_window          = false;
                                $this->renderAll();
                            }
                        }
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
                    } elseif ($scancode === Scancode::Q) {
                        $this->player_action = PlayerAction::MAGIC_FIREBALL;
                    } elseif ($scancode === Scancode::W) {
                        $this->player_action = PlayerAction::MAGIC_ICE_SHARDS;
                    } elseif ($scancode === Scancode::E) {
                        $this->player_action = PlayerAction::MAGIC_THUNDER;
                    }
                    break;
            }
        }
    }

    /** @var ffi_cdata<sdl_mixer, struct Mix_Chunk*> $resource */
    private function playSFX($resource) {
        if (!$this->sdl->playChannel(-1, $resource, 0)) {
            if ($this->sdl->getError() === 'No free channels available') {
                Logger::info('trying to play too many sounds at once');
                return;
            }
            throw new \RuntimeException($this->sdl->getError());
        }
    }

    private function castIceShards(): void {
        $this->playSFX($this->ice_shard_cast_sound);

        $world  = $this->world;
        $player = $world->player;

        $tile = $world->getPlayerTile();
        $this->ice_shards[] = new IceShard($tile->pos, $player->direction);

        if ($player->direction === Direction::LEFT || $player->direction === Direction::RIGHT) {
            $tile2 = $world->calculateStepTile($tile, Direction::UP);
            $this->ice_shards[] = new IceShard($tile2->pos, $player->direction);
            $tile3 = $world->calculateStepTile($tile, Direction::DOWN);
            $this->ice_shards[] = new IceShard($tile3->pos, $player->direction);
        } else {
            $tile2 = $world->calculateStepTile($tile, Direction::LEFT);
            $this->ice_shards[] = new IceShard($tile2->pos, $player->direction);
            $tile3 = $world->calculateStepTile($tile, Direction::RIGHT);
            $this->ice_shards[] = new IceShard($tile3->pos, $player->direction);
        }
    }

    private function castFireball(): void {
        $this->playSFX($this->fireball_cast_sound);
        $world  = $this->world;
        $player = $world->player;
        $tile   = $world->getPlayerTile();
        $tile   = $world->calculateStepTile($tile, $player->direction);
        $dist   = 3;
        /** @var Enemy $target */
        $target = null;
        while (true) {
            if ($tile->kind === MapTile::WALL) {
                break;
            }
            foreach ($world->enemies as $enemy) {
                if ($enemy->pos === $tile->pos) {
                    $target = $enemy;
                    break;
                }
            }
            if ($target !== null) {
                break;
            }
            if ($dist === 0) {
                break;
            }

            $a                  = new AnimatedTile();
            $a->frames          = 4;
            $a->ticks_per_frame = 3;
            $a->texture         = $this->fireball_trail_effect_texture;
            $a->pos             = $world->getTile($tile->row, $tile->col)->pos;
            $this->animations[] = $a;

            $tile = $world->calculateStepTile($tile, $player->direction);
            $dist--;
        }

        if ($target !== null) {
            $damage_roll = $world->player->rollSpellDamage($world->player->spellbook->fireball);
            $this->attackEnemy($target, $damage_roll);
        }

        $a                  = new AnimatedTile();
        $a->frames          = 6;
        $a->ticks_per_frame = 4;
        $a->texture         = $this->fireball_effect_texture;
        $a->pos             = $world->getTile($tile->row, $tile->col)->pos;
        $this->animations[] = $a;
    }

    private function castThunder(): void {
        $this->playSFX($this->thunder_cast_sound);

        $world       = $this->world;
        $player_tile = $world->getPlayerTile();
        foreach ($world->enemies as $enemy) {
            $enemy_tile = $world->tiles[$enemy->pos];
            if (abs($enemy_tile->col - $player_tile->col) <= 1 && abs($enemy_tile->row - $player_tile->row) <= 1) {
                $damage_roll = $world->player->rollSpellDamage($world->player->spellbook->thunder);
                $this->attackEnemy($enemy, $damage_roll);
            }
        }
        for ($delta_row = -1; $delta_row <= 1; $delta_row++) {
            for ($delta_col = -1; $delta_col <= 1; $delta_col++) {
                if ($delta_col === 0 && $delta_row === 0) {
                    continue;
                }
                $a                  = new AnimatedTile();
                $a->frames          = 5;
                $a->ticks_per_frame = 5;
                $a->texture         = $this->thunder_effect_texture;
                $a->pos             = $world->getTile($player_tile->row + $delta_row, $player_tile->col + $delta_col)->pos;
                $this->animations[] = $a;
            }
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
        } elseif ($this->player_action === PlayerAction::MAGIC_ICE_SHARDS) {
            $spell = $player->spellbook->ice_shards;
        } elseif ($this->player_action === PlayerAction::MAGIC_THUNDER) {
            $spell = $player->spellbook->thunder;
        }
        if ($spell !== null) {
            if ($player->mp < $spell->mp_cost) {
                $this->info_panel_renderer->add_event(NotEnoughManaWorldEvent::create());
                return false; // Not enough mana
            }
            $player->mp -= $spell->mp_cost;
            $this->info_panel_renderer->add_event(SpellCastWorldEvent::create($spell));
            if ($spell === $player->spellbook->fireball) {
                $this->castFireball();
            } elseif ($spell === $player->spellbook->ice_shards) {
                $this->castIceShards();
            } elseif ($spell === $player->spellbook->thunder) {
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
                if ($this->world->tileIsPortal($new_tile)) {
                    $player->on_portal = true;
                } else {
                    $player->on_portal = false;
                }
                if ($new_tile->kind === MapTile::ALTAR) {
                    $this->onAltarEntered();
                }
                $this->onPlayerMoved();
                return true;
            }
        }

        return false;
    }

    private function onAltarEntered(): void {
        if ($this->world->altar_visited) {
            return;
        }
        $this->world->altar_visited = true;
        $roll = rand(0, 3);
        $player = $this->world->player;
        if ($roll === 1 && $player->hp !== $player->max_hp) {
            $player->hp = $player->max_hp;
            $this->info_panel_renderer->add_event(AltarVisitedWorldEvent::create("heals HP"));
        } else if ($roll === 2 && $player->mp !== $player->max_mp) {
            $player->mp = $player->max_mp;
            $this->info_panel_renderer->add_event(AltarVisitedWorldEvent::create("recovers MP"));
        } else if ($roll === 3 && count($this->world->enemies) !== 0) {
            $this->info_panel_renderer->add_event(AltarVisitedWorldEvent::create("reveals map"));
            foreach ($this->world->tiles as $tile) {
                $tile->revealed = true;
            }
        } else {
            $this->addPlayerExp(20);
            $this->info_panel_renderer->add_event(AltarVisitedWorldEvent::create("grants 20 exp"));
        }
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

    private function attackEnemy(Enemy $target, int $damage) {
        $this->info_panel_renderer->add_event(AttackWorldEvent::create($this->world->player, $target, $damage));
        $target->hp -= $damage;
        $target->triggered = true;
        $this->onEnemyDamageTaken($target);
    }

    private function addPlayerExp(int $exp) {
        $gained_levels = $this->world->player->addExp($exp);
        if ($gained_levels !== 0) {
            $this->playSFX($this->level_up_sound);
        }
        for ($i = 0; $i < $gained_levels; $i++) {
            $this->info_panel_renderer->add_event(LevelUpWorldEvent::create($this->world->player));
        }
    }

    private function onEnemyDamageTaken(Enemy $target) {
        if ($target->hp <= 0) {
            $this->dead_enemies[] = $target;
            $this->info_panel_renderer->add_event(DieWorldEvent::create($target));

            $this->addPlayerExp($target->exp_reward);

            // Remove $target from the world->enemies vector.
            // Use a copying loop in hope that we can preserve world->enemies "vector" property.
            /** @var Enemy[] $alive_enemies */
            $alive_enemies = [];
            foreach ($this->world->enemies as $other) {
                if ($other !== $target) {
                    $alive_enemies[] = $other;
                }
            }
            $this->world->enemies = $alive_enemies;

            if (count($this->world->enemies) === 0) {
                $this->onStageCleared();
            }
        }
    }

    private function onStageCleared(): void {
        $this->info_panel_renderer->add_event(StageClearedWorldEvent::create());
        $this->addPlayerExp(15);
        foreach ($this->world->tiles as $tile) {
            $tile->revealed = true;
        }
    }

    private function attackPlayer(Enemy $attacker) {
        $damage_roll = rand($attacker->min_damage, $attacker->max_damage);
        $this->info_panel_renderer->add_event(AttackWorldEvent::create($attacker, $this->world->player, $damage_roll));
        $this->world->player->hp -= $damage_roll;
        $this->onPlayerDamageTaken();
    }

    private function onPlayerDamageTaken() {
        if ($this->world->player->hp <= 0) {
            $this->info_panel_renderer->add_event(DieWorldEvent::create($this->world->player));
            $this->onDefeat();
        }
    }

    private function onDefeat() {
        $this->defeat = true;
        $this->renderAll();
    }

    private function shardExplode(IceShard $shard) {
        $a                  = new AnimatedTile();
        $a->frames          = 4;
        $a->ticks_per_frame = 5;
        $a->texture         = $this->ice_shard_effect_texture;
        $a->pos             = $shard->pos;
        $this->animations[] = $a;
    }

    private function onNewTurn(): void {
        $this->turn_num++;

        $player_tile = $this->world->getPlayerTile();

        $shard_hit = false;
        foreach ($this->ice_shards as $i => $shard) {
            if ($shard->dist === 0) {
                $this->shardExplode($shard);
                unset($this->ice_shards[$i]);
                continue;
            }
            $shard_tile = $this->world->tiles[$shard->pos];
            if ($shard_tile->kind === MapTile::WALL) {
                $this->shardExplode($shard);
                unset($this->ice_shards[$i]);
                continue;
            }
            /** @var Enemy $shard_target */
            $shard_target = null;
            foreach ($this->world->enemies as $enemy) {
                if ($enemy->pos === $shard_tile->pos) {
                    $shard_target = $enemy;
                    break;
                }
            }
            if ($shard_target !== null) {
                $shard_hit = true;
                $this->shardExplode($shard);
                unset($this->ice_shards[$i]);
                $damage_roll = $this->world->player->rollSpellDamage($this->world->player->spellbook->ice_shards);
                $this->attackEnemy($shard_target, $damage_roll);
                continue;
            }
            $shard->dist--;
            $shard->pos = $this->world->calculateStepTile($this->world->tiles[$shard->pos], $shard->direction)->pos;
        }
        if ($shard_hit) {
            $this->playSFX($this->ice_shard_hit_sound);
        }

        // Enemies try to come closer.
        foreach ($this->world->enemies as $enemy) {
            $enemy_tile = $this->world->tiles[$enemy->pos];

            if (!$enemy->triggered) {
                // 10% chance for a non-triggered enemy to change its location.
                if (rand(0, 99) < 10) {
                    $dir = Direction::random();
                    $new_tile = $this->world->calculateStepTile($enemy_tile, $dir);
                    if ($this->world->tileIsFree($new_tile)) {
                        $enemy->direction = $dir;
                        $enemy->pos       = $new_tile->pos;
                        continue;
                    }
                }
                // 20% chance for a non-triggered enemy to look somewhere else.
                if (rand(0, 99) < 20) {
                    $enemy->direction = Direction::random();
                }
                continue;
            }

            $attack_dist = $enemy->name === '*BOSS*' ? 2 : 1;

            if ($enemy_tile->distTo($player_tile) <= $attack_dist) {
                $this->attackPlayer($enemy);
                if ($enemy->name === '*BOSS*') {
                    $a                  = new AnimatedTile();
                    $a->frames          = 5;
                    $a->ticks_per_frame = 4;
                    $a->texture         = $this->boss_attack_effect_texture;
                    $a->pos             = $player_tile->pos;
                    $this->animations[] = $a;
                }
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

    private function renderIceShards(): void {
        foreach ($this->ice_shards as $shard) {
            $tile = $this->world->tiles[$shard->pos];
            if ($tile->revealed) {
                $this->renderOneTile($this->ice_shard_projectile_texture, $tile->row, $tile->col, $shard->direction);
            }
        }
    }

    private function renderAnimations(): void {
        foreach ($this->animations as $i => $a) {
            if ($a->tick()) {
                unset($this->animations[$i]);
                continue;
            }
            $tile = $this->world->tiles[$a->pos];
            if ($tile->revealed) {
                $this->renderOneTile($a->texture, $tile->row, $tile->col, $a->current_frame);
            }
        }
    }

    private function renderAll(): void {
        $this->draw->clear();
        $this->renderTiles();
        $this->renderPortal();
        $this->renderEnemies();
        $this->renderPlayer();
        $this->info_panel_renderer->render($this->world);
        $this->renderRestartMenu();
        $this->renderIceShards();
        $this->renderAnimations();
        $this->renderOnPortal();
        $this->draw->present();
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

        $tile        = $this->world->getTile($row, $col);
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
        if ($this->world->stage >= GlobalConfig::MAX_STAGE) {
            return;
        }
        $tile = $this->world->tiles[$this->world->portal_pos];
        if ($tile->revealed) {
            $this->renderOneTile($this->portal_texture, $tile->row, $tile->col);
        }
        if ($this->world->getTile($tile->row, $tile->col + 1)->revealed) {
            $this->renderOneTile($this->portal_texture, $tile->row, $tile->col + 1, 1);
        }
        if ($this->world->getTile($tile->row + 1, $tile->col)->revealed) {
            $this->renderOneTile($this->portal_texture, $tile->row + 1, $tile->col, 0, 1);
        }
        if ($this->world->getTile($tile->row + 1, $tile->col + 1)->revealed) {
            $this->renderOneTile($this->portal_texture, $tile->row + 1, $tile->col + 1, 1, 1);
        }
    }

    private function renderTiles(): void {
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
                $tile_rect->x = ($tile->tileset_index * $tile_rect->w);
                $tile_rect->y = $tile_rect->h * 1;
            } elseif ($tile->kind === MapTile::ROCK) {
                $tile_rect->x = ($tile->tileset_index * $tile_rect->w);
                $tile_rect->y = $tile_rect->h * 2;
            } else if ($tile->kind === MapTile::ALTAR) {
                $tile_rect->x = 0;
                $tile_rect->y = $tile_rect->h * 3;
            }
            $tile_pos->x = $tile->col * $tile_rect->w;
            $tile_pos->y = $tile->row * $tile_rect->h;
            if (!$this->draw->copy($this->tileset_texture, \FFI::addr($tile_rect), \FFI::addr($tile_pos))) {
                throw new RuntimeException($this->sdl->getError());
            }
        }
    }

    private function renderPlayer(): void {
        $this->renderUnit($this->world->player);
    }

    private function renderEnemies() {
        foreach ($this->dead_enemies as $unit) {
            if ($this->world->tiles[$unit->pos]->revealed) {
                $this->renderUnit($unit);
            }
        }
        foreach ($this->world->enemies as $unit) {
            if ($this->world->tiles[$unit->pos]->revealed) {
                $this->renderUnit($unit);
            }
        }
    }

    private function renderUnit(Unit $unit) {
        $tile_rect    = $this->sdl->newRect();
        $tile_rect->w = GlobalConfig::TILE_WIDTH;
        $tile_rect->h = 48;

        $tile          = $this->world->tiles[$unit->pos];
        $render_pos    = $this->sdl->newRect();
        $render_pos->h = 48;
        $render_pos->y = ($tile->row * 32) - 16;

        $is_big = false;

        $texture = $this->player_texture;
        if ($unit->name === 'Orc') {
            $texture = $this->orc_texture;
        } elseif ($unit->name === 'Goblin') {
            $texture = $this->goblin_texture;
        } elseif ($unit->name === 'Ogre') {
            $texture = $this->ogre_texture;
            $is_big  = true;
        } else if ($unit->name === '*BOSS*') {
            $texture = $this->boss_texture;
            $is_big  = true;
        }

        $width = GlobalConfig::TILE_WIDTH;
        if ($is_big) {
            $width += 10;
        }
        if ($is_big) {
            $render_pos->x = ($tile->col * 32) - 10;
            $tile_rect->w  = $width;
            $render_pos->w = $width;
        } else {
            $render_pos->x = $tile->col * 32;
            $render_pos->w = 32;
        }
        if ($unit->hp > 0) {
            $tile_rect->x = $width * $unit->direction;
        } else {
            $tile_rect->x = $width * 4;
        }

        if (!$this->draw->copy($texture, \FFI::addr($tile_rect), \FFI::addr($render_pos))) {
            throw new RuntimeException($this->sdl->getError());
        }
    }

    private function renderOnPortal() {
        if ($this->world->player->on_portal) {
            $this->is_modal_window = true;
            $stage                 = $this->world->stage + 1;
            $text                  = ["You find entrance to $stage stage", 'Do you want to go now? [y/n]'];
            $this->text_block_renderer->render($text, $this->font, $this->colors->white, $this->colors->black);
        }
    }

    private function renderRestartMenu() {
        if ($this->defeat) {
            $this->is_modal_window = true;
            $text                  = [
                "Game Over",
                "Restart? [y/n]",
            ];
            $this->text_block_renderer->render($text, $this->font, $this->colors->red, $this->colors->black);
        }
    }
}
