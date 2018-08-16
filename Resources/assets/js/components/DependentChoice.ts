import {component} from 'jquery-ts-components';

@component('DependentChoice')
export default class DependentChoice
{
    private $element: JQuery;
    private $parent: JQuery;
    private $label: JQuery;
    private $dependentOptions: JQuery;
    private $dependentOptionsParent: JQuery;

    public constructor(element: JQuery|HTMLSelectElement|string, parent: JQuery|HTMLSelectElement|string)
    {
        this.$element = $(element);
        this.$parent = $(parent);
        this.$label = $(`label[for="${this.$element.attr('id')}"]`);
        this.$label.addClass('dependent-select-label');
        this.$dependentOptions = this.$element.find('[data-parent-value], [data-parent-values]');
        this.$dependentOptionsParent = this.$dependentOptions.parent();
        this.$parent.on('change dependentSelect.change', this.render.bind(this));
        this.render();
    }

    private render(): void
    {
        const value = this.$element.is('select') ? this.$element.val() : this.$element.find('input:checked').val()
        const $placeholder = this.$element.find('option:first').filter('[value=""], :not([value])');
        this.$dependentOptions.remove();
        const $dependentOptions = this.findPossibleDependentOptions();

        if ($dependentOptions.length) {
            if ($placeholder.length) {
                $placeholder.after($dependentOptions);
            } else {
                this.$dependentOptionsParent.append($dependentOptions);
            }

            this.$element.removeAttr('disabled');
            this.$label.removeClass('is-disabled');
        } else {
            this.$element.attr('disabled', 'disabled');
            this.$label.addClass('is-disabled');
        }

        if (value !== this.$element.val()) {
            this.$element.trigger('dependentSelect.changed');
        }
    }

    private findPossibleDependentOptions(): JQuery
    {
        const parentValue = this.$parent.is('select') ? this.$parent.val() : this.$parent.find('input:checked').val();

        const $dependentOptions = this.$dependentOptions.filter((index: number, dependentOption: HTMLElement) => {
            const parentValues: any[]|undefined = $(dependentOption).data('parentValues');

            if (parentValues !== undefined) {
                return parentValues.indexOf(parentValue) !== -1;
            }

            return $(dependentOption).data('parentValue') === parentValue;
        });

        return $dependentOptions;
    }
}
