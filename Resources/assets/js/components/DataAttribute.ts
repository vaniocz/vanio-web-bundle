import {component} from 'jquery-ts-components';

interface DataAttributeOptions
{
    dataSource: JQuery|HTMLElement|string;
    attributeName?: string;
}

@component('DataAttribute')
export default class DataAttribute
{
    private $element: JQuery;
    private options: DataAttributeOptions;
    private $dataSource: JQuery;

    public constructor(element: JQuery|HTMLElement|string, options: DataAttributeOptions|string)
    {
        this.options = $.extend(
            {attributeName: 'value'},
            typeof options === 'string' ? {dataSource: options} : options
        );
        this.$element = $(element);
        this.$dataSource = $(this.options.dataSource);
        this.$dataSource.on('change', this.onChange.bind(this));
        this.onChange();
    }

    private onChange(): void
    {
        const value = this.$dataSource.is('select')
            ? this.$dataSource.find(':checked').val()
            : this.$dataSource.val();

        this.$element.attr(`data-${this.options.attributeName}`, value as string);
    }
}
