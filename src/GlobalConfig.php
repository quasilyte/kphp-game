<?php

namespace KPHPGame;

class GlobalConfig {
  // TODO: a better game name?
  public const GAME_NAME = "KPHP Game";

  public const WINDOW_WIDTH = 1366;
  public const WINDOW_HEIGHT = 768;

  // Approximately 60fps.
  public const FRAME_DELAY = (int)(1000 / 60);
}