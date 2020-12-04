<?php
declare(strict_types=1);

namespace App\Application\Middleware;

use App\Domain\BicBucStriim\AppConstants;
use App\Domain\BicBucStriim\Configuration;
use App\Domain\Calibre\CalibreRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

/**
 * Class CalibreConfigMiddleware
 * @package App\Application\Middleware
 *
 * Aborts requests that need Calibre if no library is defined.
 */
class CalibreConfigMiddleware
{
    /**
     * @var LoggerInterface
     */
    protected LoggerInterface $logger;
    /**
     * @var ?CalibreRepository
     */
    protected ?CalibreRepository $calibre;
    /**
     * @var Configuration
     */
    protected Configuration $config;

    /**
     * Create the instance.
     *
     * @param LoggerInterface $logger Logger
     * @param CalibreRepository|null $calibre Calibre instance
     * @param Configuration $config App configuration
     */
    public function __construct(LoggerInterface $logger, ?CalibreRepository $calibre, Configuration $config)
    {
        $this->logger = $logger;
        $this->calibre = $calibre;
        $this->config = $config;
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
        // TODO check if we have to subtract a base path here
        $path = $request->getUri()->getPath();
        // TODO Move exception path configuration to settings
        if (substr_compare($path, '/login', -1, 6) ||
            substr_compare($path, '/admin', -1, 6)) {
            // No Calibre needed in these parts
            return $next($request, $response);
        } else {
            if (empty($this->config[AppConstants::CALIBRE_DIR])) {
                $this->logger->warning("calibre_config_middleware: No Calibre library path configured: $path");
                //$data = array('code' => AppConstants::ERROR_NO_CALIBRE_PATH, 'reason' => 'No Calibre library path configured.');
                return $response->withStatus(500, 'No Calibre library path configured.'); //->withJson($data);
            } elseif (is_null($this->calibre)) {
                $this->logger->error("calibre_config_middleware: Error while opening Calibre DB: $path");
                //$data = array('code' => AppConstants::ERROR_BAD_CALIBRE_DB, 'reason' => 'Error while opening Calibre DB');
                return $response->withStatus(500, 'Error while opening Calibre DB.'); //->withJson($data);
            } else {
                return $next($request, $response);
            }
        }
    }
}

