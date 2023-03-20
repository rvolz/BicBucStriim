<?php

namespace App\Domain\Calibre;

abstract class CalibreSearchType
{
    public const Author = 1;
    public const AuthorBook = 2;
    public const Book = 3;
    public const Series = 4;
    public const SeriesBook = 5;
    public const Tag = 6;
    public const TagBook = 7;
    public const TimestampOrderedBook = 8;
    public const PubDateOrderedBook = 9;
    public const LastModifiedOrderedBook = 10;
}
