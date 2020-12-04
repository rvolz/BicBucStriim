<?php

namespace Tests\Application\Middleware;

use Aura\Auth\AuthFactory;
use DI\Container;
use Monolog\Logger;
use Slim\Psr7\Response;
use Tests\TestCase as Own_TestCase;
use App\Application\Middleware\AuthMiddleware;

class AuthMiddlewareTest extends Own_TestCase
{
    const DB2 = __DIR__ . '/../../fixtures/data2.db';
    const DATA = __DIR__ . '/../twork/data';
    const DATADB = __DIR__ . '/../twork/data/data.db';

    protected function setUp(): void
    {
        if (file_exists(self::DATA))
            system("rm -rf " . self::DATA);
        mkdir(self::DATA, 0777, true);
        copy(self::DB2, self::DATADB);
    }

    protected function tearDown(): void
    {
        system("rm -rf " . self::DATA);
    }

    public function test_checkRequest4Auth()
    {
        $logger = new Logger("test");
        $pdo = new \PDO('sqlite:'.self::DATADB);
        $container = new Container();
        $instance = new AuthMiddleware($logger, $pdo, $container);
        $request = $this->createRequest("GET", "/",['PHP_AUTH_USER' => 'user', 'PHP_AUTH_PW' => 'password'],[],[]);
        $ad = $instance->checkRequest4Auth($request);
        $this->assertNotNull($ad);
        $this->assertEquals(['user', 'password'], $ad);
        $request = $this->createRequest("GET", "/",['HTTP_AUTHORIZATION' => 'Basic ' . base64_encode('user:password')],[],[]);
        $ad = $instance->checkRequest4Auth($request);
        $this->assertNotNull($ad);
        $this->assertEquals(['user', 'password'], $ad);
        $request = $this->createRequest("GET", "/",[],[],[]);
        $this->assertNull($instance->checkRequest4Auth($request));
        $request = $this->createRequest("GET", "/",['HTTP_AUTHORIZATION' => 'Basic ' . 'bla'],[],[]);
        $ad = $instance->checkRequest4Auth($request);
        $this->assertNull($ad);
    }

    public function test___invoke1()
    {
        $logger = new Logger("test");
        $pdo = new \PDO('sqlite:'.self::DATADB);
        $container = new Container();

        $instance = new AuthMiddleware($logger, $pdo, $container);
        $request = $this->createRequest("GET", "/",['PHP_AUTH_USER' => 'user', 'PHP_AUTH_PW' => 'password'],[],[]);
        $response = new Response();
        $response = $instance->__invoke($request, $response, function($re, $rs){ return $rs; });
        $this->assertEquals(401, $response->getStatusCode());
        $this->assertEquals('', $response->getBody()->getContents());

    }

    public function test___invoke2()
    {
        $logger = new Logger("test");
        $pdo = new \PDO('sqlite:'.self::DATADB);
        $container = new Container();
        $instance = new AuthMiddleware($logger, $pdo, $container);
        $request = $this->createRequest("GET", "/",['PHP_AUTH_USER' => 'admin', 'PHP_AUTH_PW' => 'admin'],[],[]);
        $response = new Response();
        $response = $instance->__invoke($request, $response, function($re, $rs){ return $rs; });
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('', $response->getBody()->getContents());
    }
}
