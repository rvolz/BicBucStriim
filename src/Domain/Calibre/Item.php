<?php

namespace App\Domain\Calibre;

# Utiliy classes for Calibre DB items

class Item
{
    protected $dynprops = [];

    public function __get(string $name): mixed
    {
        return $this->dynprops[$name] ?? null;
    }

    public function __set(string $name, mixed $value): void
    {
        $this->dynprops[$name] = $value;
    }

    public function __isset(string $name): bool
    {
        return isset($this->dynprops[$name]);
    }

    public function __unset(string $name): void
    {
        if (array_key_exists($name, $this->dynprops)) {
            unset($this->dynprops[$name]);
        }
    }
}
