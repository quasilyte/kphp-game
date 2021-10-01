<?php

namespace KPHPGame\Scene\GameScene\InfoPanel;

use KPHPGame\GlobalConfig;
use KPHPGame\Scene\GameScene\World;
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
     * @param ffi_cdata<sdl, struct SDL_Rect> $rect
     * @param ffi_cdata<sdl_ttf, struct TTF_Font*> $font
     */
    public function __construct(SDL $sdl, $renderer, Renderer $draw, $rect, $font, Color $text_color) {
        $this->sdl        = $sdl;
        $this->renderer   = $renderer;
        $this->draw       = $draw;
        $this->font       = $font;
        $this->text_color = $text_color;
        $this->rect       = $rect;
    }

    public function render(World $world) {
        $this->render_block('Stage: ' . $world->stage, 0);
        $this->render_block($world->player->name, 1);
        $this->render_block('Level: ' . $world->player->level . '   Exp: ' . $world->player->exp . '/' . $world->player->next_level_exp, 2);
        $this->render_block('HP: ' . ($world->player->hp > 0 ? $world->player->hp : 0) . ($world->player->hp > 10 ? '' : ' ') . '     MP: ' . $world->player->mp, 3);
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
        $x_offset = (int)($y_offset === 0 ? GlobalConfig::INFO_PANEL_OFFSET + GlobalConfig::INFO_PANEL_CENTER_OFFSET / 2 - $text_sizes[0] / 2 : GlobalConfig::INFO_PANEL_OFFSET + GlobalConfig::TEXT_MARGIN);
        $this->fill_rect(
            $x_offset,
            GlobalConfig::TEXT_MARGIN * $y_offset + $text_sizes[1],
            $text_sizes[0],
            $text_sizes[1]
        );
        $this->sdl->freeSurface($text_surface);
        if (!$this->draw->copy($text_texture, null, \FFI::addr($this->rect))) {
            throw new \RuntimeException($this->sdl->getError());
        }
        $this->sdl->destroyTexture($text_texture);
    }
}