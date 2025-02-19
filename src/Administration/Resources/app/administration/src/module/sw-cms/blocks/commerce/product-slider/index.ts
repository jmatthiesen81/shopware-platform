/**
 * @private
 * @sw-package discovery
 */
Shopware.Component.register('sw-cms-preview-product-slider', () => import('./preview'));
/**
 * @private
 * @sw-package discovery
 */
Shopware.Component.register('sw-cms-block-product-slider', () => import('./component'));

/**
 * @private
 * @sw-package discovery
 */
Shopware.Service('cmsService').registerCmsBlock({
    name: 'product-slider',
    label: 'sw-cms.blocks.commerce.productSlider.label',
    category: 'commerce',
    component: 'sw-cms-block-product-slider',
    previewComponent: 'sw-cms-preview-product-slider',
    defaultConfig: {
        marginBottom: '20px',
        marginTop: '20px',
        marginLeft: null,
        marginRight: null,
        sizingMode: 'boxed',
    },
    slots: {
        productSlider: 'product-slider',
    },
});
