/**
 * @sw-package framework
 *
 * @private
 */
export default function initMenuItems(): void {
    Shopware.ExtensionAPI.handle('menuItemAdd', async (menuItemConfig, additionalInformation) => {
        const extension = Object.values(Shopware.Store.get('extensions').extensionsState).find((ext) =>
            ext.baseUrl.startsWith(additionalInformation._event_.origin),
        );

        if (!extension) {
            throw new Error(`Extension with the origin "${additionalInformation._event_.origin}" not found.`);
        }

        await Shopware.Store.get('extensionSdkModules')
            .addModule({
                heading: menuItemConfig.label,
                locationId: menuItemConfig.locationId,
                displaySearchBar: menuItemConfig.displaySearchBar!,
                displaySmartBar: menuItemConfig.displaySmartBar,
                baseUrl: extension.baseUrl,
            })
            .then((moduleId) => {
                if (typeof moduleId !== 'string') {
                    return;
                }

                Shopware.Store.get('menuItem').addMenuItem({
                    ...menuItemConfig,
                    moduleId,
                });
            });
    });

    Shopware.ExtensionAPI.handle('menuCollapse', () => {
        Shopware.Store.get('adminMenu').collapseSidebar();
    });

    Shopware.ExtensionAPI.handle('menuExpand', () => {
        Shopware.Store.get('adminMenu').expandSidebar();
    });
}
