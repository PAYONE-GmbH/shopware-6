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
        }
    },

    data() {
        return {
            selectedItems: {},
            newItem: null,
            showAlert: false,
        };
    },

    computed: {
        valueItems() {
            return this.value;
        },

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
            }];
        }
    },

    watch: {
        value(value) {
            this.$emit('input', value);
            this.$emit('change', value);
        },
    },

    methods: {
        onInlineEditCancel() {
            this.$emit('item-cancel');
        },

        onInlineEditSave(currentItem) {
          let shopIdExists = false;
            this.value.forEach(function(item) {
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

            this.$emit('update-list', this.value);
        },

        createNewLineItem() {
            const newId = Utils.createId();

            this.value.push({'id': newId, 'shopId': '', 'currency': '' });

            this.$nextTick(() => {
                this.$refs.shopIdsDataGrid.currentInlineEditId = newId;
                this.$refs.shopIdsDataGrid.enableInlineEdit();
            });
        },

        onDeleteSelectedItem(itemToDelete) {
            this.value = this.value.filter(currentItem => currentItem.shopId !== itemToDelete.shopId);

            this.$emit('deleted', this.value);
        },
    }
});
