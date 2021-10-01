<?php

namespace KPHPGame\Scene\GameScene\InfoPanel;

// Contains the events log scene state.
// Moves, attacks and etc
use KPHPGame\GlobalConfig;
use KPHPGame\Scene\GameScene\Colors;
use KPHPGame\Scene\GameScene\InfoPanel\Events\WorldEvent;
use Quasilyte\SDLite\Renderer;
use Quasilyte\SDLite\SDL;

class WorldEventLogRenderer {

    private const MAX_EVENT_COUNT = 9;

    /** @var string[] */
    private array $events = [];

    private SDL $sdl;

    private Renderer $draw;

    /** @var ffi_cdata<sdl, struct SDL_Renderer*> */
    private $renderer;

    /** @var ffi_cdata<sdl_ttf, struct TTF_Font*> */
    private $font;

    private Colors $colors;

    /** @var ffi_cdata<sdl, struct SDL_Rect> */
    private $rect;

    /**
     * @param ffi_cdata<sdl, struct SDL_Renderer*> $renderer
     * @param ffi_cdata<sdl, struct SDL_Rect> $rect
     * @param ffi_cdata<sdl_ttf, struct TTF_Font*> $font
     */
    public function __construct(SDL $sdl, $renderer, Renderer $draw, $rect, $font, Colors $colors) {
        $this->sdl      = $sdl;
        $this->renderer = $renderer;
        $this->draw     = $draw;
        $this->font     = $font;
        $this->colors   = $colors;
        $this->rect     = $rect;
    }

    public function is_empty(): bool {
        return count($this->events) === 0;
    }

    public function add_event(WorldEvent $event) {
        $event_str = '' . $event;
        array_push($this->events, $event_str);
        if (count($this->events) > self::MAX_EVENT_COUNT) {
            array_shift($this->events);
        }
    }

    public function render() {
        $text_surface  = $this->sdl->renderUTF8Blended($this->font, GlobalConfig::SPLASH_SCREEN_POWERED_BY_KPHP, $this->colors->blue);
        $text_sizes    = $this->sdl->sizeUTF8($this->font, GlobalConfig::SPLASH_SCREEN_POWERED_BY_KPHP);
        $text_texture  = $this->sdl->createTextureFromSurface($this->renderer, $text_surface);
        $this->rect->x = (int)(GlobalConfig::INFO_PANEL_OFFSET + GlobalConfig::INFO_PANEL_CENTER_OFFSET / 2 - $text_sizes[0] / 2);
        $this->rect->y = GlobalConfig::WINDOW_HEIGHT - GlobalConfig::TEXT_MARGIN - $text_sizes[1];
        $this->rect->w = $text_sizes[0];
        $this->rect->h = $text_sizes[1];
        $this->sdl->freeSurface($text_surface);
        if (!$this->draw->copy($text_texture, null, \FFI::addr($this->rect))) {
            throw new \RuntimeException($this->sdl->getError());
        }
        $this->sdl->destroyTexture($text_texture);

        if (empty($this->events)) {
            return;
        }
        $this->rect->x  = GlobalConfig::INFO_PANEL_OFFSET + GlobalConfig::TEXT_MARGIN;
        $this->rect->y = GlobalConfig::INFO_PANEL_Y_SPLIT_OFFSET + GlobalConfig::TEXT_MARGIN;
        for ($i = 0; $i < count($this->events); $i++) {
            $raw_text   = (string)$this->events[$i];
            $split_text = explode("\n", $raw_text);
            for ($x = 0; $x < count($split_text); $x++) {
                $text = (string)$split_text[$x];
                if ($x === 0) {
                    $text = '* ' . $text;
                } elseif (count($split_text) > 1) {
                    $text = '    ' . $text;
                }

                $msg_surface = $this->sdl->renderUTF8Blended($this->font, $text, $this->colors->white);
                if (\FFI::isNull($msg_surface)) {
                    throw new \RuntimeException($this->sdl->getError());
                }
                $msg_sizes = $this->sdl->sizeUTF8($this->font, $text);

                $msg_texture = $this->sdl->createTextureFromSurface($this->renderer, $msg_surface);
                if (\FFI::isNull($msg_texture)) {
                    throw new \RuntimeException($this->sdl->getError());
                }
                $this->rect->w = $msg_sizes[0];
                $this->rect->h = $msg_sizes[1];
                $this->rect->y = $this->rect->y + $this->rect->h + 5;

                $this->sdl->freeSurface($msg_surface);
                if (!$this->draw->copy($msg_texture, null, \FFI::addr($this->rect))) {
                    throw new \RuntimeException($this->sdl->getError());
                }
                $this->sdl->destroyTexture($msg_texture);
            }
        }
    }
}
