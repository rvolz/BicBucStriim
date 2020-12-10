<?php


namespace App\Application\Actions\Admin;


use Psr\Http\Message\ResponseInterface as Response;

/**
 * Class ViewConfigurationAction generates the main configuration view.
 * NOTE: mail support via SwiftMailer is currently not supported.
 *
 * @package App\Application\Actions\Admin
 */
class ViewConfigurationAction extends AdminAction
{

    /**
     * @inheritDoc
     */
    protected function action(): Response
    {
        return $this->respondWithPage('admin_configuration.html', array(
            'page' => $this->mkPage($this->getMessageString('admin'), 0, 2),
            //'mailers' => $this->mkMailers(),
            'ttss' => $this->mkTitleTimeSortOptions(),
            'isadmin' => $this->is_admin()));
    }




}