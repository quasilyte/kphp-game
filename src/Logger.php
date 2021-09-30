<?php

namespace KPHPGame;

class Logger {
    public static function info(string $message): void {
        fprintf(STDERR, "INFO: %s\n", $message);
    }

    public static function error(string $message): void {
        fprintf(STDERR, "ERROR: %s\n", $message);
    }
}
