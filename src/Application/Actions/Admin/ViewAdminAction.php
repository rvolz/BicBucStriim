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
        return $this->respondWithPage('admin.html', array(
            'page' => $this->mkPage($this->getMessageString('admin'), 0, 1),
            'isadmin' => $this->is_admin()));
    }
}