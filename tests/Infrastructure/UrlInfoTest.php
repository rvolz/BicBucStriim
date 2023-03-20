<?php

namespace Tests\Infrastructure;

use App\Infrastructure\UrlInfo;
use PHPUnit\Framework\TestCase;

class UrlInfoTest extends TestCase
{
    public function testConstructUrlInfoSimple()
    {
        $gen = new UrlInfo('host.org', null);
        $this->assertTrue($gen->is_valid());
        $this->assertEquals('host.org', $gen->host);
        $this->assertEquals('http', $gen->protocol);

        $gen = new UrlInfo('host.org', 'https');
        $this->assertTrue($gen->is_valid());
        $this->assertEquals('host.org', $gen->host);
        $this->assertEquals('https', $gen->protocol);
    }

    public function testConstructUrlInfoForwarded()
    {
        $input1 = "for=192.0.2.60;proto=http;by=203.0.113.43";
        $input2 = "for=192.0.2.60;proto=https;by=203.0.113.43";
        $input3 = "for=192.0.2.60;by=203.0.113.43;proto=https";

        $gen = new UrlInfo($input1);
        $this->assertTrue($gen->is_valid());
        $this->assertEquals('203.0.113.43', $gen->host);
        $this->assertEquals('http', $gen->protocol);

        $gen = new UrlInfo($input2);
        $this->assertTrue($gen->is_valid());
        $this->assertEquals('203.0.113.43', $gen->host);
        $this->assertEquals('https', $gen->protocol);

        $gen = new UrlInfo($input3);
        $this->assertTrue($gen->is_valid());
        $this->assertEquals('https', $gen->protocol);
    }
}
