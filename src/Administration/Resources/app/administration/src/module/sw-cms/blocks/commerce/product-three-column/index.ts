/**
 * @private
 * @sw-package discovery
 */
Shopware.Component.register('sw-cms-preview-product-three-column', () => import('./preview'));
/**
 * @private
 * @sw-package discovery
 */
Shopware.Component.register('sw-cms-block-product-three-column', () => import('./component'));

/**
 * @private
 * @sw-package discovery
 */
Shopware.Service('cmsService').registerCmsBlock({
    name: 'product-three-column',
    label: 'sw-cms.blocks.commerce.productThreeColumn.label',
    category: 'commerce',
    component: 'sw-cms-block-product-three-column',
    previewComponent: 'sw-cms-preview-product-three-column',
    defaultConfig: {
        marginBottom: '20px',
        marginTop: '20px',
        marginLeft: null,
        marginRight: null,
        sizingMode: 'boxed',
    },
    slots: {
        left: 'product-box',
        center: 'product-box',
        right: 'product-box',
    },
});
