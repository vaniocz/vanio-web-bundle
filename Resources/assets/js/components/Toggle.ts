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
        const isSourceCheckbox = $source.is('input[type="checkbox"]');
        const issourceRadio = $source.is('input[type="radio"]');

        if (isSourceCheckbox || issourceRadio) {
            const $eventTarget = issourceRadio ? $(`input[name="${$source.attr('name')}"]`) : $source;
            const toggleClass = () => {
                $target.toggleClass(this.options.className, $source.is(':checked'));
            };

            $eventTarget.on('change', (event: JQuery.Event) => { toggleClass(); });
            toggleClass();
        } else {
            $source.on('click', (event: JQuery.Event) => {
                event.preventDefault();
                $target.toggleClass(this.options.className);
            });
        }
    }
}
