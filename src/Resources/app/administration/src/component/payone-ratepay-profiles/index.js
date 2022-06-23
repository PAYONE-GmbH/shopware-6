import './payone-ratepay-profiles.scss';
import template from './payone-ratepay-profiles.html.twig';

const { Component, Utils } = Shopware;

Component.register('payone-ratepay-profiles', {
    template,

    props: {
        value: {
            type: Array,
            required: false,
            default() {
                return [];
            }
        },
        name: {
            type: String,
            required: true
        },
    },

    data() {
        return {
            selectedItems: {},
            newItem: null,
            showAlert: false,
            profiles: this.value,
        };
    },

    computed: {
        getLineItemColumns() {
            return [{
                property: 'shopId',
                dataIndex: 'shopId',
                label: this.$tc('payone-payment.general.label.shopId'),
                allowResize: false,
                inlineEdit: 'string',
                width: '200px',
                primary: true,
            }, {
                property: 'currency',
                dataIndex: 'currency',
                label: this.$tc('payone-payment.general.label.currency'),
                allowResize: false,
                inlineEdit: 'string',
                width: '200px',
                primary: true,
            }, {
                property: 'error',
                label: this.$tc('payone-payment.general.label.error'),
                allowResize: false,
                width: '100px',
                primary: true,
            }];
        }
    },

    watch: {
        profiles(profiles) {
            this.$emit('input', profiles);
            this.$emit('change', profiles);
        },
    },

    created() {
        this.createdComponent();
    },

    destroyed() {
        this.destroyedComponent();
    },

    methods: {
        createdComponent() {
            this.$root.$on('payone-ratepay-profiles-update-result', this.onProfilesUpdateResult);
        },

        destroyedComponent() {
            this.$root.$off('payone-ratepay-profiles-update-result');
        },

        onProfilesUpdateResult(result) {
            if (result['updates'][this.name]) {
                this.profiles = result['updates'][this.name];
            }
            if (result['errors'][this.name]) {
                for (const error of result['errors'][this.name]) {
                    this.profiles.push(error);
                }
            }
        },

        onInlineEditCancel() {
            this.$emit('item-cancel');
        },

        onInlineEditSave(currentItem) {
          let shopIdExists = false;
            this.profiles.forEach(function(item) {
                if(item.id !== currentItem.id && item.shopId === currentItem.shopId) {
                    shopIdExists = true;
                }
            });

            if(shopIdExists) {
              this.showAlert = true;

              this.$nextTick(() => {
                this.$refs.shopIdsDataGrid.currentInlineEditId = currentItem.id;
                this.$refs.shopIdsDataGrid.enableInlineEdit();
              });
            } else {
              this.showAlert = false;
            }

            this.$emit('update-list', this.profiles);
        },

        createNewLineItem() {
            const newId = Utils.createId();

            this.profiles.push({'id': newId, 'shopId': '', 'currency': '' });

            this.$nextTick(() => {
                this.$refs.shopIdsDataGrid.currentInlineEditId = newId;
                this.$refs.shopIdsDataGrid.enableInlineEdit();
            });
        },

        onDeleteSelectedItem(itemToDelete) {
            this.profiles = this.profiles.filter(currentItem => currentItem.shopId !== itemToDelete.shopId);

            this.$emit('deleted', this.profiles);
        },
    }
});
