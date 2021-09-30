<?php

namespace KPHPGame\Scene\GameScene\Events;

// Contains the events log scene state.
// Moves, attacks and etc
use FFI;
use KPHPGame\GlobalConfig;
use KPHPGame\Logger;
use KPHPGame\Person;
use Quasilyte\SDLite\Color;
use Quasilyte\SDLite\Renderer;
use Quasilyte\SDLite\SDL;

class WorldEventLogger {
    /**
     * @var string[]
     */
    private array $events;

    private SDL $sdl;
    private Renderer $draw;

    /** @var ffi_cdata<sdl, struct SDL_Renderer*> */
    private $renderer;
    /** @var ffi_cdata<sdl_ttf, struct TTF_Font*> */
    private $font;
    private $text_color;

    /**
     * @param ffi_cdata<sdl, struct SDL_Renderer*> $renderer
     * @param ffi_cdata<sdl_ttf, struct TTF_Font*> $font
     */
    public function __construct(SDL $sdl, $renderer, Renderer $draw, $font) {
        $this->sdl        = $sdl;
        $this->renderer   = $renderer;
        $this->draw       = $draw;
        $this->font       = $font;
        $this->text_color = new Color(255, 255, 255);
    }

    /**
     * @return WorldEvent[]
     */
    public static function gen_test_data() {
        $player  = Person::create("player1");
        $monster = Person::create("monster1");
        return [
            MoveWorldEvent::create($player, "up"),
            MoveWorldEvent::create($monster, "down"),
            AttackWorldEvent::create($player, $monster, 5),
            AttackWorldEvent::create($player, $monster, 10),
            DieWorldEvent::create($monster),
        ];
    }

    public function add_event(WorldEvent $event) {
        $event_str = '' . $event;
        Logger::info('Add event: ' . $event_str);

        array_push($this->events, $event_str);
        if (count($this->events) > 5) {
            array_pop($this->events);
        }
    }

    public function render() {
        $text = implode('\n', $this->events);

        $msg_surf  = $this->sdl->renderTextBlended($this->font, $text, $this->text_color);
        $msg_sizes = $this->sdl->sizeUTF8($this->font, $text);

        $msg_rect    = $this->sdl->newRect();
        $msg_rect->x = GlobalConfig::WINDOW_WIDTH - 1024;
        $msg_rect->y = GlobalConfig::WINDOW_HEIGHT - 512;
        $msg_rect->w = $msg_sizes[0]; // controls the width of the rect
        $msg_rect->h = $msg_sizes[1]; // controls the height of the rect
        $msg_text    = $this->sdl->createTextureFromSurface($this->renderer, $msg_surf);
        $this->draw->copy($msg_text, null, FFI::addr($msg_rect));
    }
}
