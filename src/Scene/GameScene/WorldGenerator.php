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

        // TODO: random player deployment.
        $world->player->pos = $world->getTile(7, 7)->pos;

        switch ($world->stage) {
            case 1:
                $world->enemies[] = Enemy::newGoblin();
                $world->enemies[] = Enemy::newGoblin();
                $world->enemies[] = Enemy::newGoblin();
                $world->enemies[] = Enemy::newGoblin();
                $world->enemies[] = Enemy::newOrc();
                $world->enemies[] = Enemy::newOrc();
                break;
            case 2:
                $world->enemies[] = Enemy::newGoblin();
                $world->enemies[] = Enemy::newOrc();
                $world->enemies[] = Enemy::newOrc();
                $world->enemies[] = Enemy::newOrc();
                $world->enemies[] = Enemy::newOgre();
                break;
            case 3:
                $world->enemies[] = Enemy::newOrc();
                $world->enemies[] = Enemy::newOgre();
                $world->enemies[] = Enemy::newOgre();
                $world->enemies[] = Enemy::newBoss();
                break;
        }

        foreach ($world->enemies as $enemy) {
            $tile = $world->getTile(rand(0, $world->map_rows - 1), rand(0, $world->map_cols - 1));
            while ($tile->kind !== MapTile::EMPTY) {
                $tile = $world->getTile(rand(0, $world->map_rows - 1), rand(0, $world->map_cols - 1));
            }
            $enemy->pos = $tile->pos;
        }

        $num_walls = rand(6, 9);
        while ($num_walls > 0) {
            $wall_len = rand(1, 8);
            $dir      = rand(0, 3);
            while (true) {
                $wall_col = rand(1, $num_cols - 1);
                $wall_row = rand(1, $num_rows - 1);
                $tile     = $world->getTile($wall_row, $wall_col);
                if (!$world->tileIsFree($tile)) {
                    continue; // Try again
                }
                $tile->kind = MapTile::WALL;
                $wall_len--;
                while ($wall_len > 0) {
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
                    $tile            = $next_tile;
                }
                $num_walls--;
                break; // OK
            }
        }

        $num_rocks = rand(6, 8);
        while ($num_rocks > 0) {
            while (true) {
                $wall_col = rand(1, $num_cols - 1);
                $wall_row = rand(1, $num_rows - 1);
                $tile     = $world->getTile($wall_row, $wall_col);
                if (!$world->tileIsFree($tile)) {
                    continue; // Try again
                }
                $tile->kind = MapTile::ROCK;
                $num_rocks--;
                break; // OK
            }
        }

        // TODO: random deployment of portal.
        // Note: all 4 tiles where we put this portal should be free (MapTile::EMPTY).
        self::deployPortal($world, $world->getTile(1, 1)->pos);
    }

    // Portal occupies 4 tiles, starting from top-left tile at $pos.
    // All tiles become MapTile::PORTAL.
    private static function deployPortal(World $world, int $pos): void {
        if ($world->stage >= 3) {
            return;
        }
        $world->portal_pos = $pos;

        $row = $world->tiles[$pos]->row;
        $col = $world->tiles[$pos]->col;

        $world->getTile($row, $col)->kind         = MapTile::PORTAL;
        $world->getTile($row, $col + 1)->kind     = MapTile::PORTAL;
        $world->getTile($row + 1, $col)->kind     = MapTile::PORTAL;
        $world->getTile($row + 1, $col + 1)->kind = MapTile::PORTAL;
    }
}
