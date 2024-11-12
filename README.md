PAYONE Payment for Shopware 6
=============================

[![CI Status](https://github.com/PAYONE-GmbH/shopware-6/workflows/CI/badge.svg?branch=master)](https://github.com/PAYONE-GmbH/shopware-6/actions)
[![LICENSE](https://img.shields.io/github/license/PAYONE-GmbH/shopware-6.svg)](LICENSE)

This plugin allows merchants to effortlessly accept payments by integrating the most 
popular payment methods directly into the checkout process, providing a seamless and user-friendly experience.

## Open for Feedback

If you've discovered a bug, please don't hesitate to reach out to us at shopware@payone.com or open an issue.

## Version compatibility

The current plugin is compatible with all versions from 6.6.0 to 6.6.8.2.

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
https://docs.payone.com/integration/plugins/integration-guide-shopware-6

## Support and Contact

PAYONE GmbH  
Office Kiel  
Fraunhoferstraße 2–4  
24118 Kiel, Germany  
Phone +49 431 25968-400  
shopware@payone.com

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
