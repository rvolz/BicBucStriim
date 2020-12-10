<?php


namespace App\Application\Actions\Series;


use App\Domain\Opds\OpdsGenerator;
use Psr\Http\Message\ResponseInterface as Response;

class ViewOpdsSeriesByInitial extends \App\Application\Actions\CalibreOpdsAction
{

    /**
     * @inheritDoc
     */
    protected function action(): Response
    {
        $initials = $this->calibre->seriesInitials();
        $cat = $this->gen->seriesRootCatalog(null, $initials);
        return $this->respondWithOpds($cat, OpdsGenerator::OPDS_MIME_NAV);
    }
}