# Basic Configuration

The behaviour of the app is partly controlled by the infrastructure it relies on. These are PHP and
the web server. Some configurations cannot be modified by the app. These are explained in this section.

Most configuration settings mentioned here can be specified in two ways:

1. as environment variables (preferred)
2. in a `.env` file, if you can't use environment variables

To use (2) copy the file `public/.env.example` to `public/.env` and modify the variables there.

## Base path

By default the app is configured to live in the root path `/` of your webserver. If this is not the case with your
installation you will have to change the _base path_ configuration. Use the environment variable `BBS_BASE_PATH` 
to define your path.

If you wanted to install the app in the path `/bbs` you would define this as: 

```shell
BBS_BASE_PATH="/bbs"
```

## Log level

The app uses the *info* level to log messages. If you wanted to reduce the amount of log messages further you could
use the log levels *warning* or *error*. Specify this with the environment variable `BBS_LOG_LEVEL`:

```shell
BBS_LOG_LEVEL="warning"
```

## Debug mode

If errors appear and you can't figure out why, you could enable *debug mode* by using the environment variable:

```shell
BBS_DEBUG_MODE=true
```
This will provide more context, it sets the log level to *debug* and turns on the error display. This generates a 
lot of information. So you should turn debug mode off when you are finished.

## PHP

### User session

PHP defines various aspects of a user session in its `php.ini` runtime configuration, see the
[Session Runtime Configuration](https://www.php.net/manual/en/session.configuration.php) for details.

#### Idle time

The app includes a `php.ini` file that modifies the global PHP configuration for user sessions. These configurations
define how long a user session can be inactive before the user has to login again. In many situations the web server/PHP
environment will simply pick up the values defined in the local `php.ini` file. If not, or if you can't live with the
defaults, read on.

The most important configuration for user sessions is `session.gc_maxlifetime`. User sessions will be destroyed
if they are idle longer than the duration configured here, in seconds. After that the user will have to login again.

By default the app uses an idle time of 3600 seconds, 1 hour. If the `session.gc_maxlifetime` has a lower value than that 
you might get login errors and an error message like:

> ... session.gc_maxlifetime 1440 less than idle time 3600

In this situation you have two choices:

1. change the value of `session.gc_maxlifetime` to the app's default
2. adapt the app's default to the PHP configuration

The app provides a local `php.ini` file that follows alternative (1). Many web servers and providers allow to override
the system's `php.ini` with a local one, so this should happen automatically. (However, this feature might have to be 
enabled in your web server config or in your account.)

If you have the necessary access rights you could also modify the system `php.ini`. While this approach would be ok for
single-purpose containers or VMs, it is discouraged in all other cases because it could impact the behaviour of other
PHP applications running on the system.

Alternative (2) would be to modify the app's default. Change the environment variable `BBS_IDLE_TIME` to the value 
defined in `session.gc_maxlifetime`.

Example to use 1440 seconds (the default in many PHP distributions): 

```shell
BBS_IDLE_TIME=1440
```

