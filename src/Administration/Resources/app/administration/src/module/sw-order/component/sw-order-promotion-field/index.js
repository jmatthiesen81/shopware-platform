import './sw-order-promotion-field.scss';
import template from './sw-order-promotion-field.html.twig';

/**
 * @sw-package checkout
 */

const { Store } = Shopware;
const { ChangesetGenerator } = Shopware.Data;

// eslint-disable-next-line sw-deprecation-rules/private-feature-declarations
export default {
    template,

    inject: {
        swOrderDetailOnLoadingChange: {
            from: 'swOrderDetailOnLoadingChange',
            default: null,
        },
        swOrderDetailOnError: {
            from: 'swOrderDetailOnError',
            default: null,
        },
        swOrderDetailOnReloadEntityData: {
            from: 'swOrderDetailOnReloadEntityData',
            default: null,
        },
        repositoryFactory: {
            from: 'repositoryFactory',
            default: null,
        },
        orderService: {
            from: 'orderService',
            default: null,
        },
        acl: {
            from: 'acl',
            default: null,
        },
    },

    emits: [
        'loading-change',
        'error',
        'reload-entity-data',
    ],

    mixins: [
        'notification',
    ],

    props: {
        isLoading: {
            type: Boolean,
            required: false,
            default: false,
        },
    },

    data() {
        return {
            promotionError: null,
            disabledAutoPromotions: false,
        };
    },

    computed: {
        order: () => Store.get('swOrderDetail').order,

        versionContext: () => Store.get('swOrderDetail').versionContext,

        orderLineItemRepository() {
            return this.repositoryFactory.create('order_line_item');
        },

        hasLineItem() {
            return this.order.lineItems.filter((item) => item.hasOwnProperty('id')).length > 0;
        },

        currency() {
            return this.order.currency;
        },

        manualPromotions() {
            return this.order.lineItems.filter((item) => item.type === 'promotion' && item.referencedId !== null);
        },

        automaticPromotions() {
            return this.order.lineItems.filter((item) => item.type === 'promotion' && item.referencedId === null);
        },

        promotionCodeTags: {
            get() {
                return this.manualPromotions.map((item) => item.payload);
            },

            set(newValue) {
                const old = this.manualPromotions;

                this.promotionError = null;

                if (newValue.length < old.length) {
                    return;
                }

                const promotionCodeLength = old.length;
                const latestTag = newValue[promotionCodeLength];

                if (newValue.length > old.length) {
                    this.onSubmitCode(latestTag.code);
                }

                if (promotionCodeLength > 0 && latestTag.isInvalid) {
                    this.promotionError = {
                        detail: this.$tc('sw-order.createBase.textInvalidPromotionCode'),
                    };
                }
            },
        },

        hasAutomaticPromotions() {
            return this.automaticPromotions.length > 0;
        },

        changesetGenerator() {
            return new ChangesetGenerator();
        },

        hasOrderUnsavedChanges() {
            return this.changesetGenerator.generate(this.order).changes !== null;
        },
    },

    watch: {
        // Validate if switch can be toggled
        disabledAutoPromotions(newState, oldState) {
            // To prevent recursion when value is set in next tick
            if (oldState === this.hasAutomaticPromotions) return;

            this.toggleAutomaticPromotions(newState);
        },

        automaticPromotions() {
            // Sync value with database
            this.disabledAutoPromotions = !this.hasAutomaticPromotions;
        },
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.disabledAutoPromotions = !this.hasAutomaticPromotions;
        },

        deleteAutomaticPromotions() {
            if (this.automaticPromotions.length === 0) {
                return Promise.resolve();
            }

            const deletionPromises = [];

            this.automaticPromotions.forEach((promotion) => {
                deletionPromises.push(this.orderLineItemRepository.delete(promotion.id, this.versionContext));
            });

            return Promise.all(deletionPromises)
                .then(() => {
                    this.automaticPromotions.forEach((promotion) => {
                        this.createNotificationSuccess({
                            message: this.$tc(
                                'sw-order.detailBase.textPromotionRemoved',
                                {
                                    promotion: promotion.label,
                                },
                                0,
                            ),
                        });
                    });
                })
                .catch((error) => {
                    this.$emit('loading-change', false);
                    if (this.swOrderDetailOnLoadingChange) {
                        this.swOrderDetailOnLoadingChange(false);
                    }

                    this.$emit('error', error);
                    if (this.swOrderDetailOnError) {
                        this.swOrderDetailOnError(error);
                    }
                });
        },

        toggleAutomaticPromotions(state) {
            this.$emit('loading-change', true);
            if (this.swOrderDetailOnLoadingChange) {
                this.swOrderDetailOnLoadingChange(true);
            }

            // Throw notification warning and reset switch state
            if (this.hasOrderUnsavedChanges) {
                this.$emit('loading-change', false);
                if (this.swOrderDetailOnLoadingChange) {
                    this.swOrderDetailOnLoadingChange(false);
                }
                this.handleUnsavedOrderChangesResponse();
                this.$nextTick(() => {
                    this.disabledAutoPromotions = !state;
                });
                return;
            }

            this.deleteAutomaticPromotions()
                .then(() => {
                    return this.orderService.toggleAutomaticPromotions(this.order.id, this.order.versionId, state);
                })
                .then((response) => {
                    this.handlePromotionResponse(response);
                    this.$emit('reload-entity-data');
                    if (this.swOrderDetailOnReloadEntityData) {
                        this.swOrderDetailOnReloadEntityData();
                    }
                })
                .catch((error) => {
                    this.$emit('loading-change', false);
                    if (this.swOrderDetailOnLoadingChange) {
                        this.swOrderDetailOnLoadingChange(false);
                    }
                    this.$emit('error', error);
                    if (this.swOrderDetailOnError) {
                        this.swOrderDetailOnError(error);
                    }
                });
        },

        onSubmitCode(code) {
            this.$emit('loading-change', true);
            if (this.swOrderDetailOnLoadingChange) {
                this.swOrderDetailOnLoadingChange(true);
            }

            if (this.hasOrderUnsavedChanges) {
                this.$emit('loading-change', false);
                if (this.swOrderDetailOnLoadingChange) {
                    this.swOrderDetailOnLoadingChange(false);
                }
                this.handleUnsavedOrderChangesResponse();
                return;
            }

            this.orderService
                .addPromotionToOrder(this.order.id, this.order.versionId, code)
                .then((response) => {
                    this.handlePromotionResponse(response);
                    this.$emit('reload-entity-data');
                    if (this.swOrderDetailOnReloadEntityData) {
                        this.swOrderDetailOnReloadEntityData();
                    }
                })
                .catch((error) => {
                    this.$emit('loading-change', false);
                    if (this.swOrderDetailOnLoadingChange) {
                        this.swOrderDetailOnLoadingChange(false);
                    }
                    this.$emit('error', error);
                    if (this.swOrderDetailOnError) {
                        this.swOrderDetailOnError(error);
                    }
                });
        },

        handlePromotionResponse(response) {
            Object.values(response.data.errors).forEach((value) => {
                switch (value.level) {
                    case 0: {
                        this.createNotificationSuccess({
                            message: value.message,
                        });
                        break;
                    }

                    case 10: {
                        this.createNotificationWarning({
                            message: value.message,
                        });
                        break;
                    }

                    default: {
                        this.createNotificationError({
                            message: value.message,
                        });
                        break;
                    }
                }
            });
        },

        handleUnsavedOrderChangesResponse() {
            this.createNotificationWarning({
                message: this.$tc('sw-order.detailBase.textUnsavedChanges', 0),
            });
        },

        onRemoveExistingCode(removedItem) {
            this.$emit('loading-change', true);

            if (this.hasOrderUnsavedChanges) {
                this.$emit('loading-change', false);
                if (this.swOrderDetailOnLoadingChange) {
                    this.swOrderDetailOnLoadingChange(false);
                }
                this.handleUnsavedOrderChangesResponse();
                return;
            }

            const lineItem = this.order.lineItems.find((item) => {
                return item.type === 'promotion' && item.payload.code === removedItem.code;
            });

            this.orderLineItemRepository
                .delete(lineItem.id, this.versionContext)
                .then(() => {
                    this.$emit('reload-entity-data');
                    if (this.swOrderDetailOnReloadEntityData) {
                        this.swOrderDetailOnReloadEntityData();
                    }
                })
                .catch((error) => {
                    this.$emit('loading-change', false);
                    if (this.swOrderDetailOnLoadingChange) {
                        this.swOrderDetailOnLoadingChange(false);
                    }
                    this.$emit('error', error);
                    if (this.swOrderDetailOnError) {
                        this.swOrderDetailOnError(error);
                    }
                });
        },

        getLineItemByPromotionCode(code) {
            return this.order.lineItems.find((item) => {
                return item.type === 'promotion' && item.payload.code === code;
            });
        },
    },
};
