import template from './sw-cms-preview-image-simple-grid.html.twig';
import './sw-cms-preview-image-simple-grid.scss';

/**
 * @private
 * @sw-package discovery
 */
export default {
    template,

    computed: {
        assetFilter() {
            return Shopware.Filter.getByName('asset');
        },
    },
};
