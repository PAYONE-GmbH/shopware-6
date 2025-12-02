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
                (Array.isArray(card.elements) && card.elements.includes(element)) ||
                (Array.isArray(card.__payoneCollapsedElementsBuffer) && card.__payoneCollapsedElementsBuffer.includes(element))
            );
        },

        setFieldPresentation(element, isVisible) {
            element.config = element.config || {};
            if (element._payoneOrigLabel === undefined) {
                element._payoneOrigLabel = element.config.label;
            }

            if (element._payoneOrigHelpText === undefined) {
                element._payoneOrigHelpText = element.config.helpText;
            }
            
            if (isVisible) {
                element.config.label    = element._payoneOrigLabel;
                element.config.helpText = element._payoneOrigHelpText;
            } else {
                element.config.label    = '';
                element.config.helpText = undefined;
            }
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

            this.config.forEach((card) => {
                const matches = card.name.match(/^payment_(.*)$/);
                const paymentMethodName = matches ? matches[1] : null;

                if (paymentMethodName) {
                    this.addApiConfigurationFieldsToPaymentSettingCard(card, paymentMethodName);
                    this.addPaymentStatusFieldsToPaymentSettingCard(card, paymentMethodName);
                }

                // make ONLY payment_* cards collapsible; leave basic_configuration etc. alone
                if (card.name.startsWith('payment_') || card.name === 'status_mapping') {
                    card.setShowFields = (isVisible) => {
                        card.showFields = !!isVisible;

                        if (card.showFields) {
                            // restore buffered elements to render area
                            if (Array.isArray(card.__payoneCollapsedElementsBuffer) && card.__payoneCollapsedElementsBuffer.length) {
                                card.elements.splice(0, 0, ...card.__payoneCollapsedElementsBuffer);
                                card.__payoneCollapsedElementsBuffer.length = 0;
                            }

                            // unhide everything by default
                            card.elements.forEach((element) => {
                                element.hidden = false;
                                this.setFieldPresentation(element, true);
                            });

                            // then apply status-mapping visibility (depends on toggle)
                            this.showPaymentStatusFieldsBasedOnToggle(card);
                        } else {
                            // create buffer and move everything out of the card (prevents bool fields from leaking)
                            if (!Array.isArray(card.__payoneCollapsedElementsBuffer)) {
                                card.__payoneCollapsedElementsBuffer = [];
                            }
                            card.__payoneCollapsedElementsBuffer.length = 0;

                            card.elements.forEach((element) => {
                                element.hidden = true;
                                this.setFieldPresentation(element, false);
                                card.__payoneCollapsedElementsBuffer.push(element);
                            });

                            // remove all visual elements while collapsed
                            card.elements.splice(0, card.elements.length);
                        }
                    };

                    // start collapsed
                    card.setShowFields(false);
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

                cloned.config          = cloned.config || {};
                cloned.config.helpText = {
                    'en-GB': 'The basic configuration value is used, if nothing is entered here.',
                    'de-DE': 'Es wird der Wert aus der Grundeinstellung verwendet, wenn hier kein Wert eingetragen ist.',
                };

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
                name: this._getShowPaymentStatusFieldsFieldName(card.name)
            });

            // pick the source elements regardless of the collapsed state of the status_mapping card
            const statusTemplateCard = this.config.find(c => c.name === 'status_mapping');
            if (!statusTemplateCard) {
                return;
            }

            const sourceElements = (Array.isArray(statusTemplateCard.elements) && statusTemplateCard.elements.length)
                ? statusTemplateCard.elements
                : (statusTemplateCard.__payoneCollapsedElementsBuffer || []);

            sourceElements.forEach((element) => {
                const cloned= Utils.object.cloneDeep(element);

                cloned.name             = element.name.replace('.paymentStatus', `.${paymentMethodName}PaymentStatus`);
                cloned.__payoneIsStatus = true;
                cloned.hidden           = true;

                this.setFieldPresentation(cloned, false);

                card.elements.push(cloned);
            });
        },

        getElementBind(element, mapInheritance) {
            const bind = this.$super('getElementBind', element, mapInheritance);

            // supply options for all status selects
            if (element.name.includes('PaymentStatus') || element.name.includes('.paymentStatus')) {
                bind.config         = bind.config || {};
                bind.config.options = this.stateMaschineOptions;
            }

            // wire the "show status mapping" toggle
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
                    if (owningCard && owningCard.showFields) {
                        this.showPaymentStatusFieldsBasedOnToggle(owningCard);
                    }
                };

                bind['onUpdate:modelValue'] = handleToggleChange;
                bind.onChange = handleToggleChange;
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
            const rtn = this.$super('getInheritWrapperBind', element);

            rtn.hidden = element.hidden;

            return rtn;
        },

        showPaymentStatusFieldsBasedOnToggle(card) {
            if (!card || !card.showFields) {
                return;
            }

            const configBucket = this._getConfigBucket();
            if (!configBucket) {
                return;
            }

            const toggleFieldName = this._getShowPaymentStatusFieldsFieldName(card.name);
            const toggleIsOn      = !!configBucket[toggleFieldName];

            // ensure the toggle itself is shown while card is open
            const toggleElement = card.elements.find(e => e.name === toggleFieldName);
            if (toggleElement) {
                toggleElement.hidden = false;
                this.setFieldPresentation(toggleElement, true);
            }

            // show/hide only the status-mapping fields; all other fields stay visible while card is open
            card.elements.forEach((element) => {
                if (element.__payoneIsStatus) {
                    element.hidden = !toggleIsOn;
                    this.setFieldPresentation(element, toggleIsOn);
                }
            });
        },

        emitConfig() {
            // keep UI consistent when system-config emits updates
            this.config.forEach(card => this.showPaymentStatusFieldsBasedOnToggle(card));
            this.$super('emitConfig');
        }
    }
});
