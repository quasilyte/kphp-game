<?php

namespace KPHPGame;

class GlobalConfig {
    // TODO: a better game name?
    public const GAME_NAME = "KPHP Game";

    public const WINDOW_WIDTH  = 1366;
    public const WINDOW_HEIGHT = 768;

    public const FONT_SIZE = 20;

    // Approximately 60fps.
    public const FRAME_DELAY = (int)(1000 / 60);

    public const TILE_WIDTH  = 32;
    public const TILE_HEIGHT = 32;
}