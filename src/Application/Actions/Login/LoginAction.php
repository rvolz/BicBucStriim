<?php
declare(strict_types=1);

namespace App\Application\Actions\Login;


use App\Application\Actions\BasicAction;
use App\Domain\BicBucStriim\AppConstants;
use App\Domain\BicBucStriim\BicBucStriimRepository;
use App\Domain\BicBucStriim\Configuration;
use App\Domain\BicBucStriim\L10n;
use App\Domain\User\User;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface;
use Slim\Views\Twig;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

/**
 * Class LoginAction reimplements some HTML rendering methods because
 * here we can't rely on an existing Calibre instance.
 * @package App\Application\Actions\Login
 */
abstract class LoginAction extends BasicAction
{
    /**
     * @var L10n
     */
    protected L10n $l10n;
    /**
     * @var Twig
     */
    protected Twig $twig;

    /**
     * @param LoggerInterface $logger
     * @param BicBucStriimRepository $bbs
     * @param Configuration $config
     * @param User $user
     * @param L10n $l10n
     * @param Twig $twig
     */
    public function __construct(LoggerInterface $logger,
                                BicBucStriimRepository $bbs,
                                Configuration $config,
                                User $user,
                                L10n $l10n,
                                Twig $twig)
    {
        parent::__construct($logger, $bbs, $config, $user);
        $this->l10n = $l10n;
        $this->twig = $twig;
    }

    /**
     * Create a response by rendering the template.
     * @param string $templateName
     * @param array $data
     * @param int $statusCode
     * @return Response
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    protected function respondWithPage(string $templateName, array $data=array(), int $statusCode=200): Response
    {
        return $this->twig->render($this->response->withStatus($statusCode), $templateName, $data);
    }

    /**
     * Generate the contents of the Twig page array.
     * @param string $subtitle page title
     * @param int $menu should we display a menu?
     * @param int $level level of page (for dialogs)
     * @return array
     */
    protected function mkPage($subtitle = '', $menu = 0, $level = 0): array
    {
        if ($subtitle == '')
            $title = $this->config[AppConstants::DISPLAY_APP_NAME];
        else
            $title = $this->config[AppConstants::DISPLAY_APP_NAME] . ': ' . $subtitle;

        // TODO mkRootUrl
        // $rot = mkRootUrl();
        $rot = 'http://localhost:8081';
        $auth = true;
        $adm = $this->user->getRole() == 1;
        $page = array('title' => $title,
            'rot' => $rot,
            'h1' => $subtitle,
            'version' => APP_VERSION,
            'glob' => array('l10n' => $this->l10n),
            'menu' => $menu,
            'level' => $level,
            'auth' => $auth,
            'admin' => $adm);
        return $page;
    }

    /**
     * Return a localized message string for $id.
     *
     * If there is no defined message for $id in the current language the function
     * looks for an alterantive in English. If that also fails an error message
     * is returned.
     *
     * @param  string $id message id
     * @return string     localized message string
     */
    function getMessageString($id)
    {
        return $this->l10n->message($id);
    }

}