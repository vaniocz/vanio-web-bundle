import {component} from 'jquery-ts-components';

interface ToggleOptions
{
    source: JQuery|HTMLElement|string;
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
            {className: 'toggle--active', source: element},
            typeof options === 'string' ? {target: options} : options
        );
        const $source = $(this.options.source);
        const $target = $(this.options.target);
        
        if ($source.is(':checkbox') || $source.is(':radio')) {
            const $eventTarget = $source.is(':radio') && $source.attr('name')
                ? $(`input[name="${$source.attr('name')}"]`)
                : $source;
            const toggleClass = () => $target.toggleClass(this.options.className, $source.is(':checked'));
            $eventTarget.on('change', toggleClass);
            toggleClass();
        } else {
            $source.on('click', (event: JQuery.Event) => {
                event.preventDefault();
                $target.toggleClass(this.options.className);
            });
        }
    }
}
