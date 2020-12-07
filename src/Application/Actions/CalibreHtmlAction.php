<?php


namespace App\Application\Actions;


use App\Domain\BicBucStriim\BicBucStriimRepository;
use App\Domain\BicBucStriim\Configuration;
use App\Domain\BicBucStriim\L10n;
use App\Domain\Calibre\CalibreRepository;
use App\Domain\DomainException\DomainRecordNotFoundException;
use App\Domain\User\User;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface;
use Slim\Exception\HttpBadRequestException;
use Slim\Views\Twig;

abstract class CalibreHtmlAction extends RenderHtmlAction
{

    /**
     * @var CalibreRepository
     */
    protected CalibreRepository $calibre;

    /**
     * @param LoggerInterface $logger
     * @param BicBucStriimRepository $bbs
     * @param CalibreRepository $calibre
     * @param Configuration $config
     * @param User $user
     * @param Twig $twig
     * @param L10n $l10n
     */
    public function __construct(LoggerInterface $logger,
                                BicBucStriimRepository $bbs,
                                CalibreRepository $calibre,
                                Configuration $config,
                                User $user,
                                Twig $twig,
                                L10n $l10n)
    {
        parent::__construct($logger, $bbs, $config, $user, $twig, $l10n);
        $this->calibre = $calibre;
    }
}