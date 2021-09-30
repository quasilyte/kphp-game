<?php

namespace KPHPGame\Scene\GameScene;

class WorldGenerator {
  public static function generate(): World {
    $world = new World();

    $world->tiles = [
      [MapTile::WALL, MapTile::WALL, MapTile::WALL, MapTile::WALL],
      [MapTile::WALL, MapTile::EMPTY, MapTile::EMPTY, MapTile::WALL],
      [MapTile::WALL, MapTile::EMPTY, MapTile::EMPTY, MapTile::WALL],
      [MapTile::WALL, MapTile::WALL, MapTile::WALL, MapTile::WALL],
    ];

    $num_cols = -1;
    foreach ($world->tiles as $row) {
      if ($num_cols === -1) {
        $num_cols = count($row);
        continue;
      }
      if ($num_cols !== count($row)) {
        throw new \RuntimeException("generated bad world: rows have different sizes");
      }
    }
    $world->map_rows = count($world->tiles);
    $world->map_cols = $num_cols;

    return $world;
  }
}
