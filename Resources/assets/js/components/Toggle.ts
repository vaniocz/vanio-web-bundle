import {component} from 'jquery-ts-components';

interface ToggleOptions
{
    target: JQuery|HTMLElement|string;
    className: string;
}

@component('Toggle')
export default class Toggle
{
    private options: ToggleOptions;

    public constructor(element: JQuery|HTMLElement|string, options: ToggleOptions|string)
    {
        this.options = $.extend(
            {className: 'toggle--active'},
            typeof options === 'string' ? {target: options} : options
        );

        $(element).on('click', (event: JQuery.Event) => {
            event.preventDefault();
            $(this.options.target).toggleClass(this.options.className);
        });
    }
}
