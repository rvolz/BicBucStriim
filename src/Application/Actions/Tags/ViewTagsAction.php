<?php


namespace App\Application\Actions\Tags;


use App\Domain\BicBucStriim\AppConstants;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpBadRequestException;

class ViewTagsAction extends TagsAction
{

    /**
     * @inheritDoc
     */
    protected function action(): Response
    {
        $index = 0;
        if ($this->hasQueryParam('index'))
            $index = (int) $this->resolveQueryParam('index');
        // parameter checking
        if ($index < 0) {
            $this->logger->warning('ViewSeriesAction: invalid page id ' . $index);
            throw new HttpBadRequestException($this->request);
        }

        $search = '';
        if ($this->hasQueryParam('search')) {
            $search = $this->resolveQueryParam('search');
            $tl = $this->calibre->tagsSlice($index, $this->config[AppConstants::PAGE_SIZE], trim($search));
        } else
            $tl = $this->calibre->tagsSlice($index, $this->config[AppConstants::PAGE_SIZE]);

        return $this->respondWithPage('tags.html', array(
            'page' => $this->mkPage($this->getMessageString('tags'), 4, 1),
            'url' => 'tags',
            'tags' => $tl['entries'],
            'curpage' => $tl['page'],
            'pages' => $tl['pages'],
            'search' => $search));
    }
}