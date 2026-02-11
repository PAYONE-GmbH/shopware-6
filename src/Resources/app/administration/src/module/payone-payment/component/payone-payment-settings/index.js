const { Component, Utils } = Shopware;

Component.extend('payone-payment-settings', 'sw-system-config', {

    inject: ['PayonePaymentSettingsService'],

    methods: {
        _getShowPaymentStatusFieldsFieldName(cardName) {
            return `PayonePayment.settings.${cardName}_show_status_mapping`;
        },

        _getConfigBucket() {
            const all = this.actualConfigData || {};
            const key = (this.currentSalesChannelId === null || this.currentSalesChannelId === undefined)
                ? 'null'
                : this.currentSalesChannelId;
            return all[key] || all.null || null;
        },

        _findCardByElement(element) {
            return this.config.find(card =>
                Array.isArray(card.elements) && card.elements.includes(element)
            );
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
                            value: element.value,
                            name: translationValue,
                            label: translationValue,
                        }
                    });
                });

            await this.$super('readConfig');

            this.config.forEach((card) => {
                const matches           = card.name.match(/^payment_(.*)$/);
                const paymentMethodName = matches ? matches[1] : null;

                if (paymentMethodName) {
                    this.addApiConfigurationFieldsToPaymentSettingCard(card, paymentMethodName);
                    this.addPaymentStatusFieldsToPaymentSettingCard(card, paymentMethodName);
                }

                if (card.name === 'status_mapping' && Array.isArray(card.elements)) {
                    card.elements.forEach(element => {
                        if (element.name.toLowerCase().includes('paymentstatus')) {
                            element.config         = element.config || {};
                            element.config.options = this.stateMaschineOptions;
                        }
                    });
                }
            });

            this.config.forEach((card) => {
                if (card.name.startsWith('payment_') || card.name === 'status_mapping') {
                    card.showFields = false;

                    card.setShowFields = (visible) => {
                        card.showFields = visible;
                        this.updateCardVisibility(card);
                    };

                    this.updateCardVisibility(card);
                }
            });
        },

        updateCardVisibility(card) {
            if (!card || !Array.isArray(card.elements)) {
                return;
            }

            const bucket = this._getConfigBucket();
            const toggleFieldName       = this._getShowPaymentStatusFieldsFieldName(card.name);
            const isStatusMappingActive = bucket ? !!bucket[toggleFieldName] : false;
            const isGlobalMappingCard   = (card.name === 'status_mapping');

            card.elements.forEach((element) => {
                if (!card.showFields) {
                    element.hidden = true;
                    return;
                }

                if (isGlobalMappingCard) {
                    element.hidden = false;
                } else {
                    if (element.__payoneIsStatus) {
                        element.hidden = !isStatusMappingActive;
                    } else {
                        element.hidden = false;
                    }
                }
            });
        },

        addApiConfigurationFieldsToPaymentSettingCard(card, paymentMethodName) {
            const allowedFieldKeys = ['merchantId', 'accountId', 'portalId', 'portalKey'];
            const basicCard        = this.config.find(c => c.name === 'basic_configuration');

            if (!basicCard || !Array.isArray(basicCard.elements)) {
                return;
            }

            const elementsToPrepend = [];

            basicCard.elements.forEach((element) => {
                const matches  = element.name.match(/\.([^.]+)$/);
                const fieldKey = matches ? matches[1] : null;

                if (!fieldKey || !allowedFieldKeys.includes(fieldKey)) {
                    return;
                }

                const cloned = Utils.object.cloneDeep(element);

                cloned.name = element.name.replace(
                    '.' + fieldKey,
                    '.' + paymentMethodName + (fieldKey[0].toUpperCase() + fieldKey.slice(1))
                );

                cloned.config = cloned.config || {};
                cloned.config.helpText = {
                    'en-GB': 'The basic configuration value is used, if nothing is entered here.',
                    'de-DE': 'Es wird der Wert aus der Grundeinstellung verwendet, wenn hier kein Wert eingetragen ist.',
                };
                cloned.hidden = true;

                elementsToPrepend.push(cloned);
            });

            if (elementsToPrepend.length) {
                card.elements.splice(0, 0, ...elementsToPrepend);
            }
        },

        addPaymentStatusFieldsToPaymentSettingCard(card, paymentMethodName) {
            card.elements.push({
                type: 'bool',
                config: {
                    componentName: 'mt-switch',
                    label: {
                        'en-GB': 'Display state mapping configuration',
                        'de-DE': 'Statusmappingkonfiguration einblenden',
                    },
                    helpText: {
                        'en-GB': 'If not configured the general status mapping config will be applied.',
                        'de-DE': 'Sie können für jede Zahlungsart ein spezifisches Statusmapping konfigurieren. Existiert eine solche Konfiguration nicht, wird auf die allgemeine Konfiguration zurückgegriffen.',
                    }
                },
                name: this._getShowPaymentStatusFieldsFieldName(card.name),
                hidden: true
            });

            const statusTemplateCard = this.config.find(c => c.name === 'status_mapping');
            if (!statusTemplateCard) {
                return;
            }

            const sourceElements = statusTemplateCard.elements || [];

            sourceElements.forEach((element) => {
                const cloned = Utils.object.cloneDeep(element);

                cloned.name             = element.name.replace('.paymentStatus', `.${paymentMethodName}PaymentStatus`);
                cloned.__payoneIsStatus = true;
                cloned.hidden           = true;

                if (this.stateMaschineOptions) {
                    cloned.config         = cloned.config || {};
                    cloned.config.options = this.stateMaschineOptions;
                }

                card.elements.push(cloned);
            });
        },

        getElementBind(element, mapInheritance) {
            const bind          = this.$super('getElementBind', element, mapInheritance);
            const isStatusField = element.name.toLowerCase().includes('paymentstatus');

            if (isStatusField) {
                bind.config         = bind.config || {};
                bind.config.options = this.stateMaschineOptions;

                const bucket = this._getConfigBucket();
                if (bucket && bucket[element.name] !== undefined) {
                    bind.value      = bucket[element.name];
                    bind.modelValue = bucket[element.name];
                }
            }

            if (element.name.endsWith('_show_status_mapping')) {
                const fieldName = element.name;
                const bucket    = this._getConfigBucket() || {};
                const current   = !!bucket[fieldName];

                bind.modelValue = current;
                bind.value      = current;

                const originalOnChange = bind.onChange;

                const handleToggleChange = (nextVal) => {
                    const normalized = (nextVal === true || nextVal === 'true' || nextVal === 1 || nextVal === '1');

                    const activeBucket = this._getConfigBucket();
                    if (activeBucket) {
                        activeBucket[fieldName] = normalized;
                    }

                    if (typeof originalOnChange === 'function') {
                        originalOnChange(normalized, fieldName);
                    }

                    const owningCard = this._findCardByElement(element);
                    if (owningCard) {
                        this.updateCardVisibility(owningCard);
                    }
                };

                bind['onUpdate:modelValue'] = handleToggleChange;
                bind.onChange               = handleToggleChange;
            }

            if (element.config && element.config.componentName === 'payone-ratepay-profiles') {
                const bucket = this._getConfigBucket();

                if (bucket && bucket[element.name]) {
                    bind.value = bucket[element.name];
                }

                bind['onUpdate:value'] = (newValue) => {
                    if (newValue instanceof Event || (newValue && newValue.target && newValue.type)) {
                        return;
                    }

                    const activeBucket = this._getConfigBucket();
                    if (activeBucket) {
                        activeBucket[element.name] = newValue;
                    }
                };
            }

            return bind;
        },

        getInheritWrapperBind(element) {
            const bind = this.$super('getInheritWrapperBind', element);

            if (element.hidden) {
                bind.style = { display: 'none' };
            }

            return bind;
        },

        emitConfig() {
            this.config.forEach(card => {
                if (card.name.startsWith('payment_') || card.name === 'status_mapping') {
                    this.updateCardVisibility(card);
                }
            });
            
            this.$super('emitConfig');
        }
    }
});