const Plugin = window.PluginBaseClass;

export default class PayonePaymentClickToPay extends Plugin
{
    init()
    {
        this.tokenField = this.el.querySelector("[data-click-to-pay-token]");
        this.submitBtn  = this.el.querySelector("button");
        this.errorBox   = this.el.querySelector("#payone-click-to-pay-error");
        this.errorMsg   = this.el.querySelector(".alert-content-container");
        this.orderForm  = document.getElementById("confirmOrderForm");

        this.submitBtn.addEventListener("click", this._submitForm.bind(this));
        this.options = this._parseOptions();

        if (window.HostedTokenizationSdk) {
            this.loadSDK();
        } else {
            console.error("HTP-SDK failed to load");
        }
    }

    async loadSDK()
    {
        try {
            await window.HostedTokenizationSdk.init();
            window.HostedTokenizationSdk.getPaymentPage(this.getConfig(), this.callbackfunc);
        } catch (error) {
            console.error("Error initializing HTP-SDK:", error);
        }
    }

    tokenizationSuccessCallback(statusCode, token, cardDetails, cardInputMode)
    {
        if (null === cardDetails) {
            return;
        }

        const modeField = document.getElementById("payoneCtpCardInputMode");

        if (modeField) {
            modeField.value = String(cardInputMode);
        }

        const paymentCheckoutDataField = document.getElementById('payoneCtpPaymentCheckoutData');
        const pseudoCardPanField       = document.getElementById('payoneCtpPseudoCardPan');
        const truncatedCardPanField    = document.getElementById('payoneCtpTruncatedCardPan');
        const cardExpireDateField      = document.getElementById('payoneCtpCardExpireDate');
        const cardTypeField            = document.getElementById('payoneCtpCardType');
        const cardHolderField          = document.getElementById('payoneCtpCardHolder');

        if (cardHolderField && cardDetails && cardDetails.cardholderName) {
            cardHolderField.value = String(cardDetails.cardholderName);
        }
        if (truncatedCardPanField && cardDetails && cardDetails.cardNumber) {
            truncatedCardPanField.value = String(cardDetails.cardNumber);
        }
        if (cardExpireDateField && cardDetails && cardDetails.expiryDate) {
            cardExpireDateField.value = String(cardDetails.expiryDate);
        }
        if (cardTypeField && cardDetails && cardDetails.cardType) {
            cardTypeField.value = String(cardDetails.cardType);
        }

        this.submitBtn.disabled = false;

        if (cardInputMode === "clickToPay" || cardInputMode === "register") {
            if (paymentCheckoutDataField) {
                paymentCheckoutDataField.value = String(token);
            }

            if (pseudoCardPanField) {
                pseudoCardPanField.value = "";
            }

            if (this.tokenField) {
                this.tokenField.value = "";
            }

            this.orderForm.submit();

            return;
        }

        if (pseudoCardPanField) {
            pseudoCardPanField.value = String(token);
        }

        if (paymentCheckoutDataField) {
            paymentCheckoutDataField.value = "";
        }

        if (this.tokenField) {
            this.tokenField.value = String(token);
        }

        this.orderForm.submit();
    }

    tokenizationFailureCallback(statusCode, errorResponse)
    {
        // Try to extract a usable message
        const message =
                  (errorResponse && errorResponse.error) ? String(errorResponse.error) :
                      (errorResponse && errorResponse.message) ? String(errorResponse.message) :
                          "Payment preparation error, please try again.";

        this._showError(message);

        // 2) Common JWT problems: suggest retry / reload
        // The SDK often returns 400 with messages like "JWT ... incorrect/expired"
        const msgLower          = message.toLowerCase();
        const looksLikeJwtIssue = msgLower.includes("jwt") && (
            msgLower.includes("expired") || msgLower.includes("incorrect") || msgLower.includes("invalid")
        );

        if (looksLikeJwtIssue) {
            // simplest recovery without adding an endpoint:
            // reload confirm page so your PHP re-fetches a fresh JWT from session logic
            console.warn("CTP: JWT issue detected, reloading page to refresh token");
            window.location.reload();
        }

        this.submitBtn.disabled = false;
    }

