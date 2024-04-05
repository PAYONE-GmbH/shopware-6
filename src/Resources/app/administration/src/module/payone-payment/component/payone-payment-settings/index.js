const {Component, Utils} = Shopware;

Component.extend('payone-payment-settings', 'sw-system-config', {

    inject: ['PayonePaymentSettingsService'],

    methods: {

        _getShowPaymentStatusFieldsFieldName(cardName) {
            return `PayonePayment.settings.${cardName}_show_status_mapping`;
        },

        async readConfig() {
            this.stateMaschineOptions = await this.PayonePaymentSettingsService.getStateMachineTransitionActions()
                .then((result) => {
                    return result.data.map((element) => {
                        let translationKey = `payone-payment.transitionActionNames.${element.label}`;
                        let translationValue = this.$t(translationKey);

                        if (translationValue === translationKey) {
                            translationValue = element.label;
                        }

                        return {
                            id: element.value,
                            name: translationValue,
                        }
                    });
                });

            await this.$super('readConfig');

            this.config.forEach(card => {
                const cardNameMatches = card.name.match(/^payment_(.*)$/);
                const paymentMethodName = cardNameMatches ? cardNameMatches[1] : null;

                if (paymentMethodName) {
                    this.addApiConfigurationFieldsToPaymentSettingCard(card, paymentMethodName);
                    this.addPaymentStatusFieldsToPaymentSettingCard(card, paymentMethodName);
                }

                if (card.name.startsWith('payment_') || card.name === 'status_mapping') {
                    card.setShowFields = (isVisible) => {
                        card.showFields = isVisible;
                        card.elements.forEach(element => {
                            element.hidden = !isVisible;
                        });
                        this.showPaymentStatusFieldsBasedOnToggle(card);
                    };
                    card.setShowFields(false);
                }
            });
        },

        addApiConfigurationFieldsToPaymentSettingCard(card, paymentMethodName) {
            const allowedFieldKeys = ['merchantId', 'accountId', 'portalId', 'portalKey'];

            const cardWithStatus = this.config.find(card => card.name === 'basic_configuration');

            const newElements = [];
            cardWithStatus.elements.forEach((element => {
                const matches = element.name.match(/\.([^.]+)$/);
                const fieldKey = matches ? matches[1] : null;
                if (!fieldKey || !allowedFieldKeys.includes(fieldKey)) {
                    return;
                }

                const newElement = Utils.object.cloneDeep(element);
                newElement.name = element.name.replace('.' + fieldKey, '.' + paymentMethodName + (fieldKey[0].toUpperCase() + fieldKey.slice(1)));
                newElement.config.helpText = {
                    "en-GB": "The basic configuration value is used, if nothing is entered here.",
                    "de-DE": "Es wird der Wert aus der Grundeinstellung verwendet, wenn hier kein Wert eingetragen ist.",
                }

                newElements.push(newElement);
            }));

            card.elements = newElements.concat(card.elements); // prepend the fields
        },

        addPaymentStatusFieldsToPaymentSettingCard(card, paymentMethodName) {
            // add toggle
            card.elements.push({
                config: {
                    componentName: 'sw-switch-field',
                    label: {
                        "en-GB": "Display state mapping configuration",
                        "de-DE": "Statusmappingkonfiguration einblenden",
                    },
                    helpText: {
                        "en-GB": "If not configured the general status mapping config will be applied.",
                        "de-DE": "Sie können für jede Zahlungsart ein spezifisches Statusmapping konfigurieren. Existiert eine solche Konfiguration nicht, wird auf die allgemeine Konfiguration zurückgegriffen.",
                    }
                },
                name: this._getShowPaymentStatusFieldsFieldName(card.name)
            });

            // copy all fields from card `status_mapping`
            const cardWithStatus = this.config.find(card => card.name === 'status_mapping');
            cardWithStatus.elements.forEach((element => {
                const newElement = Utils.object.cloneDeep(element);
                newElement.name = element.name.replace('.paymentStatus', `.${paymentMethodName}PaymentStatus`)
                card.elements.push(newElement);
            }));
        },

        getElementBind(element, mapInheritance) {
            const rtn = this.$super('getElementBind', element, mapInheritance);

            if (element.name.includes('PaymentStatus') || element.name.includes('.paymentStatus')) {
                rtn.config.options = this.stateMaschineOptions;
            }

            return rtn;
        },

        getInheritWrapperBind(element) {
            const rtn = this.$super('getInheritWrapperBind', element);

            rtn.hidden = element.hidden;

            return rtn;
        },

        showPaymentStatusFieldsBasedOnToggle(card) {
            const config = this.actualConfigData[this.currentSalesChannelId];
            if (!config) {
                return;
            }
            const isVisible = config[this._getShowPaymentStatusFieldsFieldName(card.name)];

            card.elements.forEach(element => {
                if (element.name.includes('PaymentStatus')) {
                    element.hidden = !isVisible;
                }
            });
        },

        emitConfig() {
            this.config.forEach(card => this.showPaymentStatusFieldsBasedOnToggle(card));

            this.$super('emitConfig');
        }
    }
});
