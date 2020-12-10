<?php


namespace App\Application\Actions\Start;


use App\Application\Actions\CalibreOpdsAction;
use App\Domain\Opds\OpdsGenerator;
use Psr\Http\Message\ResponseInterface as Response;

class ViewOpdsRootCatalogAction extends CalibreOpdsAction
{

    /**
     * Return the OPDS root catalog, the menu.
     * @return Response
     */
    protected function action(): Response
    {
        $cat = $this->gen->rootCatalog();
        return $this->respondWithOpds($cat, OpdsGenerator::OPDS_MIME_NAV);
    }
}