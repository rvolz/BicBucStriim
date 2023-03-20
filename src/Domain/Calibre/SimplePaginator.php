<?php
/**
 * This file is part of BicBucStriim, a web frontend for Calibre.
 */

namespace App\Domain\Calibre;

use League\Fractal\Pagination\PaginatorInterface;

/**
 * Class SimplePaginator - A dummy paginator
 * @package BicBucStriim
 */
class SimplePaginator implements PaginatorInterface
{
    private $total = 0;
    private $pageSize = 30;
    private $currentPage = 0;
    private $url;
    private $sortOrder;
    private $search;


    /**
     * Get the current page.
     *
     * @return int
     */
    public function getCurrentPage()
    {
        return $this->currentPage;
    }

    /**
     * Get the last page.
     *
     * @return int
     */
    public function getLastPage()
    {
        $lp = (int)$this->total / $this->pageSize;
        $rest = $this->total % $this->pageSize;
        if ($rest) {
            $lp += 1;
        }
        return $lp;
    }

    /**
     * Get the total.
     *
     * @return int
     */
    public function getTotal()
    {
        return $this->total;
    }

    /**
     * Get the count.
     *
     * @return int
     */
    public function getCount()
    {
        // TODO: Implement getCount() method.
    }

    /**
     * Get the number per page.
     *
     * @return int
     */
    public function getPerPage()
    {
        return $this->pageSize;
    }

    /**
     * Get the url for the given page.
     *
     * @param int $page
     *
     * @return string
     */
    public function getUrl($page)
    {
        if ($this->sortOrder) {
            $sort = "&sort=$this->sortOrder";
        } else {
            $sort = '';
        }
        if ($this->search) {
            $search = "&search=$this->search";
        } else {
            $search = '';
        }
        return $this->url . "?page=$page$sort$search";
    }

    /**
     * @param int $total
     * @return SimplePaginator
     */
    public function setTotal($total)
    {
        $this->total = $total;
        return $this;
    }

    /**
     * @param int $pageSize
     * @return SimplePaginator
     */
    public function setPageSize($pageSize)
    {
        $this->pageSize = $pageSize;
        return $this;
    }

    /**
     * @param int $currentPage
     * @return SimplePaginator
     */
    public function setCurrentPage($currentPage)
    {
        $this->currentPage = $currentPage;
        return $this;
    }

    /**
     * @param mixed $url
     * @return SimplePaginator
     */
    public function setUrl($url)
    {
        $this->url = $url;
        return $this;
    }

    /**
     * @param mixed $sortOrder
     * @return SimplePaginator
     */
    public function setSortOrder($sortOrder)
    {
        $this->sortOrder = $sortOrder;
        return $this;
    }

    /**
     * @param mixed $search
     * @return SimplePaginator
     */
    public function setSearch($search)
    {
        $this->search = $search;
        return $this;
    }
}
