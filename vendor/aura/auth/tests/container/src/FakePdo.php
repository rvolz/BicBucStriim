<?php
namespace Aura\Auth\_Config;

use PDO;

class FakePdo extends PDO
{
    public function __construct()
    {
        // do nothing
    }
}
