import {component} from 'jquery-ts-components';

@component('NativeValidation')
export default class NativeValidation
{
    private element: HTMLFormElement;
    private $element: JQuery;

    public constructor(element: JQuery|HTMLFormElement|string)
    {
        this.$element = $(element);
        this.element = this.$element[0] as HTMLFormElement;

        if (typeof this.element.checkValidity !== 'function') {
            return;
        }

        this.element.addEventListener('invalid', this.suppressNativeBubblesOnInvalid, true);
        this.$element.on('submit', this.preventSubmissionOnSubmitInvalid.bind(this));
        this.$element.on('input', this.hideErrorsOnInput.bind(this));
        this.$element.find(':submit').on('click', this.onSubmit.bind(this));
    }

    private suppressNativeBubblesOnInvalid(event: Event)
    {
        event.preventDefault();
    }

    private preventSubmissionOnSubmitInvalid(event: JQueryEventObject)
    {
        if (!this.element.checkValidity()) {
            event.preventDefault();
        }
    }

    private onSubmit(event: JQueryEventObject)
    {
        if (this.element.checkValidity()) {
            return;
        }

        event.preventDefault();
        const $invalidFields = this.$element.find(':invalid');
        this.$element.find('.form-errors').remove();
        $invalidFields.each((index, field) => this.renderErrors($(field)));
        $invalidFields.first().focus();
    }

    private hideErrorsOnInput(event: JQueryEventObject)
    {
        const $field = $(event.target);
        const $container = $field.parent().hasClass('input-group') ? $field.parent() : $field;
        $container.next('.form-errors').remove();
    }

    private renderErrors($field: JQuery)
    {
        const $container = $field.parent().hasClass('input-group') ? $field.parent() : $field;
        const message = $field.data('validationMessage') || $field.prop('validationMessage');
        $container.after(`<ul class="form-errors"><li>${message}</li></ul>`);
    }
}
