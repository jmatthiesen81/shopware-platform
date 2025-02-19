import template from './sw-customer-detail-order.html.twig';
import './sw-customer-detail-order.scss';

/**
 * @sw-package checkout
 */

const { Criteria } = Shopware.Data;

// eslint-disable-next-line sw-deprecation-rules/private-feature-declarations
export default {
    template,

    inject: [
        'repositoryFactory',
        'acl',
    ],

    props: {
        customer: {
            type: Object,
            required: true,
        },
    },

    data() {
        return {
            isLoading: false,
            activeCustomer: this.customer,
            orders: null,
            term: '',
            sortBy: 'orderDateTime',
            sortDirection: 'DESC',
        };
    },

    computed: {
        orderColumns() {
            return this.getOrderColumns();
        },

        orderRepository() {
            return this.repositoryFactory.create('order');
        },

        emptyTitle() {
            return this.term
                ? this.$tc('sw-customer.detailOrder.emptySearchTitle')
                : this.$tc('sw-customer.detailOrder.emptyTitle');
        },

        currencyFilter() {
            return Shopware.Filter.getByName('currency');
        },

        assetFilter() {
            return Shopware.Filter.getByName('asset');
        },
    },

    watch: {
        customer() {
            this.createdComponent();
        },
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.isLoading = true;

            if (this.orders?.criteria) {
                this.orders.criteria = null;
            }

            this.refreshList();
        },

        onChange(term) {
            this.term = term;
            this.orders.criteria.setPage(1);
            this.orders.criteria.setTerm(term);

            this.refreshList();
        },

        getOrderColumns() {
            return [
                {
                    property: 'orderNumber',
                    label: 'sw-customer.detailOrder.columnNumber',
                    align: 'center',
                },
                {
                    property: 'amountTotal',
                    label: 'sw-customer.detailOrder.columnAmount',
                    align: 'right',
                },
                {
                    property: 'stateMachineState.name',
                    label: 'sw-customer.detailOrder.columnOrderState',
                },
                {
                    property: 'orderDateTime',
                    label: 'sw-customer.detailOrder.columnOrderDate',
                    align: 'center',
                },
            ];
        },

        refreshList() {
            let criteria = new Criteria(1, 25);
            if (!this.orders || !this.orders.criteria) {
                criteria.addFilter(Criteria.equals('order.orderCustomer.customerId', this.customer.id));
            } else {
                criteria = this.orders.criteria;
            }
            criteria.addAssociation('stateMachineState').addAssociation('currency');

            criteria.addSorting(Criteria.sort(this.sortBy, this.sortDirection));

            this.orderRepository.search(criteria).then((orders) => {
                this.orders = orders;
                this.isLoading = false;
            });
        },

        navigateToCreateOrder() {
            this.$router.push({
                name: 'sw.order.create',
                query: {
                    customerId: this.customer.id,
                },
            });
        },
    },
};
