<?php


namespace App\Application\Actions;


use App\Domain\BicBucStriim\AppConstants;
use App\Domain\BicBucStriim\BicBucStriimRepository;
use App\Domain\BicBucStriim\Configuration;
use App\Domain\BicBucStriim\L10n;
use App\Domain\Calibre\CalibreFilter;
use App\Domain\Calibre\CalibreRepository;
use App\Domain\Opds\OpdsGenerator;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface;

abstract class CalibreOpdsAction extends BasicAction
{
    protected CalibreRepository $calibre;
    protected L10n $l10n;
    protected OpdsGenerator $gen;

    /**
     * @param LoggerInterface $logger
     * @param BicBucStriimRepository $bbs
     * @param CalibreRepository $calibre
     * @param Configuration $config
     * @param L10n $l10n
     */
    public function __construct(LoggerInterface $logger,
                                BicBucStriimRepository $bbs,
                                CalibreRepository $calibre,
                                Configuration $config,
                                L10n $l10n)
    {
        parent::__construct($logger, $bbs, $config);
        $this->calibre = $calibre;
        $this->l10n = $l10n;
        $this->gen = new OpdsGenerator(
            $this->bbs,
            APP_VERSION,
            $this->config[AppConstants::CALIBRE_DIR],
            date(DATE_ATOM, $this->calibre->getModTime()),
            $this->l10n);
    }


    /**
     * Add thumbnail data to the OPDS book
     * @param object $record
     * @return object
     */
    protected function checkThumbnailOpds(object $record): object
    {
        $record['book']->thumbnail = $this->bbs->isTitleThumbnailAvailable($record['book']->id);
        return $record;
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

    /**
     * Return a localized message string for $id.
     *
     * If there is no defined message for $id in the current language the function
     * looks for an alterantive in English. If that also fails an error message
     * is returned.
     *
     * @param string $id message id
     * @return string     localized message string
     */
    protected function getMessageString(string $id): string
    {
        return $this->l10n->message($id);
    }

    /**
     * Create and return the typical (positive) OPDS response
     * @param string $payload OPDS content
     * @param string $type OPDS catalog type
     * @return Response
     */
    protected function respondWithOpds(string $payload, string $type): Response
    {
        $this->response->getBody()->write($payload);
        return $this->response
            ->withHeader('Content-Type', $type)
            ->withStatus(200);
    }
}