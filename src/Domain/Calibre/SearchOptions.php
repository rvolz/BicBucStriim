<?php


namespace App\Domain\Calibre;


class SearchOptions
{
    private const MASK_CASE = 1;
    private const MASK_TRANSLIT = 2;
    private string $searchTerm;
    private bool $respectCase;
    private bool $useAsciiTransliteration;

    /**
     * SearchOptions constructor.
     *
     * @param string $searchTerm
     * @param bool $respectCase
     * @param bool $useAsciiTransliteration
     */
    public function __construct(string $searchTerm, bool $respectCase = false, bool $useAsciiTransliteration = false)
    {
        $this->searchTerm = $searchTerm;
        $this->respectCase = $respectCase;
        $this->useAsciiTransliteration = $useAsciiTransliteration;
    }

    /**
     * Create options object from term and bitmask used in URIs
     * @param string $searchTerm
     * @param int $mask bitmask of search options
     * @return SearchOptions
     */
    public static function fromParams(string $searchTerm, int $mask): SearchOptions
    {
        return new SearchOptions($searchTerm, self::MASK_CASE & $mask, self::MASK_TRANSLIT & $mask);
    }

    /**
     * Return a bit mask of search options
     * @return int
     */
    public function toMask(): int
    {
        $mask = 0;
        if ($this->isRespectCase())
            $mask |= self::MASK_CASE;
        if ($this->isUseAsciiTransliteration())
            $mask |= self::MASK_TRANSLIT;
        return $mask;
    }

    /**
     * @return string
     */
    public function getSearchTerm(): string
    {
        return $this->searchTerm;
    }

    /**
     * @return bool
     */
    public function isRespectCase(): bool
    {
        return $this->respectCase;
    }

    /**
     * @return bool
     */
    public function isUseAsciiTransliteration(): bool
    {
        return $this->useAsciiTransliteration;
    }


}