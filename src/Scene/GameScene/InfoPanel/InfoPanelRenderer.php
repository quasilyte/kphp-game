<?php

namespace KPHPGame\Scene\GameScene\InfoPanel;

use KPHPGame\GlobalConfig;
use KPHPGame\Scene\GameScene\Colors;
use KPHPGame\Scene\GameScene\InfoPanel\Events\WorldEvent;
use KPHPGame\Scene\GameScene\World;
use Quasilyte\SDLite\Renderer;
use Quasilyte\SDLite\SDL;

class InfoPanelRenderer {

    private SDL $sdl;

    private Renderer $draw;

    /** @var ffi_cdata<sdl, struct SDL_Renderer*> */
    private $renderer;

    /** @var ffi_cdata<sdl, struct SDL_Rect> */
    private $rect;

    private Colors $colors;

    private StatusRenderer $statusRenderer;
    private WorldEventLogRenderer $eventLogRenderer;

    /**
     * @param ffi_cdata<sdl, struct SDL_Renderer*> $renderer
     * @param ffi_cdata<sdl_ttf, struct TTF_Font*> $font
     * @param ffi_cdata<sdl, struct SDL_Rect> $rect
     */
    public function __construct(SDL $sdl, $renderer, Renderer $draw, $rect, $font, Colors $colors) {
        $this->sdl      = $sdl;
        $this->renderer = $renderer;
        $this->draw     = $draw;
        $this->colors   = $colors;
        $this->rect     = $rect;

        $this->statusRenderer   = new StatusRenderer($this->sdl, $this->renderer, $draw, $rect, $font, $this->colors->white);
        $this->eventLogRenderer = new WorldEventLogRenderer($this->sdl, $this->renderer, $draw, $rect, $font, $this->colors->white);
    }

    public function add_event(WorldEvent $event) {
        $this->eventLogRenderer->add_event($event);
    }

    public function render(World $world) {
        $this->draw->setDrawColor($this->colors->white);
        $this->rect->x = GlobalConfig::INFO_PANEL_OFFSET;
        $this->rect->y = 0;
        $this->rect->w = GlobalConfig::INFO_PANEL_WINDOW_BORDER;
        $this->rect->h = GlobalConfig::WINDOW_HEIGHT;
        $this->draw->fillRect($this->rect);
        $this->rect->x = GlobalConfig::INFO_PANEL_OFFSET;
        $this->rect->y = GlobalConfig::INFO_PANEL_Y_SPLIT_OFFSET;
        $this->rect->w = GlobalConfig::WINDOW_WIDTH;
        $this->rect->h = GlobalConfig::INFO_PANEL_WINDOW_BORDER;
        $this->draw->fillRect($this->rect);
        $this->draw->setDrawColor($this->colors->black);

        $this->statusRenderer->render($world);
        $this->eventLogRenderer->render();
    }
}