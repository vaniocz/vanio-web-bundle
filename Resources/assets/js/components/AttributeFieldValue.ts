import {component} from 'jquery-ts-components';

@component('AttributeFieldValue')
export default class AttributeFieldValue
{
    private $element: JQuery;
    private $form: JQuery;
    private attributeName: string;

    public constructor(element: JQuery|HTMLElement|string, attributeName?: string)
    {
        this.$element = $(element);
        this.$form = this.$element.closest('form');
        this.attributeName = attributeName || this.generateAttributeName();
        this.$element.on('change', this.onChange.bind(this));
        this.onChange();
    }

    private onChange(): void
    {
        const value = this.$element.is('select')
            ? this.$element.find(':checked').val()
            : this.$element.val();

        (this.$form || this.$element).attr(`data-${this.attributeName}`, value as string);
    }

    private generateAttributeName(): string
    {
        const fieldName = this.$element.attr('name');

        if (this.$form && fieldName) {
            const attributeNameParts = fieldName.match(/\[.*\]/g);

            if (!attributeNameParts) {
                return fieldName;
            }

            return attributeNameParts[0]
                .replace(/\]\[/g, '-')
                .slice(1, -1);
        } else {
            return 'value';
        }
    }
}
