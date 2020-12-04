<?php

namespace App\Application\Middleware;

use App\Domain\User\User;
use Aura\Auth;
use Aura\Auth\AuthFactory;
use Aura\Auth\Exception;
use ErrorException;
use PDO;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Http\Server\MiddlewareInterface as Middleware;
use Psr\Log\LoggerInterface;
use \Psr\Http\Message\ServerRequestInterface;
use \Psr\Http\Message\ResponseInterface;
use Psr\Container\ContainerInterface;
use GuzzleHttp\Psr7\Response;

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
     */
    public function process(Request $request, RequestHandler $handler): ResponseInterface
    {
        $cookie = $request->getCookieParams();
        $auth_factory = new AuthFactory($cookie);
        $auth = $auth_factory->newInstance();
        $pdo_adapter = $this->createPdoAuthenticator($auth_factory);
        if (is_null($pdo_adapter)) {
            $response = new Response();
            return $response->withStatus(500, 'Cannot authenticate, user db error.');
        }
        $this->try_resume($auth_factory, $pdo_adapter, $auth);
        if ($auth->isValid()) {
            $ud = $auth->getUserData();
            if (!is_array($ud) || !array_key_exists('role', $ud) || !array_key_exists('id', $ud)) {
                $this->logger->error('Login error: invalid user data received. Killing session ...');
                $this->forceLogout($auth_factory, $pdo_adapter, $auth);
                $response = new Response();
                return $response->withStatus(401, 'Invalid authorization data, please login again');
            }
            $this->logger->debug("Authentication valid, resuming for user " . $auth->getUserName());
            $this->container->set(User::class, $ud);
            return $handler->handle($request);
        } else {
            $this->logger->debug("Authentication required, status is ".$auth->getStatus());
            $auth_data = $this->checkRequest4Auth($request);
            if (is_null($auth_data)) {
                $response = new Response();
                return $response->withStatus(401, 'Authentication required');
            }
            $this->logger->debug("Authentication data found");
            $ud = $this->try_login($auth_factory, $pdo_adapter, $auth, $auth_data);
            if (is_null($ud)) {
                $response = new Response();
                return $response->withStatus(401, 'Authentication required');
            } else {
                $this->container->set(User::class, $ud);
                return $handler->handle($request);
            }
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
        return $auth_factory->newPdoAdapter($this->pdo, $hash, $cols, 'user', null);
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
            if (!isset($auth_array1) || strcasecmp('Basic', $auth_array1[0]) != 0)
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
     * Try to resume an existing session. If the session timed out, the resume service
     * forces an automatic logout
     * @param AuthFactory $auth_factory
     * @param Auth\Adapter\PdoAdapter $pdo_adapter
     * @param Auth\Auth $auth
     */
    public function try_resume(AuthFactory $auth_factory, Auth\Adapter\PdoAdapter $pdo_adapter, Auth\Auth $auth): void
    {
        try {
            $resume_service = $auth_factory->newResumeService($pdo_adapter);
            $resume_service->resume($auth);
        } catch (Exception $e) {
            $this->logger->warning('Authentication error, session could not be resumed: ' . var_export(get_class($e), true));
            // Session should be killed by resume service, so nothing else to do
        }
    }

    /**
     * Force a logout
     * @param AuthFactory $auth_factory
     * @param Auth\Adapter\PdoAdapter $pdo_adapter
     * @param Auth\Auth $auth
     */
    public function forceLogout(AuthFactory $auth_factory, Auth\Adapter\PdoAdapter $pdo_adapter, Auth\Auth $auth): void
    {
        try {
            $logout_service = $auth_factory->newLogoutService($pdo_adapter);
            $logout_service->forceLogout($auth);
        } catch (ErrorException $e) {
            $this->logger->error('Authentication error, error while killing session: ' . var_export(get_class($e), true));
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