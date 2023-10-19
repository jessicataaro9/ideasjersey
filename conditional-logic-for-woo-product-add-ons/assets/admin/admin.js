jQuery(document).ready(function ($) {

    if (!cfpaGLOBAL) {
        return;
    }

    const AddonsConditionalLogic = function () {
        this.init = function () {

            jQuery(document).on('change', '.wc-pao-addon-conditions-enabled', (function (event) {
                const checkbox = jQuery(event.target);
                const conditionsContainer = checkbox.closest('.wc-pao-row-conditions-settings').find('.wc-pao-addon-conditions');

                checkbox.is(':checked') ? conditionsContainer.show() : conditionsContainer.hide();
            }).bind(this));

            $(document).on('change', '.wc-pao-addon-conditions-field', (function (event) {

                const rule = new ConditionalRule($(event.target).closest('.wc-pao-addon-conditions__rule-row'));

                rule.updateAvailableRelations();

            }).bind(this));

            $(document).on('change', '.wc-pao-addon-conditions-relation', (function (event) {

                const rule = new ConditionalRule($(event.target).closest('.wc-pao-addon-conditions__rule-row'));

                rule.updateValueInputType();

            }).bind(this));

            $(document).on('click', '.wc-pao-addon-conditions__add-new-button', (function (event) {

                event.preventDefault();

                const target = $(event.target);

                const rules = target.closest('.wc-pao-addon-conditions').find('.wc-pao-addon-conditions__rules');
                let template = target.closest('.wc-pao-row-conditions-settings').find('.wc-pao-addon-conditions-rules-template').first().clone();

                template = template.children().css('display', 'flex');

                rules.append(template);

                $('.wc-pao-addon-conditions-field').trigger('change');
            }).bind(this));


            $(document).on('click', '.wc-pao-addon-conditions-rule__remove-button', (function (event) {

                event.preventDefault();

                const target = $(event.target);

                const rules = target.closest('.wc-pao-addon-conditions').find('.wc-pao-addon-conditions__rules');

                if (rules.children().length === 1) {
                    let rule = new ConditionalRule(rules.first());

                    rule.cleanInputs();
                } else {
                    const rule = target.closest('.wc-pao-addon-conditions__rule-row');

                    rule.remove();
                }

            }).bind(this));
        }
    }

    const addonsConditionalLogic = new AddonsConditionalLogic();
    addonsConditionalLogic.init();

    const ConditionalRule = function ($container) {

        this.$valueWrapper = null;
        this.$fieldWrapper = null;
        this.$relationWrapper = null;

        this.updateValueInputType = function () {

            const valueType = this.getSelectedValueType();

            $container.find('.wc-pao-addon-conditions-rule__value-inner').css('display', 'none');
            $container.find('[data-value-input-type=' + valueType + ']').css('display', 'block');
        }

        this.isSelectedRelationAvailableForCheckbox = function () {
            return this.getRelationWrapper().find('option:selected').data('available-for-checkbox') === 'yes';
        }

        this.toggleView = function () {
            this.$container;
        }

        this.getSelectedValueType = function () {
            let valueType = this.getRelationWrapper().find('option:selected').data('value-type');

            if (this.isCheckboxFieldChosen()) {
                valueType = 'checkbox';
            }

            if (this.isFileFieldChosen()) {
                valueType = 'file';
            }

            return valueType;
        }

        this.isCheckboxFieldChosen = function () {
            const fieldType = this.getFieldType();

            return fieldType === 'checkbox' || fieldType === 'multiple_choice';
        }

        this.isFileFieldChosen = function () {
            return this.getFieldType() === 'file_upload';
        }

        this.updateAvailableRelations = function () {

            const relations = this.getRelationWrapper().find('select');
            const options = relations.find('option');

            const supportedRelations = this.getFieldWrapper().find('option:selected').data('supported-relations').split(',');
            let newValue = '';

            $.each(options, function (i, el) {

                let relationType = $(el).attr('value');

                if (supportedRelations.includes('all') || supportedRelations.includes(relationType)) {
                    $(el).show();

                    if (newValue === '') {
                        newValue = relationType;
                    }

                } else {
                    $(el).hide();
                }
            });

            if (relations.find('option:selected').css('display') === 'none') {
                relations.val(newValue);
            }

            relations.trigger('change');
        }

        this.cleanInputs = function () {

            this.getFieldWrapper().find(':input:not([readonly])').val('');
            this.getRelationWrapper().find('select').val('');
            this.getValueWrapper().find('input').val('');

            this.getFieldWrapper().find('select').trigger('change');
        }

        this.getValueWrapper = function () {
            if (!this.$valueWrapper) {
                this.$valueWrapper = $container.find('.wc-pao-addon-conditions-rule__value');
            }

            return this.$valueWrapper;
        }

        this.getRelationWrapper = function () {

            if (!this.$relationWrapper) {
                this.$relationWrapper = $container.find('.wc-pao-addon-conditions-rule__relation');
            }

            return this.$relationWrapper;
        }

        this.getFieldWrapper = function () {
            if (!this.$fieldWrapper) {
                this.$fieldWrapper = $container.find('.wc-pao-addon-conditions-rule__field');
            }

            return this.$fieldWrapper;
        }

        this.getFieldType = function () {
            return this.getFieldWrapper().find('option:selected').data('field-type');
        }
    }

    $('.wc-pao-addon-conditions-field').trigger('change');
    $('.wc-pao-addon-conditions-enabled').trigger('change');
});
