<?php
declare(strict_types=1);

namespace App\Application\Middleware;

use App\Domain\BicBucStriim\L10n;
use App\Domain\User\UserLanguage;
use \Aura\Accept\AcceptFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface as Middleware;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Log\LoggerInterface;

class NegotiationMiddleware  implements Middleware
{

    /**
     * @var LoggerInterface
     */
    protected LoggerInterface $logger;
    /**
     * @var UserLanguage
     */
    protected UserLanguage $userLanguage;
    /**
     * @var L10N
     */
    protected L10n $l10n;

    /**
     * NegotiationMiddleware constructor.
     * @param LoggerInterface $logger
     * @param UserLanguage $userLanguage
     * @param L10n $l10n
     */
    public function __construct(LoggerInterface $logger, UserLanguage $userLanguage, L10n $l10n)
    {
        $this->logger = $logger;
        $this->userLanguage = $userLanguage;
        $this->l10n = $l10n;
    }

    /**
     * {@inheritdoc}
     */
    public function process(Request $request, RequestHandler $handler): Response
    {
        $accept_factory = new AcceptFactory($request->getHeaders());
        $accept = $accept_factory->newInstance();

        # try to find a common language, if there is no fit use english
        $language = $accept->negotiateLanguage($this->userLanguage::DEFINED_LANGUAGES);
        $this->logger->debug('NegotiationMiddleware Found Language ' . var_export($language, true));
        if ($language && !empty($language->getType())) {
            $this->l10n->loadLanguage($language->getType());
        } else {
            $this->l10n->loadLanguage($this->userLanguage::FALLBACK_LANGUAGE);
        }

        // TODO is media negotiation helpful?
        /*
        $media = $accept->negotiateMedia($available);
        if ($media == false || empty($media->getValue())) {
            $data = array(
                'code' => AppConstants::ERROR_BAD_MEDIATYPE,
                'reason' => join(',', $request->getHeader('Accept')));
            return $response
                ->withStatus(406, 'No or wrong media type in Accept header')
                ->withJson($data);
        } else {
            return $next($request, $response);
        }
        */

        return $handler->handle($request);
    }
}