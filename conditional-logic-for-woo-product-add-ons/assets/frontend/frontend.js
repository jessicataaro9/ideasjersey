const CFPA_ConditionalLogic = function () {
    this.fields = null;

    this.init = function () {
        this.selectedVariation = 0;

        jQuery(document).on('woocommerce-product-addons-update', (function () {
            this.triggerConditionalRules();
        }).bind(this));

        // Variation dependencies
        jQuery(document).on('found_variation', (function (e, variation) {
            this.selectedVariation = variation['variation_id'];
            this.triggerConditionalRules();
        }).bind(this));
        jQuery(document).on('reset_data', (function (e, variation) {
            this.selectedVariation = 0;
            this.triggerConditionalRules();
        }).bind(this));

        setTimeout((function () {
            if (window.WC_PAO && window.WC_PAO.initialized_forms) {
                window.WC_PAO.initialized_forms.forEach((function (form) {

                    form.$el.on('updated_addons', (function () {
                        this.triggerConditionalRules();
                    }).bind(this)).trigger('updated_addons');

                }).bind(this));
            }
        }).bind(this), 1);

    }

    this.getFields = function (force = false) {
        if (this.fields !== null && !force) {
            return this.fields;
        }

        let fields = {};

        jQuery.each(jQuery('.wc-pao-addons-container .wc-pao-addon-container'), function (i, el) {

            const field = new CFPA_AddonField(jQuery(el));

            if (field.getSlug()) {
                fields[field.getSlug()] = field;
            }
        });

        this.fields = fields;

        return this.field;
    }

    this.triggerConditionalRules = function () {
        this.getFields();

        let fields = this.getFields();

        if (fields) {
            for (const fieldKey in fields) {
                if (fields.hasOwnProperty(fieldKey)) {
                    const field = fields[fieldKey];

                    if (field.hasConditionalRules() || field.hasVariationsDependency()) {
                        field.processConditions(this.selectedVariation);
                    }
                }
            }
        }
    }
}

const CFPA_AddonField = function ($wrapper) {

    this.$wrapper = $wrapper;
    this.data = null;

    this.hasConditionalRules = function () {
        return !!this.getConditionalRules();
    }

    this.hasVariationsDependency = function () {
        return this.data.productVariations.length > 0;
    }

    this.processConditions = function (selectedVariation = 0) {

        const conditionalRule = new CFPA_AddonConditionalRule(this.getData(), selectedVariation);

        const result = conditionalRule.run();

        if (this.getData().conditionAction === 'hide') {
            if (result) {
                this.hide();
            } else {
                this.show();
            }
        }

        if (this.getData().conditionAction === 'show') {
            if (result) {
                this.show();
            } else {
                this.hide();
            }
        }
    }

    this.getData = function () {

        if (this.data) {
            return this.data;
        }

        const data = {
            'conditionalRules': null,
            'productVariations': [],
            'conditionAction': null,
            'conditionMatchType': null,
            'type': null,
            'slug': null,
        }

        const conditionalDataEl = this.$wrapper.find('.wc-pao-addon-condition-data');

        if (conditionalDataEl.length) {
            data.conditionalRules = conditionalDataEl.data('addon-conditional-rules');
            data.productVariations = conditionalDataEl.data('addon-product-variations');
            data.conditionAction = conditionalDataEl.data('addon-condition-action');
            data.conditionMatchType = conditionalDataEl.data('addon-condition-match-type');
            data.type = conditionalDataEl.data('addon-type');
            data.slug = conditionalDataEl.data('addon-slug');
        }

        this.data = data;

        return this.data;
    }

    this.getType = function () {
        return this.getData().type;
    }

    this.getSlug = function () {
        return this.getData().slug;
    }

    this.getConditionalRules = function () {
        return this.getData().conditionalRules;
    }

    this.getSelectedOptionNumbers = function () {

        const selectedOptionNumbers = [];

        if (this.getType() === 'checkbox') {
            // Grab options that have not empty values. Can be either input[type=radio] or input[type=checkbox]
            jQuery.each(this.$wrapper.find('input[value!=""][value]'), (function (i, el) {
                if (jQuery(el).is(':checked')) {
                    // Options count from 1, but Jquery indexes start from 0
                    selectedOptionNumbers.push(++i);
                }
            }).bind(this));
        }

        if (this.getType() === 'multiple_choice') {
            // Grab options that have not empty values. Can be either input[type=radio] or select
            jQuery.each(this.$wrapper.find('input[type=radio][value!=""][value],option[value!=""][value]'), (function (i, el) {
                if (jQuery(el).is(':selected') || jQuery(el).is(':checked')) {
                    // Options count from 1, but Jquery indexes start from 0
                    selectedOptionNumbers.push(++i);
                }
            }).bind(this));
        }

        return selectedOptionNumbers;
    }

    this.getValue = function () {
        switch (this.getType()) {
            case 'custom_text':
                return this.$wrapper.find('input').val();
            case 'custom_textarea':
                return this.$wrapper.find('textarea').val();
            case 'custom_price':
            case 'input_multiplier':
                return parseFloat(this.$wrapper.find('input').val());
            case 'file_upload':
                return this.$wrapper.find('input[type=file]').val() ? 'selected' : false;
            default:
                return '';
        }
    }

    this.isRequired = function () {
        return this.$wrapper.hasClass('wc-pao-required-addon');
    }

    this.toggleRequired = function (remove = true) {
        let input;

        switch (this.getType()) {
            case 'multiple_choice':
                input = this.$wrapper.find('select');
                break;
            case 'custom_textarea':
                input = this.$wrapper.find('textarea');
                break;
            default:
                input = this.$wrapper.find('input');
                break;
        }

        if (input) {
            let restrictions = input.data('restrictions');

            if (remove) {

                if (restrictions && restrictions.required) {
                    restrictions.required = 'no';

                    input.attr('data-restrictions', JSON.stringify(restrictions));
                }

                input.attr('disabled', 'disabled');
                input.prop('disabled', true);
            } else {

                if (restrictions) {
                    restrictions.required = 'yes';
                    input.attr('data-restrictions', JSON.stringify(restrictions));
                }

                input.removeAttr('disabled');
                input.prop('disabled', false);
            }
        }
    }

    this.clearValue = function () {
        switch (this.getType()) {
            case 'custom_textarea':
                this.$wrapper.find('textarea').val('');
                break;
            case 'custom_text':
            case 'custom_price':
            case 'input_multiplier':
            case 'file_upload':
                this.$wrapper.find('input').val('');
                break;
            case 'multiple_choice':
                this.$wrapper.find('select').val('').trigger('change');
                this.$wrapper.find('input').prop('checked', false);
                break;
            case 'checkbox':
                this.$wrapper.find('input[type=checkbox]').prop('checked', false).removeAttr('checked');
                break;
        }

        setTimeout((function () {
            this.$wrapper.find('input').first().trigger('change');
        }).bind(this), 100);
    }

    this.hide = function () {

        if (!this.$wrapper.is(':hidden')) {
            this.$wrapper.hide();

            this.clearValue();

            if (this.isRequired()) {
                this.toggleRequired(true);
            }
        }
    }

    this.show = function () {

        if (this.$wrapper.is(':hidden')) {
            this.$wrapper.show();

            if (this.isRequired()) {
                this.toggleRequired(false);
            }
        }
    }
}

