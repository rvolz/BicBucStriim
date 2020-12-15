# Installation check

If an installation or update fails, you can check if the fundamentals for the app are ok: point your browser to `http://<your path to bbs>/installcheck.php`.
This page will is self-explaining, it checks for technical basics and will show you the results using a traffic light metaphor.
Some of the tests executed are:

- the correct PHP version
- necessary PHP modules and supporting libraries
- web server
- necessary permissions

An important part is the _Calibre Libray check_. There you can enter the path to your library and the script will check if the app, or better the web server and PHP, can access it.   
