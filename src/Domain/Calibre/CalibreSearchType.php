<?php


namespace App\Domain\Calibre;


abstract class CalibreSearchType
{
    const Author = 1;
    const AuthorBook = 2;
    const Book = 3;
    const Series = 4;
    const SeriesBook = 5;
    const Tag = 6;
    const TagBook = 7;
    const TimestampOrderedBook = 8;
    const PubDateOrderedBook = 9;
    const LastModifiedOrderedBook = 10;
}