<?php

namespace App\Application\Actions\Admin;

use Psr\Http\Message\ResponseInterface as Response;

class ViewAdminAction extends AdminAction
{
    /**
     * @inheritDoc
     */
    protected function action(): Response
    {
        if (!$this->is_admin()) {
            return $this->refuseNonAdmin();
        }
        return $this->respondWithPage('admin.twig', [
            'page' => $this->mkPage($this->getMessageString('admin'), 0, 1),
            'isadmin' => $this->is_admin()]);
    }
}
