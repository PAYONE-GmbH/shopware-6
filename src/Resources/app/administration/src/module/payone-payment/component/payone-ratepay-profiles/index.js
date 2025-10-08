import './payone-ratepay-profiles.scss';
import template from './payone-ratepay-profiles.html.twig';

const { Utils } = Shopware;

export default {
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
            showDuplicateAlert: false,
            showEmptyAlert: false,
            profiles: this.value,
        };
    },

    computed: {
        getLineItemColumns() {
            return [{
                property: 'shopId',
                dataIndex: 'shopId',
                label: this.$t('payone-payment.general.label.shopId'),
                allowResize: false,
                inlineEdit: 'string',
                width: '200px',
                primary: true,
            }, {
                property: 'currency',
                dataIndex: 'currency',
                label: this.$t('payone-payment.general.label.currency'),
                allowResize: false,
                inlineEdit: 'string',
                width: '200px',
                primary: true,
            }, {
                property: 'error',
                label: this.$t('payone-payment.general.label.error'),
                allowResize: false,
                width: '100px',
                primary: true,
            }];
        }
    },

    watch: {
        profiles(profiles) {
            this.$emit('update:value', profiles);
            this.$emit('input', profiles); // required for SW 6.5.x compatibility
            this.$emit('change', profiles); // required for SW 6.5.x compatibility
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
            Shopware.Utils.EventBus.on('payone-ratepay-profiles-update-result', this.onProfilesUpdateResult);
        },

        destroyedComponent() {
            Shopware.Utils.EventBus.off('payone-ratepay-profiles-update-result');
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

        onInlineEditCancel(currentItem) {
            if(currentItem.shopId === "" && currentItem.currency === "") {
                this.profiles.forEach(function(item, index, array) {
                    if(item.id === currentItem.id) {
                        array.splice(index, 1);
                    }
                });
            }

            this.$emit('item-cancel');
        },

        onInlineEditSave(currentItem) {
            if(currentItem.shopId !== "" && currentItem.currency !== "") {
                this.showEmptyAlert = false;
                let shopIdExists = false;
                this.profiles.forEach(function(item) {
                    if(item.id !== currentItem.id && item.shopId === currentItem.shopId) {
                        shopIdExists = true;
                    }
                });

                if(shopIdExists) {
                  this.showDuplicateAlert = true;

                  this.$nextTick(() => {
                    this.$refs.shopIdsDataGrid.currentInlineEditId = currentItem.id;
                    this.$refs.shopIdsDataGrid.enableInlineEdit();
                  });
                } else {
                  this.showDuplicateAlert = false;
                }
            } else {
                this.showEmptyAlert = true;

                this.$nextTick(() => {
                    this.$refs.shopIdsDataGrid.currentInlineEditId = currentItem.id;
                    this.$refs.shopIdsDataGrid.enableInlineEdit();
                });
            }

            this.$emit('update-list', this.profiles);
        },

        createNewLineItem() {
            let emptyProfilesList = false;

            emptyProfilesList = this.profiles.length === 0;

            if(emptyProfilesList) {
                this.createLine();
                return;
            }

            if(this.profiles[this.profiles.length - 1].shopId !== "") {
                this.createLine();
            }
        },

        createLine() {
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
};
