import {component} from 'jquery-ts-components';

@component('DependentSelect')
export default class DependentSelect
{
    private $element: JQuery;
    private $parent: JQuery;
    private $label: JQuery;

    public constructor(element: JQuery|HTMLSelectElement|string, parent: JQuery|HTMLSelectElement|string)
    {
        this.$element = $(element);
        this.$parent = $(parent);
        this.$label = $(`label[for="${this.$element.attr('id')}"]`);
        this.$label.addClass('dependent-select-label');
        this.$element.data('dependentOptions', this.$element.find('option[data-parent-value]'));
        this.$parent.on('change dependentSelect.change', this.render.bind(this));
        this.render();
    }

    private render(): void
    {
        const value = this.$element.val();
        const parentValue = this.$parent.val();
        const $placeholder = this.$element.find('option:first').filter('[value=""], :not([value])');
        this.$element.data('dependentOptions').remove();
        const $dependentOptions = this.$element.data('dependentOptions').filter(`[data-parent-value="${parentValue}"]`);

        if ($dependentOptions.length) {
            if ($placeholder.length) {
                $placeholder.after($dependentOptions);
            } else {
                this.$element.append($dependentOptions);
            }

            this.$element.removeAttr('disabled');
            this.$label.removeClass('dependent-select-label--disabled');
        } else {
            this.$element.attr('disabled', 'disabled');
            this.$label.addClass('dependent-select-label--disabled');
        }

        if (value !== this.$element.val()) {
            this.$element.trigger('dependentSelect.changed');
        }
    }
}
