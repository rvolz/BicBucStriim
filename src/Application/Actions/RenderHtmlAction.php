<?php
declare(strict_types=1);

namespace App\Application\Actions;


use App\Domain\BicBucStriim\AppConstants;
use App\Domain\BicBucStriim\BicBucStriimRepository;
use App\Domain\BicBucStriim\Configuration;
use App\Domain\BicBucStriim\L10n;
use App\Domain\Calibre\CalibreFilter;
use App\Domain\Calibre\CalibreRepository;
use App\Domain\User\User;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface;
use Slim\Views\Twig;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

/**
 * Class RenderAction provides support for renderering HTML responses with Twig.
 * @package App\Application\Actions
 */
abstract class RenderHtmlAction extends BasicAction
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
     * @param Twig $twig
     * @param L10n $l10n
     */
    public function __construct(LoggerInterface $logger,
                                BicBucStriimRepository $bbs,
                                Configuration $config,
                                Twig $twig,
                                L10n $l10n)
    {
        parent::__construct($logger, $bbs, $config);
        $this->twig = $twig;
        $this->l10n = $l10n;
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

    # Utility function to fill the page array
    protected function mkPage($subtitle = '', $menu = 0, $level = 0)
    {
        if ($subtitle == '')
            $title = $this->config[AppConstants::DISPLAY_APP_NAME];
        else
            $title = $this->config[AppConstants::DISPLAY_APP_NAME] . ': ' . $subtitle;

        // TODO mkRootUrl
        // $rot = mkRootUrl();
        // $rot = 'http://localhost:8081';
        $rot = BBS_BASE_PATH;
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
    protected function getMessageString($id): string
    {
        return $this->l10n->message($id);
    }

    /**
     * Create a Calibre Filter according to the current user's
     * language and tag settings.
     * @return CalibreFilter
     */
    protected function getFilter(): CalibreFilter
    {
        $lang = null;
        $tag = null;
        $user = $this->user;
        if (!empty($user->getLanguages()))
            $lang = $this->calibre->getLanguageId($user->getLanguages());
        if (!empty($user->getTags()))
            $tag = $this->calibre->getTagId($user->getTags());
        return new CalibreFilter($lang, $tag);
    }
}
