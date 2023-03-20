<?php
/**
 * This file is part of BicBucStriim, a web frontend for Calibre.
 */

namespace App\Domain\Calibre;

use League\Fractal\TransformerAbstract;

/**
 * Class BookTransformer - produces a view of a book including related information
 * @package BicBucStriim
 */
class BookTransformer extends TransformerAbstract
{
    /**
     * List of resources that could be included
     */
    protected array $availableIncludes = [
        'series',
        'tags',
        'formats',
        'identifiers',
        'comment',
    ];

    /**
     * List of resources that will always be included
     */
    protected array $defaultIncludes = [
        'authors',
    ];


    /**
     * Generate an array with book and related information
     * @param array $details generated fragments from BicBucStriim::titleDetails
     * @return array
     */
    public function transform($details)
    {
        $book = $details['book'];
        if ($book->has_cover) {
            $cover = '/titles/cover/' . $book->id;
        } else {
            $cover = '/img/default_cover.jpg';
        }
        return [
            'id' => (int)$book->id,
            'title' => $book->title,
            'sort' => $book->sort,
            'timestamp' => $book->timestamp,
            'pubdate' => $book->pubdate,
            'lastModified' => $book->last_modified,
            'seriesIndex' => $book->series_index,
            'isbn' => $book->isbn,
            'cover' => $cover,
            'links' => [
                'rel' => 'self',
                'uri' => '/titles/' . $book->id,
            ],
        ];
    }

    /**
     * Include author information, one or more
     * @param $details
     * @return \League\Fractal\Resource\Collection
     */
    public function includeAuthors($details)
    {
        $authors = $details['authors'];
        return $this->collection($authors, new AuthorTransformer(), 'authors');
    }

    /**
     * Include series information, there is at most one series per book
     * @param $details
     * @return \League\Fractal\Resource\Item|null
     */
    public function includeSeries($details)
    {
        $series = $details['series'];
        if (empty($series)) {
            return null;
        }
        return $this->item($series[0], new SeriesTransformer(), 'series');
    }

    /**
     * Include tag information, one or more
     * @param $details
     * @return \League\Fractal\Resource\Collection
     */
    public function includeTags($details)
    {
        $tags = $details['tags'];
        return $this->collection($tags, new TagTransformer(), 'tags');
    }

    /**
     * Include format information, one or more
     * @param $details
     * @return \League\Fractal\Resource\Collection
     */
    public function includeFormats($details)
    {
        $formats = $details['formats'];
        return $this->collection($formats, new FormatTransformer(), 'formats');
    }

    /**
     * Include identifier information, one or more
     * @param $details
     * @return \League\Fractal\Resource\Collection
     */
    public function includeIdentifiers($details)
    {
        $identifiers = $details['ids'];
        return $this->collection($identifiers, new IdentifierTransformer(), 'identifiers');
    }

    /**
     * Include series information, there is at most one series per book
     * @param $details
     * @return \League\Fractal\Resource\Item|null
     */
    public function includeComment($details)
    {
        $comment = $details['comment'];
        if (empty($comment)) {
            return null;
        }
        return $this->item($comment, new commentTransformer(), 'comment');
    }
}
