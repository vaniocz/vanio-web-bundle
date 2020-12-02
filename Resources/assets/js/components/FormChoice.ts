import {component, register} from 'jquery-ts-components';

@component('FormChoice')
export default class FormChoice
{
    private $element: JQuery;
    private $choice: JQuery;
    private $form: JQuery;
    private forms: {[choiceValue: string]: JQuery} = {};
    private choiceValue: string;

    public constructor(element: JQuery|HTMLElement|string)
    {
        this.$element = $(element);
        this.$choice = this.$element.find('.form-choice__choice')
            .not(this.$element.find('[data-component-form-choice] .form-choice__choice'));
        this.$form = this.$element.find('.form-choice__form')
            .not(this.$element.find('[data-component-form-choice] .form-choice__form'));
        this.$choice.change(this.switchForm.bind(this));
        this.choiceValue = this.$choice.val() as string;
        this.switchForm();
    }

    private switchForm(): void
    {
        if (!this.$form.is(':empty')) {
            this.forms[this.choiceValue] = this.$form.children();
        }

        this.choiceValue = this.$choice.val() as string;
        this.$form.children().detach();

        if (this.forms[this.choiceValue]) {
            this.$form.append(this.forms[this.choiceValue]);
        } else {
            this.$form.append($(this.$choice.find('option:selected').data('form')));
            register(this.$form);
        }
    }
}
