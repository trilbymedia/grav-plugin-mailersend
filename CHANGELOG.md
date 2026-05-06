# v1.0.5
## 05/06/2026

1. [](#bugfix)
    * Stop shipping a bundled `psr/log` 1.x — declare `replace: psr/log: '*'` in `composer.json` so the plugin uses the host Grav's psr/log instead. Fixes a fatal `E_COMPILE_ERROR` (`AbstractLogger::emergency` signature incompatible with `LoggerInterface::emergency`) when the host ships `psr/log` 3.x (Grav 2.0+, or any 1.7 install where another plugin pulls in 3.x).
    * Required to make the 1.7/2.0 compatibility flag added in 1.0.4 actually accurate.

# v1.0.4
## 05/01/2026

1. [](#improved)
    * Added 1.7|2.0 compatibility flags

# v1.0.3
## 12/13/2023

1. [](#new)
   * Added a new 'debug' mode to log rather than send
1. [](#bugfix)
   * Better handling of email format errors

# v1.0.2
## 10/25/2022

1. [](#new)
   * Throw validation exception if API token is missing or invalid
1. [](#bugfix)
   * Fixed an issue with basic array style email addresses

# v1.0.1
## 09/27/2022

1. [](#new)
    * added `onMailerSendVars`, `onMailerSendBeforeSend` and `onMailerSendAfterSend` events
    * added support for **arrays** and also **comma-separated strings** for multiple addresses
    * added support for `Your Name <hello@yoursite.com>`, `{hello@yoursite.com: Your Name}` and `'<hello@yoursite.com>'` email formats

# v1.0.0
## 09/26/2022

1. [](#new)
    * Initial release