const CFPA_AddonConditionalRule = function (data, selectedVariation) {

    this.run = function () {

        // Check for selected variation
        if (data.productVariations.length > 0) {
            if (!data.productVariations.includes(selectedVariation)) {
                return false;
            }
        }

        if (data.conditionalRules.length < 1) {
            return true;
        }

        if (data.conditionMatchType === 'all') {
            return data.conditionalRules.every(this.checkCondition.bind(this));

        } else if (data.conditionMatchType === 'any') {
            return data.conditionalRules.some(this.checkCondition.bind(this));
        }

        return false;
    }

    this.checkCondition = function (rule) {
        const field = document.cfpa_ConditionalLogic.fields[rule.field];

        if (!field) {
            return;
        }

        if (rule.type === 'checkbox' || rule.type === 'multiple_choice') {
            const isIncluded = field.getSelectedOptionNumbers().includes(rule.value);

            // Relation can be either 'is' or 'is_not'. Return true for is_not if value is not included.
            return rule.relation === 'is' ? isIncluded : !isIncluded;
        }

        if (rule.type === 'file_upload') {
            const isSelected = field.getValue() === 'selected';

            // Relation can be either 'is' or 'is_not'. Return true for is_not if value is not selected.
            return rule.relation === 'is' ? isSelected : !isSelected;
        }

        if (rule.type === 'custom_text' || rule.type === 'custom_textarea') {

            const fieldValue = field.getValue();

            if (rule.relation === 'is') {
                return fieldValue === rule.value;
            }
            if (rule.relation === 'is_not') {
                return fieldValue !== rule.value;
            }
            if (rule.relation === 'is_empty') {
                return fieldValue === '';
            }
            if (rule.relation === 'is_not_empty') {
                return fieldValue !== '';
            }
            if (rule.relation === 'text_contains') {
                return fieldValue.includes(rule.value);
            }
            if (rule.relation === 'text_does_not_contain') {
                return !fieldValue.includes(rule.value);
            }
            if (rule.relation === 'text_start_with') {
                return fieldValue.startsWith(rule.value);
            }
            if (rule.relation === 'text_not_start_with') {
                return !fieldValue.startsWith(rule.value);
            }
            if (rule.relation === 'text_end_with') {
                return fieldValue.endsWith(rule.value);
            }
            if (rule.relation === 'text_not_end_with') {
                return !fieldValue.endsWith(rule.value);
            }
        }

        if (rule.type === 'custom_price' || rule.type === 'input_multiplier') {

            const fieldValue = field.getValue();

            if (rule.relation === 'is') {
                return fieldValue === rule.value;
            }

            if (rule.relation === 'is_not') {
                return fieldValue !== rule.value;
            }

            if (rule.relation === 'is_empty') {
                return fieldValue === '';
            }

            if (rule.relation === 'is_not_empty') {
                return fieldValue !== '';
            }

            if (rule.relation === 'is_greater_than') {
                return fieldValue > rule.value;
            }

            if (rule.relation === 'is_less_than') {
                return fieldValue < rule.value;
            }

            if (rule.relation === 'is_greater_than_or_equal') {
                return fieldValue >= rule.value;
            }

            if (rule.relation === 'is_less_than_or_equal') {
                return fieldValue <= rule.value;
            }
        }

    }
}

jQuery(document).ready(function () {
    document.cfpa_ConditionalLogic = new CFPA_ConditionalLogic();
    document.cfpa_ConditionalLogic.init();
});