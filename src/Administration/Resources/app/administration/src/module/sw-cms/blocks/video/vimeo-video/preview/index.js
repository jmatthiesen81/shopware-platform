import template from './sw-cms-preview-vimeo-video.html.twig';
import './sw-cms-preview-vimeo-video.scss';

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
