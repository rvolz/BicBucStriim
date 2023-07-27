<?php

namespace App\Application\Actions\Tags;

use App\Application\Actions\CalibreOpdsAction;
use App\Domain\DomainException\DomainRecordNotFoundException;
use App\Domain\Opds\OpdsGenerator;
use Psr\Http\Message\ResponseInterface as Response;

class ViewOpdsTagNames4Initial extends CalibreOpdsAction
{
    /**
     * @inheritDoc
     */
    protected function action(): Response
    {
        $initial = $this->resolveArg('initial');
        if (!(ctype_upper($initial))) {
            $msg = 'opdsByTagNamesForInitial: invalid initial ' . $initial;
            $this->logger->warning($msg);
            throw new DomainRecordNotFoundException($msg);
        }

        $tags = $this->calibre->tagsNamesForInitial($initial);
        $cat = $this->gen->tagsNamesForInitialCatalog(null, $tags, $initial);
        return $this->respondWithOpds($cat, OpdsGenerator::OPDS_MIME_NAV);
    }
}
