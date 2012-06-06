<?php
# Configuration file for BicBucStriim_
# Please don't edit THIS file. Instead copy this file to "config.php" and change 
# the defaults there, if necessary.

#### Per user configuration - please change

# Root directory of the Calibre library. It should contain Calibres catalog file
# "metadata.db".
$calibre_dir = '/tmp/calibre';

# Global download protection toggle. Turn on (set to "true") to require a valid password before 
# books can be downloaded. Initially off.
$glob_dl_toggle = false;
# Password to enter when global download protection is active.
$glob_dl_password = '7094e7dc2feb759758884333c2f4a6bdc9a16bb2';


#### App configuration -- only change if you know what you are doing

# Name of the Calibre library file
$metadata_db = 'metadata.db';
?>
