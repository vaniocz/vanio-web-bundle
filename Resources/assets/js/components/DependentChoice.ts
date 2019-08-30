import {component} from 'jquery-ts-components';

interface DependentChoiceOptions
{
    parent: JQuery|HTMLElement|string;
    listen?: JQuery|HTMLElement|string;
}

@component('DependentChoice')
export default class DependentChoice
{
    private $element: JQuery;
    private options: DependentChoiceOptions;
    private $label: JQuery;
    private $dependentOptions: JQuery;
    private $dependentOptionsParent: JQuery;

    public constructor(
        element: JQuery|HTMLSelectElement|string,
        options: DependentChoiceOptions|string
    ) {
        this.$element = $(element);
        this.options = typeof options === 'string' ? {parent: options} : options;
        this.$label = $(`label[for="${this.$element.attr('id')}"]`);
        this.$label.addClass('dependent-select-label');
        this.$dependentOptions = this.$element.find('[data-parent-value], [data-parent-values]');
        this.$dependentOptionsParent = this.$dependentOptions.parent();

        if (typeof this.options.listen === 'string') {
            $(document).on('change dependent_choice.change', this.options.listen, this.render.bind(this));
        } else {
            $(this.options.listen || this.options.parent).on('change dependent_choice.change', this.render.bind(this));
        }

        this.render();
    }

    public get $parent(): JQuery
    {
        return $(this.options.parent);
    }

    public get value(): string|number|string[]|undefined
    {
        return this.$element.is('select') ? this.$element.val() : this.$element.find('input:checked').val();
    }

    private render(): void
    {
        const value = this.value;
        const $placeholder = this.$element.find('option:first').filter('[value=""], :not([value])');
        const $dependentOptions = this.findPossibleDependentOptions();
        const $selectedOption = $dependentOptions.filter(this.$element.is('select') ? ':selected' : 'input:checked');
        this.$dependentOptions.not($selectedOption).remove();
        this.$element
            .removeAttr('disabled')
            .removeAttr('readonly');
        this.$label.removeClass('is-disabled is-readonly');

        if ($dependentOptions.length) {
            if ($placeholder.length) {
                $placeholder.after($dependentOptions);
            } else {
                this.$dependentOptionsParent.append($dependentOptions);
            }

            if ($dependentOptions.length === 1 && (!$placeholder.length || this.$element.is(':required'))) {
                this.$element.attr('readonly', 'readonly');
                $dependentOptions.prop('selected', true);
                this.$label.addClass('is-readonly');
            } else if (!$selectedOption.prop('selected')) {
                $placeholder.prop('selected', true);
            }
        } else {
            this.$element.attr('disabled', 'disabled');
            this.$label.addClass('is-disabled');
            $placeholder.prop('selected', true);
        }

        if (this.value !== value) {
            this.$element.trigger('dependent_choice.change');
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
