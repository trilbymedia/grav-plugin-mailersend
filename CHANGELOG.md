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
