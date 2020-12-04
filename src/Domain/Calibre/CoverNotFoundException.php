<?php


namespace App\Domain\Calibre;


use App\Domain\DomainException\DomainRecordNotFoundException;

class CoverNotFoundException extends DomainRecordNotFoundException
{
    public $message = 'The book cover you requested does not exist.';
}