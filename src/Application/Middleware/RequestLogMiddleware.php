<?php
declare(strict_types=1);

namespace App\Application\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

class RequestLogMiddleware
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
     *
     * @param  ServerRequestInterface $request PSR7 request
     * @param  ResponseInterface $response PSR7 response
     * @param  callable $next Next middleware
     *
     * @return ResponseInterface
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next): ResponseInterface
    {
        $this->logger->debug('Request target: ' . $request->getRequestTarget());
        $response = $next($request, $response);
        $this->logger->debug('Response code: ' . $response->getStatusCode());
        return $response;
    }
}