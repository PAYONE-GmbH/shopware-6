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

# 2.1.0

New Features

* new payment method: PAYONE safe invoice
* new payment method: Trustly
* added optional submission of order number in the "narrative_text" parameter. This will show the shopware order number on the customer's bank statement or payment info (depending on payment method). If left out, our internal txid is shown.

Bugfix(es)

* fixed payone_allow_refund and payone_allow_capture custom fields to better reflect the current status of an order. This can help when issuing captures and debits via third party systems

Maintenance

* tested with Shopware 6.3.4.1

# 2.2.0

New Features

* compatibility with Shopware 6.4.x

Bugfixes

* fixed API Test for paydirekt
* always provide shipping address for paypal payments
* fixed labels for PAYONE status mapping (finally!)

Maintenance

* tested with Shop version 6.4.1.0
* better error message translations

# 2.3.0

New Features

* new PAYONE permissions management
* status mapping per payment method possible

Bugfixes

* fix for unlock the buy now button
* PayPal Express: telephone number not a mandatory field

Maintenance

* Shopware 6.4.3.1 compatibility
* massive refactoring effort
* Elasticsearch compatibility

# 2.3.1

Bugfix

* backward compatibility to version <6.4.0

# 2.3.2

Bugfix

* transaction status transmission of txstatus "paid"

Maintenance

* Shopware 6.4.4.0 compatibility

Notice

* We're dropping compatibility with Shopware 6.2.* in a future release of this plugin

# 2.4.0

New Features

* New payment method: Apple Pay
* Enable forwarding of transaction status to third-party systems

Bugfixes

* Various fixes for different languages
* Fixed the bug for Prepayment method

Maintenance

* Compatibility with 0€ orders
* tested with 6.4.1

# 2.4.1

New Features

* Deactivate payone payment methods on zero amount carts
* Add apple-pay
* Add payment method description

Bugfixes

* Fix config loading error
* Fix storefront requests
* Fix missing service
* Fix missing customer parameter on prepayment

Maintenance

* Fix backwards compatibility
* Removed cardtype type discover
* Add dependency to GitHub pipeline
* Add fix for Version 6.4.5.0

# 3.0.0

Bugfixes

* Customer deletion now possible
* Refund only from not yet refunded items possible
* Adjustment missing dependencies when installing via store

Maintenance

* fix compatibility 6.4.7.0
* drop support for 6.2

# 3.1.0

New Features

* New payment method: Open Invoice
* Add checkbox for credit card payments to save or remove payment data

Bugfixes

* remove capturemode param if completed
* update ZeroAmountCartValidator
* always set data protection check

Maintenance
* include line items with no tax for capture
* add shipping information to Unzer

Tested with:
Shopware 6.4.10.0

# 3.2.0

New Features

* New payment method: Bancontact
* Added bankgrouptypes for iDEAL
* Add scheduled task to clean up redirect table
* Add due date for invoice on standard invoice

Bugfixes

* added shipping costs to line items
* fixed removal of secure invoice

Maintenance

* Changed renaming of payment methods
* Changed PAYONE Logo
* tested with 6.4.12

# 3.3.0

New Features

* New payment method: Ratepay Open Invoice
* New payment method: Ratepay Direct Debit
* New payment method: Ratepay Installments

Maintenance

* Integrate a sales landingpage in backend
* Tested with version 6.4.14

# 4.0.0

New Features

* Shopware 6.3 support removed
* General code optimizations implemented

* Important change: The transaction data of PAYONE payments was
previously always stored in the additional fields of the orders.
Since the additional fields are stored as JSON in the database,
searching the transaction data was not very performant for large
amounts of data. Therefore, an entity extension was set up for
the transaction data so that the data is stored in an extra database
table that can be searched much more performantly. During the plugin
update, the old additional fields are migrated to the entity extension
and then the additional fields are deleted. If you have used our
additional fields in your own code or for example in the synchronization
to external systems, you have to adapt this to the new entity extension.

Bugfix

* Remove deletion of saved credit cards

Maintenance

* Remove BIC from debit
* Tested with 6.4.16

### Read transaction data ###
```
$criteria = (new Criteria())
->addAssociation(PayonePaymentOrderTransactionExtension::NAME)
->addFilter(new EqualsFilter(PayonePaymentOrderTransactionExtension::NAME . '.transactionId', $payoneTransactionId));

/** @var null|OrderTransactionEntity $transaction */
$transaction = $this->transactionRepository->search($criteria, $context)->first();

/** @var PayonePaymentOrderTransactionDataEntity $payoneTransactionData */
$payoneTransactionData = $transaction->getExtension(PayonePaymentOrderTransactionExtension::NAME);
   ```

### Update transaction data ###

```
$this->transactionRepository->upsert([[
   'id'                                         => $transaction->getId(),
   PayonePaymentOrderTransactionExtension::NAME => [
        'id' => $payoneTransactionData->getId(),
        'sequenceNumber' => 1,
        'transactionState' => 'appointed'
   ],
]], $context);
 ```

# 4.1.0

New Features

* New payment method: Klarna Rechnung
* New payment method: Klarna Sofort
* New payment method: Klarna Ratenkauf
* New payment method: P24
* The credit card - card type is now displayed in the backend at the order details

Bugfixes

* Fixed redirect routing when using multi-saleschannels - Thanks to @patchee500
* Fixed Unzer B2B
* Fixed Refund with wrong tx_id

Maintenance

* tested with 6.4.17.1

# 4.2.0

New Features

* New payment method: PAYONE Secured Invoice
* New payment method: PAYONE Secured Installment
* New payment method: PAYONE Secured Direct Debit
* New payment method: PAYONE WeChat Pay
* New payment method: PAYONE Postfinance Card
* New payment method: PAYONE Postfinance E-Finance
* New payment method: PAYONE AliPay
* Opt-in for automatic capture

Bugfixes

* fixed reference problem in paydirekt
* fixed capture problem in iDEAL
* fixed data-type-casting in migration
* fixed support for vouchers

Maintenance

* improve payment filter technology
* removed birthday field from open invoice
* update iDEAL issuer list
* tested with 6.4.20

# 4.2.1

New Features

*

Bugfixes

* Payla: fix typo in deviceFingerPrint

Maintenance

*
