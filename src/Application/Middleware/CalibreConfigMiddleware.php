<?php
declare(strict_types=1);

namespace App\Application\Middleware;

use App\Domain\BicBucStriim\AppConstants;
use App\Domain\BicBucStriim\Configuration;
use App\Domain\Calibre\CalibreRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface as Middleware;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Log\LoggerInterface;
use GuzzleHttp\Psr7\Response;

/**
 * Class CalibreConfigMiddleware
 * @package App\Application\Middleware
 *
 * Aborts requests that need Calibre if no library is defined.
 */
class CalibreConfigMiddleware implements Middleware
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
     * {@inheritdoc}
     */
    public function process(Request $request, RequestHandler $handler): ResponseInterface
    {
        // TODO check if we have to subtract a base path here
        $path = $request->getUri()->getPath();
        // TODO Move exception path configuration to settings
        if (substr_compare($path, '/login', -1, 6) == 0 ||
            substr_compare($path, '/admin', -1, 6) == 0) {
            // No Calibre needed in these parts
            return $handler->handle($request);
        } else {
            if (empty($this->config[AppConstants::CALIBRE_DIR])) {
                $this->logger->warning("calibre_config_middleware: No Calibre library path configured: $path");
                $response = new Response();
                return $response->withStatus(400, 'No Calibre library path configured. Please configure first.'); //->withJson($data);
            } elseif (is_null($this->calibre)) {
                $this->logger->error("calibre_config_middleware: Error while opening Calibre DB: $path");
                $response = new Response();
                return $response->withStatus(500, 'Error while opening Calibre DB.'); //->withJson($data);
            } else {
                return $handler->handle($request);
            }
        }
    }
}

