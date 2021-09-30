<?php

namespace KPHPGame\Scene\GameScene;

use KPHPGame\GlobalConfig;
use Quasilyte\SDLite\Color;
use Quasilyte\SDLite\Renderer;
use Quasilyte\SDLite\SDL;

class AlertWindowRenderer {

    private SDL $sdl;
    private Renderer $draw;

    /** @var ffi_cdata<sdl, struct SDL_Renderer*> */
    private $renderer;

    /** @var ffi_cdata<sdl, struct SDL_Rect> */
    private $rect;

    /**
     * @param ffi_cdata<sdl, struct SDL_Renderer*> $renderer
     * @param ffi_cdata<sdl, struct SDL_Rect> $rect
     */
    public function __construct(SDL $sdl, Renderer $draw, $renderer, $rect) {
        $this->sdl      = $sdl;
        $this->draw     = $draw;
        $this->renderer = $renderer;
        $this->rect     = $rect;
    }

    /**
     * @param string[] $text
     * @param ffi_cdata<sdl_ttf, struct TTF_Font*> $font
     */
    public function render(array $text, $font, Color $text_color, Color $background_color) {
        $this->draw->setDrawColor($text_color);
        $this->fill_rect(
            (GlobalConfig::WINDOW_WIDTH >> 1) - (GlobalConfig::ALERT_WINDOW_WIDTH >> 1),
            (GlobalConfig::WINDOW_HEIGHT >> 1) - (GlobalConfig::ALERT_WINDOW_HEIGHT >> 1),
            GlobalConfig::ALERT_WINDOW_WIDTH,
            GlobalConfig::ALERT_WINDOW_HEIGHT
        );
        $this->draw->fillRect($this->rect);
        $this->draw->setDrawColor($background_color);
        $this->fill_rect(
            (GlobalConfig::WINDOW_WIDTH >> 1) - ((GlobalConfig::ALERT_WINDOW_WIDTH - GlobalConfig::ALERT_WINDOW_BORDER) >> 1),
            (GlobalConfig::WINDOW_HEIGHT >> 1) - ((GlobalConfig::ALERT_WINDOW_HEIGHT - GlobalConfig::ALERT_WINDOW_BORDER) >> 1),
            GlobalConfig::ALERT_WINDOW_WIDTH - GlobalConfig::ALERT_WINDOW_BORDER,
            GlobalConfig::ALERT_WINDOW_HEIGHT - GlobalConfig::ALERT_WINDOW_BORDER
        );
        $this->draw->fillRect($this->rect);

        for ($i = 0; $i < count($text); $i++) {
            $this->line_render($text[$i], $font, $text_color, $i);
        }
    }

    /**
     * @param ffi_cdata<sdl_ttf, struct TTF_Font*> $font
     */
    private function line_render(string $text, $font, Color $text_color, int $line) {
        $text_surface = $this->sdl->renderUTF8Blended($font, $text, $text_color);
        $text_sizes   = $this->sdl->sizeUTF8($font, $text);
        $text_texture = $this->sdl->createTextureFromSurface($this->renderer, $text_surface);
        $this->fill_rect(
            (GlobalConfig::WINDOW_WIDTH >> 1) - ($text_sizes[0] >> 1),
            (GlobalConfig::WINDOW_HEIGHT >> 1) + ($line === 0 ? -$text_sizes[1] : $text_sizes[1] * $line),
            $text_sizes[0],
            $text_sizes[1]
        );
        $this->sdl->freeSurface($text_surface);
        if (!$this->draw->copy($text_texture, null, \FFI::addr($this->rect))) {
            throw new \RuntimeException($this->sdl->getError());
        }
        $this->sdl->destroyTexture($text_texture);
    }

    private function fill_rect(int $x, int $y, int $w, int $h) {
        $this->rect->x = $x;
        $this->rect->y = $y;
        $this->rect->w = $w;
        $this->rect->h = $h;
    }
}