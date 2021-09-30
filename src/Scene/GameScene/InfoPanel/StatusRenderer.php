<?php

namespace KPHPGame\Scene\GameScene\InfoPanel;

use KPHPGame\GlobalConfig;
use KPHPGame\Scene\GameScene\Player;
use Quasilyte\SDLite\Color;
use Quasilyte\SDLite\Renderer;
use Quasilyte\SDLite\SDL;

class StatusRenderer {

    private SDL $sdl;

    private Renderer $draw;

    /** @var ffi_cdata<sdl, struct SDL_Renderer*> */
    private $renderer;

    /** @var ffi_cdata<sdl_ttf, struct TTF_Font*> */
    private $font;

    private Color $text_color;

    /** @var ffi_cdata<sdl, struct SDL_Rect> */
    private $rect;

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
        $this->rect       = $sdl->newRect();
    }

    public function render(Player $player) {
        $this->render_block($player->name, 0);
        $this->render_block('lvl: ' . $player->level . '   exp: ' . $player->exp . '/' . $player->next_level_exp, 1);
        $this->render_block('HP: ' . ($player->hp > 0 ? $player->hp : 0) . '   MP: ' . $player->mp, 2);
    }

    private function fill_rect(int $x, int $y, int $w, int $h) {
        $this->rect->x = $x;
        $this->rect->y = $y;
        $this->rect->w = $w;
        $this->rect->h = $h;
    }
    
    private function render_block(string $text, int $y_offset) {
        $text_surface = $this->sdl->renderUTF8Blended($this->font, $text, $this->text_color);
        $text_sizes   = $this->sdl->sizeUTF8($this->font, $text);
        $text_texture = $this->sdl->createTextureFromSurface($this->renderer, $text_surface);
        $this->fill_rect(GlobalConfig::UI_OFFSET + GlobalConfig::TEXT_MARGIN, GlobalConfig::TEXT_MARGIN * $y_offset + $text_sizes[1], $text_sizes[0], $text_sizes[1]);
        $this->sdl->freeSurface($text_surface);
        if (!$this->draw->copy($text_texture, null, \FFI::addr($this->rect))) {
            throw new \RuntimeException($this->sdl->getError());
        }
        $this->sdl->destroyTexture($text_texture);
    }
}