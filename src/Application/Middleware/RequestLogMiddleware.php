<?php

declare(strict_types=1);

namespace App\Application\Middleware;

use Psr\Log\LoggerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface as Middleware;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

class RequestLogMiddleware implements Middleware
{
    /**
     * @var LoggerInterface
     */
    protected LoggerInterface $logger;

    /**
     * Set the LoggerInterface instance.
     *
     * @param LoggerInterface $logger Logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function process(Request $request, RequestHandler $handler): Response
    {
        $this->logger->debug('Request target: ' . $request->getRequestTarget());
        $response = $handler->handle($request);
        $this->logger->debug('Response code: ' . $response->getStatusCode());
        return $response;
    }
}
