import {component} from 'jquery-ts-components';

@component('Popover')
export default class Popover
{
    private defaultLinkTarget: string|undefined;

    public constructor(element: JQuery|HTMLElement|string, defaultLinkTarget?: string)
    {
        $(element)
            .popover({
                animation: false,
                selector: '[data-toggle="popover"]',
                container: 'body',
                placement: 'auto bottom' as any,
                trigger: 'focus',
                html: true,
            })
            .on('mousedown', '.popover', this.onPopoverMouseDown.bind(this));

        this.defaultLinkTarget = defaultLinkTarget;
    }

    private onPopoverMouseDown(event: JQuery.Event): void
    {
        const $target = $(event.target);

        if ($target.is('a')) {
            event.preventDefault();

            if (!$target.attr('target') && this.defaultLinkTarget) {
                $target.attr('target', this.defaultLinkTarget);
            }
        }
    }
}
