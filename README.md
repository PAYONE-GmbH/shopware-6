PAYONE Payment for Shopware 6
=============================

[![CI Status](https://github.com/PAYONE-GmbH/shopware-6/workflows/CI/badge.svg?branch=master)](https://github.com/PAYONE-GmbH/shopware-6/actions)
[![LICENSE](https://img.shields.io/github/license/PAYONE-GmbH/shopware-6.svg)](LICENSE)

This plugin enables merchants to accept payments in a simple and convenient way.
We offer state-of-the art integration of the most used payment methods directly
into your checkout. 

## Open for Feedback

This plugin is a complete rewrite of our Shopware 5 plugin and we'd love to hear your
feedback on it! Drop us a message at shopware@payone.com or open an issue if
you've found a bug.

## Super Bonus for Early Adopters

We'd especially love to hear from you if you plan on going live using the plugin
so we can assist and show some appreciation to the first adopters. :crown:

## Installation

The plugin can easily be integrated via Composer:

```
composer require payone-gmbh/shopware-6
php bin/console plugin:install PayonePayment
php bin/console plugin:activate PayonePayment
php bin/console cache:clear
```

## Not a Customer Yet?

If you don't have a PAYONE Merchant Account yet, get in touch and we'll setup
a test account for you!

## Documentation

Please refer to our extensive online documentation at
https://docs.payone.com/display/public/INT/Shopware+6+Plugin 

## Support and Contact

PAYONE GmbH  
Office Kiel  
Fraunhoferstraße 2–4  
24118 Kiel, Germany  
Phone +49 431 25968-400  
shopware@payone.com

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
