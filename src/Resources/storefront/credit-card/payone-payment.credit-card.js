/* eslint-disable import/no-unresolved */

import Plugin from 'src/script/plugin-system/plugin.class';

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
        ],
    };

    init() {
        this.iframe = null;
        this.orderFormDisabled = true;

        let requestContainer = document.getElementById('payone-request');

        let language = requestContainer.getAttribute('data-payone-language');
        let request = JSON.parse(requestContainer.innerHTML);

        this._createScript(() => {
            let config = this._getClientConfig(language);

            this.iframe = new window.Payone.ClientApi.HostedIFrames(config, request);

            document
                .getElementById('confirmOrderForm')
                .addEventListener("submit", this._handleOrderSubmit.bind(this));
        });
    }

    _getSelectStyle() {
        const styles = [
            "width: 100%;",
            "height: calc(1.5em + 1.45rem);",
            "padding: .5625rem;",
            "color: #8798a9;",
            "vertical-align: middle;",
            "line-height: 1.5;",
            "font-weight: 500;",
            "background-color: #fff;",
            "border: .0625rem solid #d1d9e0;",
            "border-radius: 3px",
        ];

        return styles.join(' ');
    }

    _getFieldStyle() {
        const styles = [
            "width: 100%;",
            "height: 100%;",
            "padding: .5625rem;",
            "color: #8798a9;",
            "vertical-align: middle;",
            "line-height: 1.5;",
            "font-weight: 500;",
            "background-color: #fff;",
            "border: .0625rem solid #d1d9e0;",
            "border-radius: .1875rem;",
        ];

        return styles.join(' ');
    }

    _getClientConfig(language) {
        return {
            fields: {
                cardpan: {
                    selector: 'cardpan',
                    type: 'text',
                    style: this._getFieldStyle(),
                },
                cardcvc2: {
                    selector: 'cardcvc2',
                    type: 'password',
                    size: '4',
                    maxlength: '4',
                    style: this._getFieldStyle(),
                },
                cardexpiremonth: {
                    selector: 'cardexpiremonth',
                    type: 'select',
                    size: '2',
                    maxlength: '2',
                    style: this._getSelectStyle(),
                },
                cardexpireyear: {
                    selector: 'cardexpireyear',
                    type: 'select',
                    style: this._getSelectStyle(),
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
            },
        };
    }

    _cardDetectionCallback(detectedCardtype) {
        if (detectedCardtype === "-" || detectedCardtype === "?") {
            return;
        }

        let src = 'https://cdn.pay1.de/cc/' + detectedCardtype.toLowerCase() + '/xl/default.png';

        let errorOutput = document.getElementById('errorOutput');
        let logo = document.getElementById('card-logo');

        logo.setAttribute("src", src);

        errorOutput.style.display = 'none';
        logo.style.display = 'block';
    }

    _createScript(callback) {
        let url = 'https://secure.pay1.de/client-api/js/v1/payone_hosted.js';

        let script = document.createElement('script');
        script.type = 'text/javascript';
        script.src = url;

        script.addEventListener('load', callback.bind(this), false);

        document.head.appendChild(script);
    }

    _handleOrderSubmit(event) {
        let errorOutput = document.getElementById('errorOutput');

        errorOutput.style.display = 'none';

        let savedCardPan = document.getElementById('savedpseudocardpan');

        if (savedCardPan && savedCardPan.value.length > 0) {
            return true;
        }

        if (!this.iframe.isComplete() || this.orderFormDisabled) {
            let me  = this;

            window.creditCardCheckCallback = function(response) {
                me._payoneCheckCallback(response);
            };

            this.iframe.creditCardCheck('creditCardCheckCallback');

            event.preventDefault();

            return false;
        }
    }

     _payoneCheckCallback(response) {
        if (response.status === 'VALID') {
            document.getElementById('pseudocardpan').value = response.pseudocardpan;
            document.getElementById('truncatedcardpan').value = response.truncatedcardpan;

            this.orderFormDisabled = false;

            document.getElementById('confirmOrderForm').submit()
        } else {
            let button = document.getElementById('confirmFormSubmit');
            let errorOutput = document.getElementById('errorOutput');

            button.removeAttribute('disabled');

            errorOutput.innerHTML = response.errormessage;
            errorOutput.style.display = 'block';
        }
    }
}
