<?php

namespace Tests\Domain\BicBucStriim;

use App\Domain\BicBucStriim\L10n;
use PHPUnit\Framework\TestCase;

class L10nTest extends TestCase
{
    ##
    # Test array functionality
    #
    public function testArrayGet()
    {
        global $langde;

        $l10n = new L10n('de');
        $this->assertEquals($langde['admin'], $l10n->message('admin'));
        $this->assertEquals($langde['admin'], $l10n['admin']);
        $this->assertEquals('Undefined message!', $l10n['bla bla']);
    }
}
