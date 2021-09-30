<?php

namespace KPHPGame\Scene\GameScene;

use KPHPGame\GlobalConfig;

class WorldGenerator {
    public static function generate(World $world): void {
        $num_cols = (int)(1024 / GlobalConfig::TILE_WIDTH);
        $num_rows = (int)(GlobalConfig::WINDOW_HEIGHT / GlobalConfig::TILE_HEIGHT);

        for ($i = 0; $i < $num_rows; $i++) {
            for ($j = 0; $j < $num_cols; $j++) {
                $pos       = $i * $num_cols + $j;
                $tile      = new MapTile();
                $tile->pos = $pos;
                $tile->row = $i;
                $tile->col = $j;

                $tile->tileset_index = rand(0, 3);

                if ($i === 0 || $j === 0 || $i === $num_rows - 1 || $j === $num_cols - 1) {
                    $tile->kind = MapTile::WALL;
                } else {
                    $tile->kind = MapTile::EMPTY;
                }

                $world->tiles[$pos] = $tile;
            }
        }

        $world->map_cols = $num_cols;
        $world->map_rows = $num_rows;

        $world->player->pos = $world->getTile(2, 2)->pos;
    }
}
