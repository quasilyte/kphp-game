<?php

namespace KPHPGame;

class GlobalConfig {
    // TODO: a better game name?
    public const GAME_NAME = "KPHP Game";

    public const WINDOW_WIDTH  = 1366;
    public const WINDOW_HEIGHT = 768;

    public const ALERT_WINDOW_WIDTH  = 500;
    public const ALERT_WINDOW_HEIGHT = 200;
    public const ALERT_WINDOW_BORDER = 10;

    public const INFO_PANEL_OFFSET         = 1024;
    public const INFO_PANEL_Y_SPLIT_OFFSET = GlobalConfig::WINDOW_HEIGHT - 512;
    public const INFO_PANEL_WINDOW_BORDER  = 5;
    public const TEXT_MARGIN               = 25;

    public const INFO_PANEL_CENTER_OFFSET = GlobalConfig::WINDOW_WIDTH - GlobalConfig::INFO_PANEL_OFFSET;

    public const FONT_SIZE = 20;

    // Approximately 60fps.
    public const FRAME_DELAY = (int)(1000 / 60);

    public const TILE_WIDTH  = 32;
    public const TILE_HEIGHT = 32;
}
