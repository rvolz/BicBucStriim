<?php


namespace App\Application\Actions\Titles;


use App\Domain\BicBucStriim\AppConstants;
use App\Domain\DomainException\DomainRecordNotFoundException;
use Psr\Http\Message\ResponseInterface as Response;
use Twig\TwigFilter;

class ViewTitleAction extends TitlesAction
{
    /**
     * @inheritDoc
     */
    protected function action(): Response
    {
        $id = (int) $this->resolveArg('id');

        $details = $this->calibre->titleDetails($this->l10n->user_lang, $id);
        if (empty($details)) {
            $this->logger->warning('ViewTitleAction: invalid book id ' . $id);
            throw new DomainRecordNotFoundException();
        }
        // for people trying to circumvent filtering by direct access
        if ($this->title_forbidden($details)) {
            $this->logger->warning("ViewTitleAction: requested book not allowed for user: " . $id);
            throw new DomainRecordNotFoundException();
        }
        // Show ID links only if there are templates and ID data
        $idtemplates = $this->bbs->idTemplates();
        $id_tmpls = array();
        if (count($idtemplates) > 0 && count($details['ids']) > 0) {
            $show_idlinks = true;
            foreach ($idtemplates as $idtemplate) {
                $id_tmpls[$idtemplate->name] = array($idtemplate->val, $idtemplate->label);
            }
        } else
            $show_idlinks = false;
        $kindle_format = ($this->config[AppConstants::KINDLE] == 1) ? $this->calibre->titleGetKindleFormat($id) : null;

        $this->addFsFilter();
        return $this->respondWithPage('title_detail.html',
            array('page' => $this->mkPage($this->getMessageString('book_details'), 2, 2),
                'book' => $details['book'],
                'authors' => $details['authors'],
                'series' => $details['series'],
                'tags' => $details['tags'],
                'formats' => $details['formats'],
                'comment' => $details['comment'],
                'language' => $details['language'],
                'ccs' => (count($details['custom']) > 0 ? $details['custom'] : null),
                'show_idlinks' => $show_idlinks,
                'ids' => $details['ids'],
                'id_templates' => $id_tmpls,
                'kindle_format' => $kindle_format,
                'kindle_from_email' => $this->config[AppConstants::KINDLE_FROM_EMAIL],
                'protect_dl' => false));
    }

    private function addFsFilter(): void
    {
        // Add filter for human-readable file sizes
        $filter = new TwigFilter('hfsize', function ($string) {
            return $this->human_filesize($string);
        });
        $tenv = $this->twig->getEnvironment();
        $tenv->addFilter($filter);
    }
}