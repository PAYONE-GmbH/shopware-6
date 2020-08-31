# 1.0.0
- First version of the PAYONE Payment integration for Shopware 6.1

# 1.0.1
Bugfixes

* Corrected encoding of response parameters during PayPal ECS
* Added missing CVC length configs for less used card types
* Fixed a bug where custom fields weren't shown in checkout when using non-default shop languages. We currently support DE and EN and plan to improve this further

Maintenance

* Incorporated best practices for Shopware Code Review

# 1.0.2
Enhancement

* Add new possibility for partial capture and refund

# 1.1.0

New Features

* Partial Captures and Refunds are now possible!
* UI improvements in Settings (these are now collapsible)
* You can now choose the authorization method for every payment method!
* New payment method: iDeal
* New payment method: EPS

Bugfixes

* fixed PayPal ECS button
* fixed translation bugs during checkout
* Better feedback when verifiyng API credentials without active PAYONE payment methods
* fixed a bug that could occur when migrating from 1.0.0 to 1.0.1

Known Incompatibilities

* Backurlhandling in Shopware 6.2 is currently broken. If a customer gets redirected to their favorite payment method but decides to cancel and choose another one, no PAYONE payment methods are available. We're working on a fix to enable correct handling of this use case.

# 2.0.0

New Features

* Enables the switch to PAYONE payment methods after ordering
* Multiple PAYONE transactions can now be handled in the administration per order
* New payment method: Prepayment
* New payment method: Paydirekt

Bugfixes

* fixed a bug where existing settings like payment method assignments could get lost after a plugin update (thx @boxblinkracer)
* fixed wrong sales channel routing of PayPal Express Payments (thx @boxblinkracer)
* various smaller fixes

Maintenance

* Added compatibility for new status transitions in Shopware 6.2
* Shopware 6.2.x support
* We had to drop support for Shopware <6.2.0