    callbackfunc(statusCode, res)
    {
        // If callback contains an error, surface it
        if (res && (res.error || res.errorMessage)) {
            console.error("Tokenization error", statusCode);
            const message = String(res.error || res.errorMessage);
            this._showError(message);
        }
    }

    getConfig()
    {
        let config = {
            mode: "live",
            iframe: {
                iframeWrapperId: "payment-IFrame",
                zIndex: 10000,
                height: 500,
                width: 400,
            },
            locale: this.options.locale,
            token: this.options.token,
            email:  this.options.email,
            showCardholderName: false,
            allowedCardSchemes: [
                "amex",
                "diners",
                "discover",
                "jcb",
                "maestro",
                "mastercard",
                "visa",
                "unionpay",
            ],
            CTPConfig: {
                enableCTP: true,
                enableCustomerOnboarding: true,
                transactionAmount: {
                    amount: this.options.amount,
                    currencyCode: this.options.currencyCode,
                },
                shopName: this.el.getAttribute("data-shop-name"),
            }
        };

        const labelStyle = Object.fromEntries(Object.entries({
            fontSize: this.options.labelStyleFontSize,
            fontWeight: this.options.labelStyleFontWeight,
        }).filter(([ _, value ]) => null !== value));

        const inputStyle = Object.fromEntries(Object.entries({
            fontSize: this.options.inputStyleFontSize,
            fontWeight: this.options.inputStyleFontWeight,
        }).filter(([ _, value ]) => null !== value));

        const errorValidationStyle = Object.fromEntries(Object.entries({
            fontSize: this.options.errorValidationStyleFontSize,
            fontWeight: this.options.errorValidationStyleFontWeight,
        }).filter(([ _, value ]) => null !== value));

        const uiConfig = Object.fromEntries(Object.entries({
            formBgColor: this.options.formBgColor,
            fieldBgColor: this.options.fieldBgColor,
            fieldBorder: this.options.fieldBorder,
            fieldOutline: this.options.fieldOutline,
            fieldLabelColor: this.options.fieldLabelColor,
            fieldPlaceholderColor: this.options.fieldPlaceholderColor,
            fieldTextColor: this.options.fieldTextColor,
            fieldErrorCodeColor: this.options.fieldErrorCodeColor,
            fontFamily: this.options.fontFamily,
            fontUrl: this.options.fontUrl,
            btnBgColor: this.options.btnBgColor,
            btnTextColor: this.options.btnTextColor,
            btnBorderColor: this.options.btnBorderColor,
            separatorColor: this.options.separatorColor,
            separatorTextColor: this.options.separatorTextColor,
        }).filter(([ _, value ]) => null !== value));

        if (0 < Object.keys(labelStyle).length) {
            uiConfig['labelStyle'] = labelStyle;
        }

        if (0 < Object.keys(inputStyle).length) {
            uiConfig['inputStyle'] = inputStyle;
        }

        if (0 < Object.keys(errorValidationStyle).length) {
            uiConfig['errorValidationStyle'] = errorValidationStyle;
        }

        if (0 < Object.keys(uiConfig).length) {
            config['uiConfig'] = uiConfig;
        }

        return config;
    }

    _parseOptions()
    {
        try {
            return JSON.parse(this.el.getAttribute("data-payone-click-to-pay-options"));
        } catch (err) {
            console.error("PayoneClickToPay: config parse error", err);

            return {};
        }
    }

    _submitForm(e)
    {
        e.preventDefault();
        this._hideError();

        if (!this.orderForm.reportValidity()) {
            return;
        }

        this.submitBtn.disabled = true;

        window.HostedTokenizationSdk.submitForm(
            this.tokenizationSuccessCallback.bind(this),
            this.tokenizationFailureCallback.bind(this)
        );
    }

    _showError(msg)
    {
        if (this.errorMsg) {
            this.errorMsg.textContent = msg;
        }
        if (this.errorBox) {
            this.errorBox.style.display = "block";
        }
    }

    _hideError()
    {
        if (this.errorMsg) {
            this.errorMsg.textContent = "";
        }
        if (this.errorBox) {
            this.errorBox.style.display = "none";
        }
    }
}
