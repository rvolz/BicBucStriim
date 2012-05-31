BicBucStriim
============

BicBucStriim streams books, digital books. It fills a gap in the functionality of current NAS devices that provide access to music, videos and photos -- but not books. BicBucStriim fills this gap and provides web-based access to your e-book collection.

BicBucStriim was created when I bought a NAS device (Synology DS 512+) to store my media on it. NAS devices like the Synology DS typically include media servers that publish audio, video, photos, so that you can access your media from all kinds of devices (TV, smart phone, laptop ...) inside the house, which is very convenient. Unfortunately there is nothing like that for e-books. So BicBucStriim was created.

BicBucStriim is a simple PHP application that runs in the Apache/PHP environment provided by the NAS. It assumes that you manage your e-book collection with [Calibre](http://calibre-ebook.com/). The application reads the Calibre data and publishes it in HTML form. To access the e-book catalog you simply point your ebook reader to your NAS, select one of your e-books and download it. 

Alternatives
------------

BicBucStriim is intended for in-house usage, on a NAS or a similar device without much memory and processing power. On a PC you could simply use the Content Server built into Calibre.

If you are looking for a possibility to publish a larger book collection, or want to publish on the Internet, then [calibre2opds](http://calibre2opds.com/) might be interesting. It generates a static HTML catalog of a Calibre library that then can be transferred to a web server.

Status
------

BicBucStriim is still under development. Version 0.7.0 of BicBucStriim: 

* displays the 30 most recent titles on the index page
* provides listing/filtering/searching of book titles
* provides listing/filtering/searching of authors
* provides listing/filtering/searching of tags
* shows detail pages (including download links) for authors and books
* is bilingual, English/German
* is ready for mobile clients (tested with Kindle, iPhone, iPad)
* provides a download protection for books (optional, think "parental control")


Install
-------

The easy way assumes that BicBucStriim lives right below the web root of your device and can be addressed like `http://<your ip>/bbs/`:

* Download a installation archive from [GitHub](https://github.com/rvolz/BicBucStriim/downloads).
* Unarchive the downloaded archive below the web server root of your NAS (e.g. "/volume1/web" on a Synology device)
* Rename the newly created directory (e.g. BicBucStriim-0.5.0.zip) to "bbs".
* NOTE: If you don't want to use the directory name *bbs* simply change the .htaccess file accordingly (the line "RewriteBase /bbs/")
* Make the "logs" directory writeable for all: `chmod ga+w logs`
* Copy the file `config.php.template` to `config.php` and edit the latter:
  * Enter the full path to your Calibre library directory, e.g. `$calibre_dir = '/volume1/books';`
  * Optional: change the value of `$glob_dl_toggle = false;` to `true` if you want a password protection for book downloads
  * Optional: enter a password of your choice in `$glob_dl_password = '7094e7dc2feb759758884333c2f4a6bdc9a16bb2';` if you enabled the global password protection
* BicBucStriim should now be working, start your web browser and navigate to `http://<address of your NAS>/bbs/`


Requirements
------------
* Apache web server with PHP 5.2+ and sqlite3 support

License
-------

BicBucStriim itself is licensed under the MIT license, for the licenses of the libraries used see the file NOTICE.

(The MIT License)

Copyright (c) 2012 Rainer Volz

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the 'Software'), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED 'AS IS', WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.


