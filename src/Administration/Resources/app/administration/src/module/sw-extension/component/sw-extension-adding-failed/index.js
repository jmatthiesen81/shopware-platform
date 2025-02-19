import template from './sw-extension-adding-failed.html.twig';
import './sw-extension-adding-failed.scss';

/**
 * @sw-package checkout
 * @private
 */
export default {
    template,

    inject: [
        'shopwareExtensionService',
    ],

    emits: ['close'],

    props: {
        extensionName: {
            type: String,
            required: true,
        },

        title: {
            type: String,
            required: false,
            default: null,
        },

        detail: {
            type: String,
            required: false,
            default: null,
        },

        documentationLink: {
            type: String,
            required: false,
            default: null,
        },
    },

    computed: {
        myExtensions() {
            return Shopware.Store.get('shopwareExtensions').myExtensions;
        },

        extension() {
            return this.myExtensions.data.find((extension) => {
                return extension.name === this.extensionName;
            });
        },

        isRent() {
            return this.extension?.storeLicense?.variant === this.shopwareExtensionService.EXTENSION_VARIANT_TYPES.RENT;
        },

        headline() {
            if (this.extension === undefined) {
                return this.$tc('sw-extension-store.component.sw-extension-adding-failed.titleFailure');
            }

            return this.$tc('sw-extension-store.component.sw-extension-adding-failed.installationFailed.titleFailure');
        },

        text() {
            if (this.extension === undefined) {
                return this.$tc('sw-extension-store.component.sw-extension-adding-failed.textProblem');
            }

            return this.$tc('sw-extension-store.component.sw-extension-adding-failed.installationFailed.textProblem');
        },
    },
};
