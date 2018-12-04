import {component, register} from 'jquery-ts-components';

@component('FormChoice')
export default class FormChoice
{
    private $element: JQuery;
    private $choice: JQuery;
    private isChoiceSelect: boolean;
    private $form: JQuery;
    private forms: {[choiceValue: string]: JQuery} = {};
    private choiceValue: string;

    public constructor(element: JQuery|HTMLElement|string)
    {
        this.$element = $(element);
        this.$choice = this.$element.find('.form-choice__choice');
        this.isChoiceSelect = this.$choice.is('select');
        this.$form = this.$element.find('.form-choice__form');
        this.$choice.change(this.switchForm.bind(this));
        this.choiceValue = this.getChoiceValue();
        this.switchForm();
    }

    private switchForm(): void
    {
        if (!this.$form.is(':empty')) {
            this.forms[this.choiceValue] = this.$form.children();
        }

        this.choiceValue = this.getChoiceValue();
        this.$form.children().detach();

        if (this.forms[this.choiceValue]) {
            this.$form.append(this.forms[this.choiceValue]);
        } else {
            this.$form.append(this.getSelectedChoiceForm());
            register(this.$form);
        }
    }

    private getChoiceValue(): string
    {
        return this.isChoiceSelect
            ? this.$choice.val() as string
            : this.$choice.find('input:checked').val() as string;
    }

    private getSelectedChoiceForm(): JQuery
    {
        return $(this.$choice.find(this.isChoiceSelect ? 'option:selected' : 'input:checked').data('form'));
    }
}
