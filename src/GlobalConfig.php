<?php

namespace KPHPGame;

class GlobalConfig {
    public const GAME_NAME = "KPHP Game";

    public const POWERED_BY                    = "Powered by";
    public const KPHP                          = "KPHP";
    public const SPLASH_SCREEN_POWERED_BY_KPHP = self::POWERED_BY . ' ' . self::KPHP;
    public const SPLASH_SCREEN_TEAM_NAME       = "KPHP Gamedev team";
    public const SPLASH_SCREEN_PRESENTS_NAME   = "presents";
    public const SPLASH_SCREEN_PRESS_ENTER     = "PRESS ENTER";

    public const MAX_STAGE = 3;

    public const WINDOW_WIDTH  = 1366;
    public const WINDOW_HEIGHT = 768;
    public const ALERT_WINDOW_WIDTH  = 500;
    public const ALERT_WINDOW_HEIGHT = 200;
    public const ALERT_WINDOW_BORDER = 10;

    public const INFO_PANEL_OFFSET         = 1024;
    public const INFO_PANEL_Y_SPLIT_OFFSET = GlobalConfig::WINDOW_HEIGHT - 600;
    public const INFO_PANEL_WINDOW_BORDER  = 5;
    public const TEXT_MARGIN               = 25;

    public const INFO_PANEL_CENTER_OFFSET = GlobalConfig::WINDOW_WIDTH - GlobalConfig::INFO_PANEL_OFFSET;

    public const FONT_SIZE     = 20;
    public const BIG_FONT_SIZE = 40;

    // Approximately 60fps (1000 / 60).
    public const FRAME_DELAY = 17;

    public const TILE_WIDTH  = 32;
    public const TILE_HEIGHT = 32;
}
