<?php

namespace KPHPGame;

class Person {

    private string $name;

    public function __construct(string $name) {
        $this->name = $name;
    }

    public static function create(string $name): Person {
        return new Person($name);
    }

    public function getName(): string {
        return $this->name;
    }
}