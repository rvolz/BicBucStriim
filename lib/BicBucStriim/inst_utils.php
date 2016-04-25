<?php
/**
 * BicBucStriim
 *
 * Copyright 2012-2016 Rainer Volz
 * Licensed under MIT License, see LICENSE
 *
 * Installation check utilities.
 *
 */


/**
 * Try to find the GD version number.
 *
 * @param $module_info string formatted by phpinfo(8)
 * @return string  version number, "0" if not found
 */
function find_gd_version($module_info) {
    if (preg_match("/\bgd\s+version\b[^\d\n\r]+?([\d\.]+)/i", $module_info, $matches)) {
        $gd_version_number = $matches[1];
    } elseif (preg_match("/\bgd\s+headers\s+version\b[^\d\n\r]+?([\d\.]+(\-\w+)?)/i", $module_info, $matches)){
        $gd_version_number = $matches[1];
    } else {
        $gd_version_number = "0";
    }
    return $gd_version_number;
}