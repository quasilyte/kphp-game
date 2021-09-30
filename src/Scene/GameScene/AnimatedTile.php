<?php

namespace KPHPGame\Scene\GameScene;

class AnimatedTile {
    public int $frames;
    public int $ticks_per_frame; // Controls the animation speed

    /** @var ffi_cdata<sdl, struct SDL_Texture*> */
    public $texture;
    public int $pos;

    public int $ticker = 0;
    public int $current_frame = 0;

    public function tick(): bool {
        $this->ticker++;
        if ($this->ticker === $this->ticks_per_frame) {
            $this->ticker = 0;
            $this->current_frame++;
            if ($this->current_frame === $this->frames) {
                return true;
            }
        }
        return false;
    }
}
