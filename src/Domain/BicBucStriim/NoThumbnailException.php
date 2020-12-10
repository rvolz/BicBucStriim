<?php


namespace App\Domain\BicBucStriim;


use App\Domain\DomainException\DomainRecordNotFoundException;

class NoThumbnailException extends DomainRecordNotFoundException
{
    public $message = 'The book thumbnail you requested does not exist and could not be created.';
}