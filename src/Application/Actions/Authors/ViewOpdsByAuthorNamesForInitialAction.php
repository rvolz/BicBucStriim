<?php

namespace App\Application\Actions\Authors;

use App\Domain\Opds\OpdsGenerator;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpBadRequestException;

class ViewOpdsByAuthorNamesForInitialAction extends \App\Application\Actions\CalibreOpdsAction
{
    /**
     * Return a catalog with author names for an initial.
     */
    protected function action(): Response
    {
        $initial = $this->resolveArg('initial');
        // parameter checking
        if (!(ctype_upper($initial))) {
            $this->logger->error('opdsByAuthorNamesForInitial: invalid initial ' . $initial);
            throw new HttpBadRequestException($this->request);
        }

        $authors = $this->calibre->authorsNamesForInitial($initial);
        $cat = $this->gen->authorsNamesForInitialCatalog(null, $authors, $initial);
        return $this->respondWithOpds($cat, OpdsGenerator::OPDS_MIME_NAV);
    }
}
