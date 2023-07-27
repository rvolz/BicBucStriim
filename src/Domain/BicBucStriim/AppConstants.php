<?php
/**
 * BicBucStriim
 *
 * Copyright 2012-2015 Rainer Volz
 * Licensed under MIT License, see LICENSE
 *
 */

namespace App\Domain\BicBucStriim;

class AppConstants
{
    # Current DB schema version
    public const DB_SCHEMA_VERSION = 3;

    # URL for version information
    public const VERSION_URL = 'http://projekte.textmulch.de/bicbucstriim/version.json';

    # Cookie name to store Kindle email address
    public const KINDLE_COOKIE = 'kindle_email';
    # Calibre library path
    public const CALIBRE_DIR = 'calibre_dir';
    # BicBucStriim DB version
    public const DB_VERSION = 'db_version';
    # Thumbnail generation method
    public const THUMB_GEN_CLIPPED = 'thumb_gen_clipped';
    # Send-To-Kindle enabled/disabled
    public const KINDLE = 'kindle';
    # Send-To-Kindle from-address
    public const KINDLE_FROM_EMAIL = 'kindle_from_email';
    # Page size for list views, no. of elemens
    public const PAGE_SIZE = 'page_size';
    # Displayed app name for page title
    public const DISPLAY_APP_NAME = 'display_app_name';
    # Kind of mail support used
    public const MAILER = 'mailer';
    # Name of SMTP server, if SMTP mailer is used
    public const SMTP_SERVER = 'smtp_server';
    # Port of SMTP server, if SMTP mailer is used
    public const SMTP_PORT = 'smtp_port';
    # SMTP user name, if SMTP mailer is used
    public const SMTP_USER = 'smtp_user';
    # SMTP password, if SMTP mailer is used
    public const SMTP_PASSWORD = 'smtp_password';
    # SMTP encryption, if SMTP mailer is used
    public const SMTP_ENCRYPTION = 'smtp_encryption';
    # if true then the metadata of books is updated before download
    public const METADATA_UPDATE = 'metadata_update';
    # if true then login is required
    public const LOGIN_REQUIRED = 'must_login';
    # field for time-sorting of books
    public const TITLE_TIME_SORT = 'title_time_sort';
    # Possible values for the above field
    public const TITLE_ALPHA_SORT = 'alphabetic';
    public const TITLE_TIME_SORT_TIMESTAMP = 'timestamp';
    public const TITLE_TIME_SORT_PUBDATE = 'pubdate';
    public const TITLE_TIME_SORT_LASTMODIFIED = 'lastmodified';
    # if true then relative urls will be generated
    public const RELATIVE_URLS = 'relative_urls';

    /* Error codes */

    public const ERROR_BAD_DB = 1; # data.db no access
    public const ERROR_BAD_SCHEMA_VERSION = 2; # data.db schema version incorrect
    public const ERROR_NO_CALIBRE_PATH = 3; # Calibre library path empty
    public const ERROR_BAD_CALIBRE_DB = 4; # Calibre library no access
    public const ERROR_BAD_JSON = 5; # JSON input can't be decoded
    public const ERROR_UNKNOWN_CONFIG = 6; # Unknown configuration key found
    public const ERROR_BAD_INPUT = 7;
    public const ERROR_NO_KINDLEFROM = 8; # No kindle FROM email address
    public const ERROR_BAD_KINDLEFROM = 9; # Bad kindle FROM email address
    public const ERROR_BAD_PAGESIZE = 10; # page size out of bounds
    public const ERROR_BAD_MEDIATYPE = 11; # wrong media type in accept header
}
