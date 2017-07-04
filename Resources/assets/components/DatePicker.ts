import {component} from 'jquery-ts-components';

const FORMATS: {[language: string]: string|undefined} = {
    en: 'M d, yyyy',
    cs: 'd. m. yyyy',
};

@component('DatePicker')
export default class DatePicker
{
    public constructor(element: JQuery|HTMLElement|string)
    {
        const $element = $(element);
        const language = $element.prop('ownerDocument').documentElement.lang;
        $element.datepicker({
            autoclose: true,
            format: FORMATS[language],
            todayHighlight: true,
            language,
            templates: {
                leftArrow: '<span class="s7-angle-left"></span>',
                rightArrow: '<span class="s7-angle-right"></span>',
            },
        });
    }
}
