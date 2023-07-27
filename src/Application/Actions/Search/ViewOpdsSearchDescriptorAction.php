<?php

namespace App\Application\Actions\Search;

use App\Application\Actions\CalibreOpdsAction;
use App\Domain\Opds\OpdsGenerator;
use Psr\Http\Message\ResponseInterface as Response;

class ViewOpdsSearchDescriptorAction extends CalibreOpdsAction
{
    /**
     * @inheritDoc
     */
    protected function action(): Response
    {
        $cat = $this->gen->searchDescriptor(null, '/opds/search/');
        return $this->respondWithOpds($cat, OpdsGenerator::OPENSEARCH_MIME);
    }
}
