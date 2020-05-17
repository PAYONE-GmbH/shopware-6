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
