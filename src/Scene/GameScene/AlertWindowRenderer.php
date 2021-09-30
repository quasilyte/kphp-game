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
     */
    public function __construct(SDL $sdl, Renderer $draw, $renderer) {
        $this->sdl      = $sdl;
        $this->draw     = $draw;
        $this->renderer = $renderer;
        $this->rect     = $sdl->newRect();
    }

    /**
     * @param string[] $text
     * @param ffi_cdata<sdl_ttf, struct TTF_Font*> $font
     */
    public function render(array $text, $font, Color $text_color, Color $background_color) {
        for ($i = 0; $i < count($text); $i++) {
            $this->part_render($text[$i], $font, $text_color, $i);
        }
    }

    /**
     * @param ffi_cdata<sdl_ttf, struct TTF_Font*> $font
     */
    private function part_render(string $text, $font, Color $text_color, int $line) {
        $text_surface = $this->sdl->renderUTF8Blended($font, $text, $text_color);
        $text_sizes   = $this->sdl->sizeUTF8($font, $text);
        $text_texture = $this->sdl->createTextureFromSurface($this->renderer, $text_surface);
        $this->fill_rect(
            (int)(GlobalConfig::WINDOW_WIDTH / 2 - $text_sizes[0] / 2),
            (int)(GlobalConfig::WINDOW_HEIGHT / 2 + ($line === 0 ? -$text_sizes[1] : $text_sizes[1] * $line)),
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