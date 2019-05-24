import {component} from 'jquery-ts-components';

interface CollapseOptions
{
    source: JQuery|HTMLElement|string;
    target: JQuery|HTMLElement|string;
    className: string;
    dimensions?: string[];
    inverseState?: boolean;
}

@component('Collapse')
export default class Collapse
{
    private $source: JQuery;
    private $target: JQuery;
    private options: CollapseOptions;

    public constructor(element: JQuery|HTMLElement|string, options: CollapseOptions|string)
    {
        this.options = $.extend(
            {className: 'collapse--active', source: element},
            typeof options === 'string' ? {target: options} : options
        );
        this.$source = $(this.options.source);
        this.$target = $(this.options.target);

        if (this.$source.is(':checkbox') || this.$source.is(':radio') || this.$source.is('option')) {
            let $eventTarget;

            if (this.$source.is('option')) {
                $eventTarget = this.$source.closest('select');
            } else if (this.$source.is(':radio') && this.$source.attr('name')) {
                $eventTarget = $(`input[name="${this.$source.attr('name')}"]`);
            } else {
                $eventTarget = this.$source;
            }

            $eventTarget.on('change', () => this.collapse(this.$source.is(':checked')));
            this.collapse(this.$source.is(':checked'));
        } else {
            this.$source.on('click', (event: JQuery.Event) => {
                event.preventDefault();
                this.collapse();
            });
        }
    }

    private collapse(state?: boolean): void
    {
        let properties = this.options.dimensions;

        if (!properties) {
            const dimensions = (this.$target.css('--collapse-dimensions') || '').toLowerCase().trim();
            properties = ['', 'unset', 'initial'].indexOf(dimensions) === -1
                ? dimensions.split(/\s*,\s*/)
                : [];
        }

        const isStateInversed = this.options.inverseState === undefined
            ? (this.$target.css('--collapse-state') || '').trim() === 'inversed'
            : this.options.inverseState;

        if (state === undefined) {
            state = !this.$target.hasClass(this.options.className);
        }

        if (isStateInversed) {
            state = !state;
        }

        const sourceStyle: {[property: string]: string} = {};
        const targetStyle: {[property: string]: string} = {};

        for (const property of properties) {
            sourceStyle[property] = this.$target.css(property);
            this.$target.css(property, state ? 'auto' : sourceStyle[property]);
            // When changing from auto to fixed set it to fixed value
            // otherwise set it to auto to bypass the transition before toggling the class.
        }

        this.$source.attr('aria-expanded', state ? 'true' : 'false');
        this.$target.toggleClass(this.options.className, arguments.length === 0 ? undefined : state);

        for (const property of properties) {
            if (state) {
                targetStyle[property] = this.$target.css(property);

                const onTransitionEnd = (event: JQuery.Event) => {
                    if ((event.originalEvent as any).propertyName === property) {
                        this.$target
                            .off('transitionend.toggle', onTransitionEnd)
                            .css(property, '');
                    }
                };

                this.$target.on('transitionend.toggle', onTransitionEnd);
            } else {
                this.$target.off('transitionend.toggle');
                targetStyle[property] = '';
            }
        }

        this.$target.css(sourceStyle);
        setTimeout(() => this.$target.css(targetStyle), 20);
    }
}