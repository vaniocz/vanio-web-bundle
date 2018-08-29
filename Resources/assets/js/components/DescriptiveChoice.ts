import {component} from 'jquery-ts-components';

@component('DescriptiveChoice')
export default class DescriptiveChoice
{
    private $element: JQuery;
    private $target: JQuery;

    public constructor(element: JQuery|HTMLSelectElement|string, target?: JQuery|HTMLSelectElement|string)
    {
        this.$element = $(element);
        this.$target = $(target);

        if (!this.$target.length) {
            this.$target = $('<div class="descriptive-choice__description"/>').insertAfter(this.$element);
        }

        this.$element.on('change dependent_choice.change', this.render.bind(this));
        this.render();
    }

    private render(): void
    {
        const description = this.$element.find('option:selected').data('description');

        if (description) {
            this.$target.html(description).show();
        } else {
            this.$target.hide();
        }
    }
}
