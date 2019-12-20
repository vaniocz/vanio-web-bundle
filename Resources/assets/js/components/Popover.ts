import {component} from 'jquery-ts-components';

@component('Popover')
export default class Popover
{
    private $element: JQuery;

    public constructor(element: JQuery|HTMLElement|string)
    {
        this.$element = $(element);
        this.$element.popover({
            animation: false,
            selector: '[data-toggle="popover"]',
            container: 'body',
            placement: 'auto bottom' as any,
            trigger: 'focus',
            delay: {show: 0, hide: 100},
            html: true,
        });
    }
}
