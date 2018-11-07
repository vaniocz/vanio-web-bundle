import {component} from 'jquery-ts-components';

@component('Tooltip')
export default class Tooltip
{
    private $element: JQuery;

    public constructor(element: JQuery|HTMLElement|string)
    {
        this.$element = $(element);
        this.$element.tooltip({
            animation: false,
            selector: '[title]',
            container: 'body',
            placement: 'auto bottom' as any,
            trigger: 'manual',
        });
        const tooltip = this.$element.data('bs.tooltip');
        this.$element.on({
            'mouseenter pointerenter': tooltip.enter.bind(tooltip),
            'mouseleave pointerleave': tooltip.leave.bind(tooltip),
        }, '[title]');
    }
}
