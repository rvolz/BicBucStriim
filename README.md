# BicBucStriim

BicBucStriim streams books, digital books. It was primarily designed to fill a gap in the functionality of NAS devices that provide access to music, videos and photos -- but not books. BicBucStriim fills this gap and provides web-based access to your e-book collection.

BicBucStriim is a simple PHP application that runs in a PHP environment provided by the NAS. It assumes that you manage your e-book collection with [Calibre](http://calibre-ebook.com/). The application reads the Calibre data and publishes it in HTML form. To access the e-book catalog you simply point your ebook reader to your NAS, select one of your e-books and download it. 

## Notice

For a very long time BicBucStriim supported older PHP versions up to PHP 7.2. The current development version (2.0.0-alpha.X) in this repository breaks with that and requires **PHP 7.4 and higher**. This is mostly due to the requirements of updated dependencies. If you can't update your environment accordingly, you should use the older app versions, the last one being 1.5.0.

The purpose of alpha versions 2.0.0-alpha.X is primarily testing. They are incomplete and contain probably migration bugs. You should not use them to manage your book collection.

- 2.0.0-alpha.1: Contains an updated and restructured backend for PHP 7.4+. The UI will be updated in the following version.
- 2.0.0-alpha.2: Replaced the jQuery Mobile UI with Tailwind CSS.  


## Features & Issues

* shows the most recent titles of your library on the main page
* there are sections for browsing through book titles, authors, tags and series
* individual books can be downloaded
* information about your favourite authors can be added (links, picture)
* global search 
* speaks multiple languages, e.g. Dutch, English, French, German, Galician, Italian
* is ready for mobile clients
* provides login-based access control 
* users can be restricted by book language and/or tag
* provides OPDS book catalogs for reading apps like Stanza
* has an admin GUI for configuration

* no support for Calibre's virtual libraries
* only simple custom columns supported


Install
-------

TBD

For older versions (PHP 7.2): [Download](http://projekte.textmulch.de/bicbucstriim/downloads/) an installation archive. These are stable releases with a reduced footprint, unnecesary files are removed.

Troubleshooting
---------------

If you encounter problems, use the installation test to check your environment. Invoke this test by navigating to `http://<NAS address>/bbs/installcheck.php`. This test checks for certain problems, which users experienced in the past.


Requirements
------------

BicBucStriim publishes Calibre libraries via a web server, so it requires some modules to be pre-installed 
on your machine. The required modules are common ones for NAS, however you should check first if your device supports 
them:

* PHP >= 7.4
* Web server with PHP, including support for mcrypt and sqlite3
* Optional: if PHP module *intl* (phpX-intl) is installed, book languages will be displayed

License
-------

BicBucStriim itself is licensed under the MIT license, for the licenses of the libraries used see the file NOTICE.

(The MIT License)

Copyright (c) 2012-2020 Rainer Volz

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the 'Software'), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED 'AS IS', WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.


