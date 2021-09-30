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

        $orc = new Enemy('Orc');
        $orc->pos = $world->player->pos + 4;
        $world->enemies[] = $orc;

        $num_walls = rand(6, 9);
        while ($num_walls > 0) {
            $wall_len = rand(1, 8);
            $dir = rand(0, 3);
            while (true) {
                $wall_col = rand(1, $num_cols - 1);
                $wall_row = rand(1, $num_rows - 1);
                $tile = $world->getTile($wall_row, $wall_col);
                if (!$world->tileIsFree($tile)) {
                    continue; // Try again
                }
                $tile->kind = MapTile::WALL;
                $wall_len--;
                while ($wall_len > 0) {
                    var_dump("hehe");
                    [$row, $col] = $world->calculateStep($tile, $dir);
                    if (!$world->hasTileAt($row, $col)) {
                        break;
                    }
                    $next_tile = $world->getTile($row, $col);
                    $wall_len--;
                    if (!$world->tileIsFree($next_tile)) {
                        continue;
                    }
                    $next_tile->kind = MapTile::WALL;
                    $tile = $next_tile;
                }
                $num_walls--;
                break; // OK
            }
        }
    }
}
