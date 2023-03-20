<?php

namespace App\Infrastructure;

/**
 * Class InstUtils - Installation check utilities.
 * @package App\Infrastructure
 */
class InstUtils
{
    /**
     * Try to find the GD version number.
     *
     * @param string $module_info  formatted by phpinfo(8)
     * @return string  version number, "0" if not found
     */
    public static function find_gd_version(string $module_info): string
    {
        if (preg_match("/\bgd\s+version\b[^\d\n\r]+?([\d\.]+)/i", $module_info, $matches)) {
            $gd_version_number = $matches[1];
        } elseif (preg_match("/\bgd\s+headers\s+version\b[^\d\n\r]+?([\d\.]+(\-\w+)?)/i", $module_info, $matches)) {
            $gd_version_number = $matches[1];
        } else {
            $gd_version_number = "0";
        }
        return $gd_version_number;
    }
}
