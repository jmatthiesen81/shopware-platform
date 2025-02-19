/**
 * @sw-package framework
 */

import { defineComponent } from 'vue';

/**
 * @private
 */
export default Shopware.Mixin.register(
    'sw-inline-snippet',
    defineComponent({
        computed: {
            swInlineSnippetLocale(): string {
                return Shopware.Store.get('session').currentLocale as unknown as string;
            },

            swInlineSnippetFallbackLocale(): string {
                return Shopware.Context.app.fallbackLocale as unknown as string;
            },
        },

        methods: {
            getInlineSnippet(value: { [key: string]: string }) {
                if (Shopware.Utils.types.isEmpty(value)) {
                    return '';
                }
                if (value[this.swInlineSnippetLocale]) {
                    return value[this.swInlineSnippetLocale];
                }
                if (value[this.swInlineSnippetFallbackLocale]) {
                    return value[this.swInlineSnippetFallbackLocale];
                }
                if (Shopware.Utils.types.isObject(value)) {
                    const locale = Object.keys(value).find((key) => {
                        return value[key] !== '';
                    });

                    if (locale !== undefined) {
                        return value[locale];
                    }
                }

                return value;
            },
        },
    }),
);
