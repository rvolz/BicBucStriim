<?php


namespace App\Application\Actions\Tags;


use App\Domain\DomainException\DomainRecordNotFoundException;
use App\Domain\Opds\OpdsGenerator;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpBadRequestException;

class ViewOpdsTagsByInitial extends \App\Application\Actions\CalibreOpdsAction
{

    /**
     * @inheritDoc
     */
    protected function action(): Response
    {
        $initials = $this->calibre->tagsInitials();
        $cat = $this->gen->tagsRootCatalog(null, $initials);
        return $this->respondWithOpds($cat, OpdsGenerator::OPDS_MIME_NAV);
    }
}