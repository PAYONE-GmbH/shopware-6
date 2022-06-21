import './payone-ratepay-profiles.scss';
import template from './payone-ratepay-profiles.html.twig';

const { Component, Utils } = Shopware;

Component.register('payone-ratepay-profiles', {
    template,

    props: {
        profile: {
            type: String,
            required: false
        },

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
            isLoading: false,
            selectedItems: {},
            total: 0,
            newItem: null,
            showAlert: false,
        };
    },

    computed: {
        shopIdsRepository() {
            return this.repositoryFactory.create('payone_ratepay_shop_ids');
        },

        getCriteria() {
            const criteria = new Criteria(1, 500);
            criteria.addFilter(Criteria.equals('profile', this.profile))
              .addSorting(Criteria.sort('position', 'ASC'));

            return criteria;
        },

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
        createdComponent() {
            this.isLoading = true;
            this.shopIdsRepository.search(this.getCriteria, Shopware.Context.api).then((items) => {
                this.total = items.total;
                this.groupOptions = items;
                this.isLoading = false;
                return items;
            }).catch(() => {
                this.isLoading = false;
            });
        },

        onInlineEditCancel() {
            this.$emit('item-cancel');
        },

        onInlineEditSave(currentItem) {
            const values = JSON.stringify(this.value);
            const currentId = currentItem.shopId;

            console.log(values);
            console.log(currentId);
            console.log(values.indexOf(currentId));

            if(values.indexOf(currentId) !== -1) {
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

        onInlineEditStart() {
          console.log("START INLINE EDIT")
            this.showAlert = false;
        },

        addProfile(item) {
            this.value.push(item);
        },

        createNewLineItem() {
            const newId = Utils.createId();

            this.value.push({'id': newId, 'shopId': '', 'currency': '' });

            this.$nextTick(() => {
                this.$refs.shopIdsDataGrid.currentInlineEditId = newId;
                this.$refs.shopIdsDataGrid.enableInlineEdit();
            });
        },

        onSelectionChanged(selection) {
            this.selectedItems = selection;
        },

        onDeleteSelectedItems(itemToDelete) {
            this.value = this.value.filter(currentItem => currentItem.shopId !== itemToDelete.shopId);
            this.$emit('deleted', this.value);
        },
    }
});
