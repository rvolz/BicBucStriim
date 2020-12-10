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
            $this->logger->warning('opdsByTagNamesForInitial: invalid initial ' . $initial);
            throw new DomainRecordNotFoundException($this->request);
        }

        $tags = $this->calibre->tagsNamesForInitial($initial);
        $cat = $this->gen->tagsNamesForInitialCatalog(null, $tags, $initial);
        return $this->respondWithOpds($cat, OpdsGenerator::OPDS_MIME_NAV);
    }
}