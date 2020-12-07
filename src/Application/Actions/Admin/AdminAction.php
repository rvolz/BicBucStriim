<?php


namespace App\Application\Actions\Admin;


use App\Application\Actions\RenderHtmlAction;
use App\Domain\BicBucStriim\AppConstants;
use App\Domain\BicBucStriim\ConfigMailer;
use App\Domain\BicBucStriim\ConfigTtsOption;
use App\Domain\BicBucStriim\Encryption;
use App\Domain\DomainException\DomainRecordNotFoundException;
use App\Infrastructure\Mail\Mailer;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpBadRequestException;

abstract class AdminAction extends RenderHtmlAction
{

    protected function mkMailers(): array
    {
        $e0 = new ConfigMailer();
        $e0->key = Mailer::SMTP;
        $e0->text = $this->getMessageString('admin_mailer_smtp');
        $e1 = new ConfigMailer();
        $e1->key = Mailer::SENDMAIL;
        $e1->text = $this->getMessageString('admin_mailer_sendmail');
        $e2 = new ConfigMailer();
        $e2->key = Mailer::MAIL;
        $e2->text = $this->getMessageString('admin_mailer_mail');
        return array($e0, $e1, $e2);
    }



    protected function mkTitleTimeSortOptions(): array
    {
        $e0 = new ConfigTtsOption();
        $e0->key = AppConstants::TITLE_TIME_SORT_TIMESTAMP;
        $e0->text = $this->getMessageString('admin_tts_by_timestamp');
        $e1 = new ConfigTtsOption();
        $e1->key = AppConstants::TITLE_TIME_SORT_PUBDATE;
        $e1->text = $this->getMessageString('admin_tts_by_pubdate');
        $e2 = new ConfigTtsOption();
        $e2->key = AppConstants::TITLE_TIME_SORT_LASTMODIFIED;
        $e2->text = $this->getMessageString('admin_tts_by_lastmodified');
        return array($e0, $e1, $e2);
    }

    protected function mkEncryptions(): array
    {
        $e0 = new Encryption();
        $e0->key = 0;
        $e0->text =$this->getMessageString('admin_smtpenc_none');
        $e1 = new Encryption();
        $e1->key = 1;
        $e1->text =$this->getMessageString('admin_smtpenc_ssl');
        $e2 = new Encryption();
        $e2->key = 2;
        $e2->text =$this->getMessageString('admin_smtpenc_tls');
        return array($e0, $e1, $e2);
    }

}