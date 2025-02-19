import template from './sw-condition-date-range.html.twig';
import './sw-condition-date-range.scss';

const { Component } = Shopware;
const { mapPropertyErrors } = Component.getComponentHelper();

/**
 * @public
 * @sw-package fundamentals@after-sales
 * @description Condition for the DateRangeRule. This component must a be child of sw-condition-tree.
 * @status prototype
 * @example-type code-only
 * @component-example
 * <sw-condition-date-range :condition="condition" :level="0"></sw-condition-date-range>
 */
Component.extend('sw-condition-date-range', 'sw-condition-base', {
    template,

    computed: {
        selectValues() {
            return [
                {
                    label: this.$tc('global.sw-condition.condition.withTime'),
                    value: true,
                },
                {
                    label: this.$tc('global.sw-condition.condition.withoutTime'),
                    value: false,
                },
            ];
        },

        useTime: {
            get() {
                this.ensureValueExist();
                if (typeof this.condition.value.useTime === 'undefined') {
                    // eslint-disable-next-line vue/no-side-effects-in-computed-properties
                    this.condition.value = {
                        ...this.condition.value,
                        useTime: false,
                    };
                }

                return this.condition.value.useTime;
            },
            set(useTime) {
                this.ensureValueExist();
                this.condition.value = { ...this.condition.value, useTime };
            },
        },

        fromDate: {
            get() {
                this.ensureValueExist();
                return this.condition.value.fromDate || null;
            },
            set(fromDate) {
                this.ensureValueExist();

                // eslint-disable-next-line max-len
                const date =
                    this.isDateTime === 'datetime' ? fromDate.replace('.000Z', '+00:00') : fromDate.concat('+00:00');
                this.condition.value = {
                    ...this.condition.value,
                    fromDate: date,
                };
            },
        },

        toDate: {
            get() {
                this.ensureValueExist();
                return this.condition.value.toDate || null;
            },
            set(toDate) {
                this.ensureValueExist();

                const date = this.isDateTime === 'datetime' ? toDate.replace('.000Z', '+00:00') : toDate.concat('+00:00');
                this.condition.value = {
                    ...this.condition.value,
                    toDate: date,
                };
            },
        },

        isDateTime() {
            return this.useTime ? 'datetime' : 'date';
        },

        ...mapPropertyErrors('condition', [
            'value.useTime',
            'value.fromDate',
            'value.toDate',
        ]),

        currentError() {
            return this.conditionValueUseTimeError || this.conditionValueFromDateError || this.conditionValueToDateError;
        },
    },
});
