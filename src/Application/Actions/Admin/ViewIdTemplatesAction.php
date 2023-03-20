<?php

namespace App\Application\Actions\Admin;

use App\Application\Actions\CalibreHtmlAction;
use App\Domain\BicBucStriim\IdUrlTemplate;
use Psr\Http\Message\ResponseInterface as Response;

/**
 * Class ViewIdTemplatesAction needs Calibre, therefore it extends from a different Action class.
 * @package App\Application\Actions\Admin
 */
class ViewIdTemplatesAction extends CalibreHtmlAction
{
    /**
     * @inheritDoc
     */
    protected function action(): Response
    {
        $idtemplates = $this->bbs->idTemplates();
        $idtypes = $this->calibre->idTypes();
        $ids2add = [];
        foreach ($idtypes as $idtype) {
            if (empty($idtemplates)) {
                array_push($ids2add, $idtype['type']);
            } else {
                $found = false;
                foreach ($idtemplates as $idtemplate) {
                    if ($idtype['type'] === $idtemplate->name) {
                        $found = true;
                        break;
                    }
                }
                if (!$found) {
                    array_push($ids2add, $idtype['type']);
                }
            }
        }
        foreach ($ids2add as $id2add) {
            $ni = new IdUrlTemplate();
            $ni->name = $id2add;
            $ni->val = '';
            $ni->label = '';
            array_push($idtemplates, $ni);
        }
        $this->logger->debug('admin_get_idtemplates ' . var_export($idtemplates, true));
        return $this->respondWithPage('admin_idtemplates.html', [
            'page' => $this->mkPage($this->getMessageString('admin_idtemplates'), 0, 2),
            'templates' => $idtemplates,
            'isadmin' => $this->is_admin()]);
    }
}
