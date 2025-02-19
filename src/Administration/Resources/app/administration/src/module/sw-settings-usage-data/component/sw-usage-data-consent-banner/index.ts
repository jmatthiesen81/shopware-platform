import template from './sw-usage-data-consent-banner.html.twig';
import './sw-usage-data-consent-banner.scss';

/**
 * @sw-package data-services
 *
 * @private
 */
export default Shopware.Component.wrapComponentConfig({
    name: 'sw-usage-data-consent-banner',

    template,

    inject: [
        'acl',
        'usageDataService',
    ],

    props: {
        canBeHidden: {
            type: Boolean,
            required: false,
            default: false,
        },
    },

    data(): { showLinkToSettingsPage: boolean; showThankYouBanner: boolean } {
        return {
            showLinkToSettingsPage: false,
            showThankYouBanner: false,
        };
    },

    computed: {
        isAccepted: {
            get() {
                return Shopware.Store.get('usageData').isConsentGiven;
            },
            set(isConsentGiven: boolean) {
                Shopware.Store.get('usageData').updateIsConsentGiven(isConsentGiven);
            },
        },

        isHidden() {
            return Shopware.Store.get('usageData').isBannerHidden;
        },

        hasSufficientPrivileges() {
            return this.acl.can('system.system_config');
        },
    },

    methods: {
        async onReject() {
            await this.usageDataService.revokeConsent();

            this.isAccepted = false;
        },

        async onAccept() {
            await this.usageDataService.acceptConsent();

            this.showThankYouBanner = true;
            this.isAccepted = true;
        },

        async onHide() {
            await this.usageDataService.hideBanner();
            this.showLinkToSettingsPage = true;

            Shopware.Store.get('usageData').hideBanner();
        },

        onClose(): void {
            this.showLinkToSettingsPage = false;
            this.showThankYouBanner = false;
        },
    },
});
