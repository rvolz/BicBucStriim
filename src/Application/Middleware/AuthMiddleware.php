<?php
declare(strict_types=1);
namespace App\Application\Middleware;


use App\Domain\User\User;
use Aura\Auth;
use Aura\Auth\AuthFactory;
use Aura\Auth\Exception;
use PDO;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Http\Server\MiddlewareInterface as Middleware;
use Psr\Log\LoggerInterface;
use \Psr\Http\Message\ServerRequestInterface;
use \Psr\Http\Message\ResponseInterface;
use Psr\Container\ContainerInterface;
use GuzzleHttp\Psr7\Response;
use Slim\Exception\HttpUnauthorizedException;

class AuthMiddleware  implements Middleware
{

    private LoggerInterface $logger;
    private PDO $pdo;
    private ContainerInterface $container;

    /**
     * Set the LoggerInterface instance.
     *
     * @param LoggerInterface $logger Logger
     * @param PDO $pdo BicBucStriim user database
     * @param ContainerInterface $container
     */
    public function __construct(LoggerInterface $logger, PDO $pdo, ContainerInterface $container)
    {
        $this->logger = $logger;
        $this->pdo = $pdo;
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     * @throws HttpUnauthorizedException
     */
    public function process(Request $request, RequestHandler $handler): ResponseInterface
    {
        $cookie = $request->getCookieParams();
        $auth_factory = new AuthFactory($cookie);
        $auth = $auth_factory->newInstance();
        $pdo_adapter = $this->createPdoAuthenticator($auth_factory);
        $this->try_resume($auth_factory, $pdo_adapter, $auth);
        // TODO check if we have to subtract a base path here
        $path = $request->getUri()->getPath();
        if ($auth->isValid()) {
            $ud = $auth->getUserData();
            if (!is_array($ud) || !array_key_exists('role', $ud) || !array_key_exists('id', $ud)) {
                $this->logger->error('Login error: invalid user data received. Killing session ...');
                $this->logout($auth_factory, $pdo_adapter, $auth, true);
                return $this->answer401($request,'Invalid authorization data, please login again');
            }
            $this->logger->debug("Authentication valid, resuming for user " . $auth->getUserName());
            if (substr_compare($path, '/logout', 0, 7) == 0) {
                $this->logout($auth_factory, $pdo_adapter, $auth, false);
                $this->container->set(User::class, User::emptyUser());
                $request = $request->withAttribute('user', User::emptyUser());
                return $handler->handle($request);
            }
            // TODO find another method for setting or a different interface
            $this->container->set(User::class, User::fromArray($ud, array($auth->getUserName(),'')));
            $request = $request->withAttribute('user', User::fromArray($ud, array($auth->getUserName(),'')));
            return $handler->handle($request);
        } else {
            if (substr_compare($path, '/login', 0, 6) == 0) {
                if ($request->getMethod() == 'POST') {
                    $form_data = $request->getParsedBody();
                    if (isset($form_data['username']) && isset($form_data['password'])) {
                        $auth_data = array($form_data['username'], $form_data['password']);
                    } else {
                        $auth_data = array('', '');
                    }
                    $ud = $this->try_login($auth_factory, $pdo_adapter, $auth, $auth_data);
                    if (is_null($ud)) {
                        $this->container->set(User::class, User::emptyUser());
                    } else {
                        $this->container->set(User::class, User::fromArray($ud, $auth_data));
                        $request = $request->withAttribute('user', User::fromArray($ud, array($auth->getUserName(),'')));
                    }
                    return $handler->handle($request);
                } else {
                    return $handler->handle($request);
                }
            } else {
                $this->logger->debug("Authentication required, status is ".$auth->getStatus());
                $auth_data = $this->checkRequest4Auth($request);
                if (is_null($auth_data))
                    return $this->answer401($request,'Authentication required');
                $this->logger->debug("Authentication data found in headers");
                $ud = $this->try_login($auth_factory, $pdo_adapter, $auth, $auth_data);
                if (is_null($ud)) {
                    return $this->answer401($request, 'Authentication required.');
                } else {
                    $this->container->set(User::class, User::fromArray($ud, $auth_data));
                    $request = $request->withAttribute('user', User::fromArray($ud, array($auth->getUserName(),'')));
                    return $handler->handle($request);
                }
            }
        }
    }


    /**
     * Send a 401 (Unauthorized) answer depending on the access type:
     * - API: send a 401 via the exception
     * - HTML: send a redirect to the login form
     * @param ServerRequestInterface $r
     * @param string $msg
     * @return Response
     * @throws HttpUnauthorizedException
     */
    protected function answer401(Request $r, string $msg): ResponseInterface
    {
        if ($this->isApiRequest($r)) {
            $this->logger->debug("AuthMiddleware::answer401: unauthorized API request");
            //$response =  $response->withStatus(401, 'Authentication required');
            throw new HttpUnauthorizedException($r,$msg);
        } else {
            $this->logger->debug("AuthMiddleware::answer401: unauthorized HTML request");
            return new Response(302, ['Location' => '/login/'], null, '1.1', $msg);
        }

    }


    /**
     * Create a PDO authenticator for the user table. Return null if there were errors, see log.
     * @param AuthFactory $auth_factory
     * @return Auth\Adapter\PdoAdapter
     */
    protected function createPdoAuthenticator(AuthFactory $auth_factory): Auth\Adapter\PdoAdapter
    {
        $hash = new PasswordVerifier(PASSWORD_BCRYPT);
        $cols = array(
            'username', // "AS username" is added by the adapter
            'password', // "AS password" is added by the adapter
            'id',
            'email',
            'languages',
            'tags',
            'role'
        );
        return $auth_factory->newPdoAdapter($this->pdo, $hash, $cols, 'user');
    }


    /**
     * Check "PHP_AUTH_..." and "Authorization" headers for data and return it,
     * or null if nothing was found.
     * @param ServerRequestInterface $request
     * @return array|null
     */
    public function checkRequest4Auth(ServerRequestInterface $request): ?array
    {
        $auth_data = $this->checkPhpAuth($request);
        if (is_null($auth_data)) {
            $auth_data = $this->checkHttpAuth($request);
        }
        return $auth_data;
    }

    /**
     * Look for PHP authorization headers
     * @param ServerRequestInterface $request PSR7 request
     * @return array with username and pasword, or null
     */
    protected function checkPhpAuth(ServerRequestInterface $request): ?array
    {
        $authUser = $request->getHeader('PHP_AUTH_USER');
        $authPass = $request->getHeader('PHP_AUTH_PW');
        if (!empty($authUser) && !empty($authPass))
            return array($authUser[0], $authPass[0]);
        else
            return null;
    }

    /**
     * Look for a HTTP Authorization header and decode it
     * @param ServerRequestInterface $request PSR7 request
     * @return array with username and pasword, or null
     */
    protected function checkHttpAuth(ServerRequestInterface $request): ?array
    {
        $b64auth = $request->getHeader('Authorization');
        if (!empty($b64auth)) {
            $auth_array1 = preg_split('/ /', $b64auth[0]);
            if (strcasecmp('Basic', $auth_array1[0]) != 0)
                return null;
            if (sizeof($auth_array1) != 2 || !isset($auth_array1[1]))
                return null;
            $auth = base64_decode($auth_array1[1]);
            $auth_data = preg_split('/:/', $auth);
            if (sizeof($auth_data) != 2)
                return null;
            else
                return $auth_data;

        } else
            return null;
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
    /**
     * Try to resume an existing session. If the session timed out, the resume service
     * forces an automatic logout
     * @param AuthFactory $auth_factory
     * @param Auth\Adapter\PdoAdapter $pdo_adapter
     * @param Auth\Auth $auth
     */
    public function try_resume(AuthFactory $auth_factory, Auth\Adapter\PdoAdapter $pdo_adapter, Auth\Auth $auth): void
    {
        try {
            $resume_service = $auth_factory->newResumeService($pdo_adapter, BBS_IDLE_TIME);
            $resume_service->resume($auth);
        } catch (Exception $e) {
            $this->logger->warning('Authentication error, session could not be resumed: ' . get_class($e) . ', ' .var_export($e->getMessage()));
            // Session should be killed by resume service, so nothing else to do
        }
    }

    /**
     * Perform a logout
     * @param AuthFactory $auth_factory
     * @param Auth\Adapter\PdoAdapter $pdo_adapter
     * @param Auth\Auth $auth
     * @param bool $force foreed logout if true, else normal logout
     */
    public function logout(AuthFactory $auth_factory, Auth\Adapter\PdoAdapter $pdo_adapter, Auth\Auth $auth, bool $force = false): void
    {
        $logout_service = $auth_factory->newLogoutService($pdo_adapter);
        if ($force) {
            $this->logger->debug("%s: %s, forcing logout", [__FILE__, __FUNCTION__]);
            $logout_service->forceLogout($auth);
        }
        else {
            $this->logger->debug("%s: %s, logout", [__FILE__, __FUNCTION__]);
            $logout_service->logout($auth);
        }
    }

    /**
     * @param AuthFactory $auth_factory
     * @param Auth\Adapter\PdoAdapter $pdo_adapter
     * @param Auth\Auth $auth
     * @param array $auth_data
     * @return array|null
     */
    public function try_login(AuthFactory $auth_factory, Auth\Adapter\PdoAdapter $pdo_adapter, Auth\Auth $auth, array $auth_data): ?array
    {
        try {
            $login_service =  $auth_factory->newLoginService($pdo_adapter);
            $login_service->login($auth, array('username' => $auth_data[0], 'password' => $auth_data[1]));
            if ($auth->isValid()) {
                $this->logger->debug("Login succeeded for user ".$auth->getUserName());
                $ud = $auth->getUserData();
                return $ud;
            } else {
                $this->logger->warning("Login failed for user ".$auth->getUserName());
                return null;
            }
        } catch (Auth\Exception $e) {
            $this->logger->error('Login error: '.var_export(get_class($e),true));
            return null;
        }
    }
}