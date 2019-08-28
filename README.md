# PayonePayment

## How to change field styling

Some payment methods like credit cards have custom field styling for the iframe-based input fields. You can change the field styling by creating a new plugin depending on this plugin via composer.

In the new plugin you can then define an overriding javascript plugin like this (example uses `custom/plugins/MyPlugin/src/Resources/storefront/my-payone-payment/my-payone-payment.credit-card.plugin.js`):

```js
import PayonePaymentCreditCard from '../../../../../PayonePayment/src/Resources/storefront/credit-card/payone-payment.credit-card';

export default class MyPayonePaymentCreditCard extends PayonePaymentCreditCard
{
    getFieldStyle() {
        const style = super.getFieldStyle();

        style.push('height: 300px');

        return style;
    }
    getSelectStyle(){
        const style = super.getSelectStyle();

        style.push('background-color: black');

        return style;
    }
}
```

Then you can register the overriding plugin in your `custom/plugins/MyPlugin/src/Resources/storefront/main.js`:

```js
import MyPayonePaymentCreditCard from './my-payone-payment/my-payone-payment.credit-card.plugin';

const PluginManager = window.PluginManager;
PluginManager.override('PayonePaymentCreditCard', MyPayonePaymentCreditCard, '[data-is-payone-credit-card]');

// Necessary for the webpack hot module reloading server
if (module.hot) {
    module.hot.accept();
}
```

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
