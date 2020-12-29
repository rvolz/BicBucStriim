<?php


namespace App\Application\Actions\Tags;


use App\Application\Actions\ActionPayload;
use Psr\Http\Message\ResponseInterface as Response;

class ViewTagInitialsAction extends TagsAction
{

    /**s
     * Returns a Json document with a sorted list of tag initials
     */
    protected function action(): Response
    {
        $search = $this->checkAndGenSearchOptions();
        $list = $this->calibre->tagsInitials($search);
        $appData = new ActionPayload(200, array_map(function($item) { return $item->initial;}, $list));
        return $this->respondWithData($appData);
    }
}