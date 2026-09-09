# v1.0.7
## 09/09/2026

1. [](#bugfix)
    * Removed the placeholder `demo` URL from the plugin manifest, so the download page no longer shows a Demo button pointing at a parked domain

# v1.0.6
## 06/23/2026

1. [](#improved)
    * `debug` mode now writes full send diagnostics (composed email, API response, and any warnings) to a dedicated `logs/mailersend.log` instead of `grav.log`, and **still sends the email**. Previously `debug` was a silent dry-run that logged to `grav.log` at error level and never sent.
    * Log MailerSend warnings such as `ALL_SUPPRESSED` / `hard_bounced` — these return a `202` but mean the email was NOT actually delivered, which was previously invisible.
1. [](#new)
    * Added a `dry_run` option (and admin toggle) for true no-send testing, preserving the old "log but don't send" behaviour separately from `debug`.
1. [](#bugfix)
    * Send exceptions are no longer silently swallowed — the real failure reason is now logged to `grav.log` (and `mailersend.log` when debug is on) in addition to being passed to the form validation error.

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
