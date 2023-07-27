<?php

namespace App\Application\Actions\Series;

use App\Domain\DomainException\DomainRecordNotFoundException;
use App\Domain\Opds\OpdsGenerator;
use Psr\Http\Message\ResponseInterface as Response;

class ViewOpdsSeriesNames4Initials extends \App\Application\Actions\CalibreOpdsAction
{
    /**
     * @inheritDoc
     */
    protected function action(): Response
    {
        $initial = $this->resolveArg('initial');
        if (!($initial == 'all' || ctype_upper($initial))) {
            $msg = 'opdsBySeriesNamesForInitial: invalid initial ' . $initial;
            $this->logger->warning($msg);
            throw new DomainRecordNotFoundException($msg);
        }

        $tags = $this->calibre->seriesNamesForInitial($initial);
        $cat = $this->gen->seriesNamesForInitialCatalog(null, $tags, $initial);
        return $this->respondWithOpds($cat, OpdsGenerator::OPDS_MIME_NAV);
    }
}
