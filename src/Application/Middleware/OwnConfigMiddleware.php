<?php
declare(strict_types=1);

namespace App\Application\Middleware;

use App\Domain\BicBucStriim\AppConstants;
use App\Domain\BicBucStriim\BicBucStriimRepository;
use App\Domain\BicBucStriim\Configuration;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

class OwnConfigMiddleware
{
    /**
     * @var LoggerInterface
     */
    protected LoggerInterface $logger;
    /**
     * @var BicBucStriimRepository
     */
    protected BicBucStriimRepository $bbs;
    /**
     * @var Configuration
     */
    protected Configuration $config;

    /**
     * Set the LoggerInterface instance.
     *
     * @param LoggerInterface $logger Logger
     * @param BicBucStriimRepository $bbs BicBucStriim instance
     * @param Configuration $config User configuration
     */
    public function __construct(LoggerInterface $logger, BicBucStriimRepository $bbs, Configuration $config)
    {
        $this->logger = $logger;
        $this->bbs = $bbs;
        $this->config = $config;
    }

    protected function check_config_db(BicBucStriimRepository $bbs, array $currentConfig)
    {
        if ($bbs->dbOk()) {
            $we_have_config = 1;
            if ($currentConfig[AppConstants::DB_VERSION] != AppConstants::DB_SCHEMA_VERSION) {
                $this->logger->warning("own_config_middleware: different db schema detected, should be " .
                    AppConstants::DB_SCHEMA_VERSION . ", is {$currentConfig[DB_VERSION]}. please check");
                $we_have_config = 2;
            }
        } else {
            $we_have_config = 0;
        }
        return $we_have_config;
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
        $config_status = $this->check_config_db($this->bbs, $this->config->getConfig());
        if ($config_status == 0) {
            return $response->withStatus(500, 'No or bad configuration database.');
        } elseif ($config_status == 2) {
            return $response->withStatus(500, 'Different db schema version detected. Please upgrade');
        } else {
            return $next($request, $response);
        }
    }
}

