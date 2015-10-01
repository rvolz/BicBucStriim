<?php
/**
 * BicBucStriim
 *
 * Copyright 2012-2015 Rainer Volz
 * Licensed under MIT License, see LICENSE
 *
 */

# Current DB schema version
const DB_SCHEMA_VERSION = 3;

# URL for version information
const VERSION_URL = 'http://projekte.textmulch.de/bicbucstriim/version.json';

# Cookie name to store Kindle email address
const KINDLE_COOKIE = 'kindle_email';
# Calibre library path
const CALIBRE_DIR = 'calibre_dir';
# BicBucStriim DB version
const DB_VERSION = 'db_version';
# Thumbnail generation method
const THUMB_GEN_CLIPPED = 'thumb_gen_clipped';
# Send-To-Kindle enabled/disabled
const KINDLE = 'kindle';
# Send-To-Kindle from-address
const KINDLE_FROM_EMAIL = 'kindle_from_email';
# Page size for list views, no. of elemens
const PAGE_SIZE = 'page_size';
# Displayed app name for page title
const DISPLAY_APP_NAME = 'display_app_name';
# Kind of mail support used
const MAILER = 'mailer';
# Name of SMTP server, if SMTP mailer is used
const SMTP_SERVER = 'smtp_server';
# Port of SMTP server, if SMTP mailer is used
const SMTP_PORT = 'smtp_port';
# SMTP user name, if SMTP mailer is used
const SMTP_USER = 'smtp_user';
# SMTP password, if SMTP mailer is used
const SMTP_PASSWORD = 'smtp_password';
# SMTP encryption, if SMTP mailer is used
const SMTP_ENCRYPTION = 'smtp_encryption';
# if true then the metadata of books is updated before download
const METADATA_UPDATE = 'metadata_update';
# if true then login is required
const LOGIN_REQUIRED = 'must_login';
# field for time-sorting of books
const TITLE_TIME_SORT = 'title_time_sort';
# Possible values for the above field
const TITLE_TIME_SORT_TIMESTAMP = 'timestamp';
const TITLE_TIME_SORT_PUBDATE = 'pubdate';
const TITLE_TIME_SORT_LASTMODIFIED = 'lastmodified';
?>
