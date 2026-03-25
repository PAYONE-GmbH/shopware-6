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

        const resolvedCardInputMode = cardInputMode || "manual";

        const modeField = document.getElementById("payoneCtpCardInputMode");
        if (modeField) {
            modeField.value = String(resolvedCardInputMode);
        }

        const paymentCheckoutDataField = document.getElementById("payoneCtpPaymentCheckoutData");

        const pseudoCardPanField    = document.getElementById("payoneCtpPseudoCardPan");
        const truncatedCardPanField = document.getElementById("payoneCtpTruncatedCardPan");
        const cardExpireDateField   = document.getElementById("payoneCtpCardExpireDate");
        const cardTypeField         = document.getElementById("payoneCtpCardType");
        const cardHolderField       = document.getElementById("payoneCtpCardHolder");

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

        if (resolvedCardInputMode === "clickToPay" || resolvedCardInputMode === "register") {
            if (paymentCheckoutDataField) {
                paymentCheckoutDataField.value = String(token);
            }

            if (pseudoCardPanField) {
                pseudoCardPanField.value = "";
            }

            if (this.tokenField) {
                this.tokenField.value = "";
            }
        } else {
            if (pseudoCardPanField) {
                pseudoCardPanField.value = String(token);
            }

            if (paymentCheckoutDataField) {
                paymentCheckoutDataField.value = "";
            }

            if (this.tokenField) {
                this.tokenField.value = String(token);
            }
        }

        this.submitBtn.disabled = false;
        this.orderForm.submit();
    }

    tokenizationFailureCallback(statusCode, errorResponse)
    {
        console.error("Tokenization of card failed");

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

    getConfig(me)
    {
        return {
            iframe: {
                iframeWrapperId: "payment-IFrame",
                zIndex: 10000,
                height: 500,
                width: 400
            },
            uiConfig: {
                formBgColor: "#ffffff",
                fieldBgColor: "wheat",
                fieldBorder: "1px solid #b33cd8",
                fieldOutline: "#101010 solid 5px",
                fieldLabelColor: "#d3d83c",
                fieldPlaceholderColor: "blue",
                fieldTextColor: "crimson",
                fieldErrorCodeColor: "green"
            },
            locale: "de_DE",
            token: this.options.token,
            mode: "live",
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
                schemeConfig: {
                    merchantPresentationName: "PayoneC2P-00004",
                    visaConfig: {
                        srcInitiatorId: this.options.visaSrcInitiatorId,
                        srcDpaId: this.options.visaSrcDpaId,
                        encryptionKey: this.options.visaEncryptionKey,
                        nModulus: this.options.visaNModulus,
                    },
                    mastercardConfig: {
                        srcInitiatorId: this.options.mastercardSrcInitiatorId,
                        srcDpaId: this.options.mastercardSrcDpaId,
                    }
                },
                transactionAmount: {
                    amount: this.options.amount,
                    currencyCode: this.options.currencyCode
                },
                uiConfig: {
                    buttonStyle: this.options.buttonStyle,
                    buttonTextCase: this.options.buttonTextCase,
                    buttonAndBadgeColor: this.options.buttonAndBadgeColor,
                    buttonFilledHoverColor: this.options.buttonFilledHoverColor,
                    buttonOutlinedHoverColor: this.options.buttonOutlinedHoverColor,
                    buttonDisabledColor: this.options.buttonDisabledColor,
                    cardItemActiveColor: this.options.cardItemActiveColor,
                    buttonAndBadgeTextColor: this.options.buttonAndBadgeTextColor,
                    linkTextColor: this.options.linkTextColor,
                    accentColor: this.options.accentColor,
                    fontFamily: this.options.fontFamily,
                    buttonAndInputRadius: this.options.buttonAndInputRadius,
                    cardItemRadius: this.options.cardItemRadius
                },
                shopName: this.el.getAttribute("data-shop-name"),
            }
        };
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
