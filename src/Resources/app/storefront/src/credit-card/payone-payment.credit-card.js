/* eslint-disable import/no-unresolved */

import Plugin from 'src/plugin-system/plugin.class';
import ButtonLoadingIndicator from 'src/utility/loading-indicator/button-loading-indicator.util';

export default class PayonePaymentCreditCard extends Plugin {
    static options = {
        supportedCardtypes: [
            '#',
            'V',
            'A',
            'M',
            'D',
            'J',
            'O',
            'U',
            'P'
        ]
    };

    init() {
        this.iframe = null;

        this.iframeFieldCheckerStarted = false;
        this.orderFormDisabled = true;

        const requestContainer = document.getElementById('payone-request');

        const language = requestContainer.getAttribute('data-payone-language');
        const request = JSON.parse(requestContainer.innerHTML);

        this._createScript(() => {
            const config = this.getClientConfig(language);

            this.iframe = new window.Payone.ClientApi.HostedIFrames(config, request);

            const field = document.getElementById('savedpseudocardpan');
            const form = document.getElementById('confirmOrderForm');

            if (field) {
                field.addEventListener('change', this._handleChangeSavedCard.bind(this));
            }

            if (form) {
                form.addEventListener('submit', this._handleOrderSubmit.bind(this));
            }
        });
    }

    getSelectStyle() {
        return [
            'width: 100%',
            'padding: .5625rem',
            'color: #8798a9',
            'vertical-align: middle',
            'line-height: 1.5',
            'font-weight: 500',
            'background-color: #fff',
            'border: none',
            'border-radius: 3px'
        ];
    }

    getFieldStyle() {
        return [
            'width: 100%',
            'height: 100%',
            'padding: .5625rem',
            'color: #8798a9',
            'vertical-align: middle',
            'line-height: 1.5',
            'font-weight: 500',
            'background-color: #fff',
            'border: none',
            'border-radius: .1875rem'
        ];
    }

    getClientConfig(language) {
        return {
            fields: {
                cardpan: {
                    selector: 'cardpan',
                    type: 'text',
                    style: this.getFieldStyle().join('; ')
                },
                cardcvc2: {
                    selector: 'cardcvc2',
                    type: 'password',
                    size: '4',
                    maxlength: '4',
                    length: { 'V': 3, 'M': 3, 'A': 4, 'D': 3, 'J': 0, 'O': 3, 'P': 3, 'U': 3 },
                    style: this.getFieldStyle().join('; ')
                },
                cardexpiremonth: {
                    selector: 'cardexpiremonth',
                    type: 'select',
                    size: '2',
                    maxlength: '2',
                    style: this.getSelectStyle().join('; ')
                },
                cardexpireyear: {
                    selector: 'cardexpireyear',
                    type: 'select',
                    style: this.getSelectStyle().join('; ')
                }
            },

            language: window.Payone.ClientApi.Language[language],

            defaultStyle: {
                iframe: {
                    height: '100%',
                    width: '100%'
                }
            },

            autoCardtypeDetection: {
                supportedCardtypes: PayonePaymentCreditCard.options.supportedCardtypes,
                callback: this._cardDetectionCallback
            }
        };
    }

    _cardDetectionCallback(detectedCardtype) {
        if (detectedCardtype === '-' || detectedCardtype === '?') {
            return;
        }

        const src = 'https://cdn.pay1.de/cc/' + detectedCardtype.toLowerCase() + '/xl/default.png';

        const errorOutput = document.getElementById('errorOutput');
        const logo = document.getElementById('card-logo');

        logo.setAttribute('src', src);

        errorOutput.style.display = 'none';
        logo.style.display = 'block';
    }

    _createScript(callback) {
        const url = 'https://secure.pay1.de/client-api/js/v1/payone_hosted.js';

        const script = document.createElement('script');
        script.type = 'text/javascript';
        script.src = url;

        script.addEventListener('load', callback.bind(this), false);

        document.head.appendChild(script);
    }

    _handleOrderSubmit(event) {
        const errorOutput = document.getElementById('errorOutput');

        errorOutput.style.display = 'none';

        const savedCardPan = document.getElementById('savedpseudocardpan');

        if (savedCardPan && savedCardPan.value.length > 0) {
            return true;
        }

        // Check if the hosted IFrame fields are invalid,
        // if so, show error message and prevent form submit.
        if (!this.iframe.isComplete()) {
            const iframeError = document.getElementById('iframeErrorOutput');

            // Register an interval to after the first time the
            // user clicked the submit button to re-check the fields.
            if (!this.iframeFieldCheckerStarted) {
                setInterval(() => {
                    if (this.iframe.isComplete()) {
                        iframeError.style.display = 'none';
                    } else {
                        iframeError.style.display = 'block';
                    }
                }, 250);
            }

            // Flag that the check interval has started and prevent
            // submitting the form.
            this.iframeFieldCheckerStarted = true;
            this._handleOrderFormError(event);
            return false;
        }

        // Perform the credit card check if no check was performed
        // before or the prior check was not successful.
        if (this.orderFormDisabled) {
            window.payoneCreditCardCheckCallback = this._payoneCheckCallback.bind(this);
            this.iframe.creditCardCheck('payoneCreditCardCheckCallback');

            this._handleOrderFormError(event);
            return false;
        }
    }

    _handleOrderFormError(event) {
        const confirmFormSubmit = document.getElementById('confirmFormSubmit');

        event.preventDefault();

        if(confirmFormSubmit) {
            const loader = new ButtonLoadingIndicator(confirmFormSubmit);
            confirmFormSubmit.disabled = false;
            loader.remove();
        }
    }

    _handleChangeSavedCard() {
        const savedCardPan = document.getElementById('savedpseudocardpan');

        if (savedCardPan.options[savedCardPan.selectedIndex].value) {
            [...document.getElementsByClassName('credit-card-input')].forEach(function(element) {
                element.classList.add('hide')
            });
        } else {
            [...document.getElementsByClassName('credit-card-input')].forEach(function(element) {
                element.classList.remove('hide');
            });
        }
    }

    _payoneCheckCallback(response) {
        if (response.status === 'VALID') {
            document.getElementById('pseudocardpan').value = response.pseudocardpan;
            document.getElementById('truncatedcardpan').value = response.truncatedcardpan;
            document.getElementById('cardexpiredate').value = response.cardexpiredate;

            this.orderFormDisabled = false;

            document.getElementById('confirmOrderForm').submit()
        } else {
            const button = document.getElementById('confirmFormSubmit');
            const errorOutput = document.getElementById('errorOutput');

            button.removeAttribute('disabled');

            errorOutput.innerHTML = response.errormessage;
            errorOutput.style.display = 'block';
        }
    }
}
