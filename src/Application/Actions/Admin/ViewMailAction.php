<?php


namespace App\Application\Actions\Admin;


use App\Domain\BicBucStriim\AppConstants;
use App\Domain\BicBucStriim\Encryption;
use Psr\Http\Message\ResponseInterface as Response;

class ViewMailAction extends AdminAction
{

    /**
     * @inheritDoc
     */
    protected function action(): Response
    {
        $mail = array(
            'username' => $this->config[AppConstants::SMTP_USER],
            'password' => $this->config[AppConstants::SMTP_PASSWORD],
            'smtpserver' => $this->config[AppConstants::SMTP_SERVER],
            'smtpport' => $this->config[AppConstants::SMTP_PORT],
            'smtpenc' => $this->config[AppConstants::SMTP_ENCRYPTION]
        );
        return $this->respondWithPage('admin_mail.html', array(
            'page' => $this->mkPage($this->getMessageString('admin_mail'), 0, 2),
            'mail' => $mail,
            'encryptions' => $this->mkEncryptions(),
            'isadmin' => $this->is_admin()));
    }

}