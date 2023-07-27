<?php

namespace App\Application\Actions\Authors;

use App\Domain\Opds\OpdsGenerator;
use Psr\Http\Message\ResponseInterface as Response;

class ViewOpdsByAuthorInitialsAction extends \App\Application\Actions\CalibreOpdsAction
{
    /**
     * Return a page with author names initials
     */
    protected function action(): Response
    {
        $initials = $this->calibre->authorsInitials();
        $cat = $this->gen->authorsRootCatalog(null, $initials);
        return $this->respondWithOpds($cat, OpdsGenerator::OPDS_MIME_NAV);
    }
}
