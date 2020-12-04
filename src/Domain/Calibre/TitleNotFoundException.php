<?php


namespace App\Domain\Calibre;


use App\Domain\DomainException\DomainRecordNotFoundException;

class TitleNotFoundException extends DomainRecordNotFoundException
{
    public $message = 'The book you requested does not exist.';
}