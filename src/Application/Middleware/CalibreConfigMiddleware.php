<?php
declare(strict_types=1);

namespace App\Application\Middleware;

use App\Domain\BicBucStriim\AppConstants;
use App\Domain\BicBucStriim\Configuration;
use App\Domain\Calibre\CalibreRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface as Middleware;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Log\LoggerInterface;
use GuzzleHttp\Psr7\Response;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpInternalServerErrorException;

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
     * @throws HttpBadRequestException
     * @throws HttpInternalServerErrorException
     */
    public function process(Request $request, RequestHandler $handler): ResponseInterface
    {
        // TODO check if we have to subtract a base path here
        $path = $request->getUri()->getPath();
        if (!empty(BBS_BASE_PATH)) {
            $path = str_replace(BBS_BASE_PATH, '', $path);
        }
        // TODO Move exception path configuration to settings
        if (substr_compare($path, '/login', 0, 6) == 0 ||
            substr_compare($path, '/admin', 0, 6) == 0) {
            // No Calibre needed in these parts
            return $handler->handle($request);
        } else {
            if (empty($this->config[AppConstants::CALIBRE_DIR])) {
                $this->logger->warning("calibre_config_middleware: No Calibre library path configured: $path");
                return $this->answer($request, 400, 'No Calibre library path configured. Please configure first.');
            } elseif (is_null($this->calibre)) {
                $this->logger->error("calibre_config_middleware: Error while opening Calibre DB: $path");
                return $$this->answer($request, 500, 'Error while opening Calibre DB.'); //->withJson($data);
            } else {
                return $handler->handle($request);
            }
        }
    }

    /**
     * Send a 401 (Unauthorized) answer depending on the access type:
     * - API: send a 401 via the exception
     * - HTML: send a redirect to the login form
     * @param ServerRequestInterface $r
     * @param int $code
     * @param string $msg
     * @return Response
     * @throws HttpBadRequestException
     * @throws HttpInternalServerErrorException
     */
    protected function answer(Request $r, int $code, string $msg): ResponseInterface
    {
        if ($code == 400) {
            if ($this->isApiRequest($r)) {
                $this->logger->debug("CalibreConfigMiddleware::answer: api request %s", [$msg]);

                throw new HttpBadRequestException($r,$msg);
            } else {
                $this->logger->debug("AuthMiddleware::answer: HTML request %s", [$msg]);
                return new Response(302, ['Location' => BBS_BASE_PATH . '/admin/configuration/'], null, '1.1', $msg);
            }
        } else {
            $this->logger->debug("CalibreConfigMiddleware::answer: api request %s", [$msg]);
            throw new HttpInternalServerErrorException($r,$msg);
        }

    }
    /**
     * Find out if the request is an API call, OPDS or JSON. Uses the X-Requested-With or
     * the Content-Type headers to decide that.
     * @param ServerRequestInterface $r
     * @return bool
     */
    protected function isApiRequest(Request $r): bool
    {
        // jQuery Mobile uses Xhr to communicate so we can't use this
        // TODO enable XHR check
        //if ($r->getHeaderLine('X-Requested-With') === 'XMLHttpRequest')
        //    return true;
        $ct = $r->getHeaderLine('Content-Type');
        foreach (['application/xml', 'application/atom+xml', 'application/json'] as $item) {
            if (strstr($ct, $item))
                return true;
        }
        return false;
    }
}

