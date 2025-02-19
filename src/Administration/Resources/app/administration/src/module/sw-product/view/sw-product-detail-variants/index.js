/*
 * @sw-package inventory
 */

import template from './sw-product-detail-variants.html.twig';
import './sw-product-detail-variants.scss';

const { Criteria, EntityCollection } = Shopware.Data;
const { uniqBy } = Shopware.Utils.array;

// eslint-disable-next-line sw-deprecation-rules/private-feature-declarations
export default {
    template,

    inject: [
        'repositoryFactory',
        'acl',
    ],

    data() {
        return {
            variantListHasContent: false,
            activeModal: '',
            isLoading: true,
            productEntity: {},
            configuratorSettingsRepository: {},
            groups: [],
            productEntityLoaded: false,
            propertiesAvailable: true,
            showAddPropertiesModal: false,
            defaultTab: 'all',
            activeTab: 'all',
            configSettingGroups: [],
        };
    },

    computed: {
        product() {
            return Shopware.Store.get('swProductDetail').product;
        },

        variants() {
            return Shopware.Store.get('swProductDetail').variants;
        },

        isStoreLoading() {
            return Shopware.Store.get('swProductDetail').isLoading;
        },

        contextLanguageId() {
            return Shopware.Store.get('context').api.languageId;
        },

        productRepository() {
            return this.repositoryFactory.create('product');
        },

        groupRepository() {
            return this.repositoryFactory.create('property_group');
        },

        propertyRepository() {
            return this.repositoryFactory.create('property_group_option');
        },

        productProperties() {
            return this.isChild && this.product?.properties?.length <= 0
                ? this.parentProduct.properties
                : this.product.properties;
        },

        currentProductStates() {
            return this.activeTab.split(',');
        },

        assetFilter() {
            return Shopware.Filter.getByName('asset');
        },
    },

    watch: {
        isStoreLoading: {
            handler() {
                if (this.isStoreLoading === false) {
                    this.loadData();
                }
            },
        },

        contextLanguageId: {
            handler() {
                this.$refs.generatedVariants.getList();
            },
        },
    },

    created() {
        this.createdComponent();
    },

    mounted() {
        this.mountedComponent();
    },

    methods: {
        createdComponent() {
            this.checkIfPropertiesExists();
        },

        mountedComponent() {
            this.loadData();
        },

        setActiveTab(tabName) {
            this.activeTab = tabName;
        },

        loadData() {
            if (!this.isStoreLoading) {
                this.loadOptions()
                    .then(() => {
                        return this.loadGroups();
                    })
                    .then(() => {
                        return this.loadConfigSettingGroups();
                    });
            }
        },

        async loadConfigSettingGroups() {
            const groupIds = uniqBy(this.productEntity.configuratorSettings, 'option.groupId').map(
                (group) => group.option.groupId,
            );

            const criteria = new Criteria(1, null);
            if (groupIds.length) {
                criteria.addFilter(Criteria.equalsAny('id', groupIds));
            }

            this.configSettingGroups = await this.groupRepository.search(criteria);
        },

        loadOptions() {
            return new Promise((resolve) => {
                const criteria = new Criteria(1, 25);

                criteria.addAssociation('configuratorSettings.option');
                criteria.addAssociation('prices');

                this.productRepository.get(this.product.id, Shopware.Context.api, criteria).then((product) => {
                    this.productEntity = product;
                    this.productEntityLoaded = true;

                    resolve();
                });
            });
        },

        loadGroups() {
            return new Promise((resolve) => {
                this.$nextTick().then(() => {
                    const groupCriteria = new Criteria(1, null);

                    this.groupRepository.search(groupCriteria).then((searchResult) => {
                        this.groups = searchResult;
                        resolve();
                    });
                });
            });
        },

        updateVariations() {
            // Reset filter
            this.$refs.generatedVariants.includeOptions = [];
            this.$refs.generatedVariants.filterWindowOpen = false;

            // get new filter options
            this.loadOptions()
                .then(() => {
                    return this.loadGroups();
                })
                .then(() => {
                    this.$refs.generatedVariants.getFilterOptions();
                    this.$refs.generatedVariants.resetFilterOptions();
                });
        },

        updateVariantListHasContent(variantList) {
            // Check for empty search or filter results
            const isFilterActive = this.$refs.generatedVariants.includeOptions.length > 0;
            const searchTerm = this.$route.query ? this.$route.query.term : '';

            this.variantListHasContent = variantList.length > 0 || searchTerm || isFilterActive;
            this.isLoading = false;
        },

        openModal(value) {
            this.activeModal = value;
        },

        onConfigurationClosed() {
            this.loadData();
            this.activeModal = '';
        },

        checkIfPropertiesExists() {
            this.propertyRepository.search(new Criteria(1, 1)).then((res) => {
                this.propertiesAvailable = res.total > 0;
            });
        },

        openAddPropertiesModal() {
            if (!this.propertiesAvailable) {
                this.$router.push({ name: 'sw.property.index' });
            } else {
                this.updateNewProperties();
                this.showAddPropertiesModal = true;
            }
        },

        closeAddPropertiesModal() {
            this.showAddPropertiesModal = false;
            this.updateNewProperties();
        },

        updateNewProperties() {
            this.newProperties = new EntityCollection(
                this.productProperties.source,
                this.productProperties.entity,
                this.productProperties.context,
                Criteria.fromCriteria(this.productProperties.criteria),
                this.productProperties,
                this.productProperties.total,
                this.productProperties.aggregations,
            );
        },

        onCancelAddPropertiesModal() {
            this.closeAddPropertiesModal();
        },

        onSaveAddPropertiesModal(newProperties) {
            this.closeAddPropertiesModal();

            if (newProperties.length <= 0) {
                return;
            }

            this.productProperties.splice(0, this.productProperties.length, ...newProperties);
        },
    },
};
