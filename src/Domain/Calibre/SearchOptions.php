<?php


namespace App\Domain\Calibre;


class SearchOptions
{
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
    public function __construct(string $searchTerm, bool $respectCase, bool $useAsciiTransliteration)
    {
        $this->searchTerm = $searchTerm;
        $this->respectCase = $respectCase;
        $this->useAsciiTransliteration = $useAsciiTransliteration;
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