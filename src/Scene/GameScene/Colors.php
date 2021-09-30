<?php

namespace KPHPGame\Scene\GameScene;

use Quasilyte\SDLite\Color;

class Colors {

    public Color $white;
    public Color $black;
    public Color $red;

    public function __construct() {
        $this->white = new Color(255, 255, 255);
        $this->black = new Color(0, 0, 0);
        $this->red   = new Color(255, 0, 0);
    }
}