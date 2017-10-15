import {component} from 'jquery-ts-components';

@component('Collection')
export default class Collection
{
    private $element: JQuery;
    private $body: JQuery;

    public constructor(element: JQuery|HTMLElement|string, options: JQueryCollectionOptions = {})
    {
        this.$element = $(element);
        this.$body = this.$element.find('> .collection__body');
        options = $.extend({
            fade_in: false,
            fade_out: false,
            after_init: this.updateEntriesCount.bind(this),
            after_add: this.updateEntriesCount.bind(this),
            after_remove: this.updateEntriesCount.bind(this),
            drag_drop_options: {
                handle: '.collection-move',
                cursor: 's-resize',
                containment: 'parent',
                tolerance: 'pointer',
                forcePlaceholderSize: true,
            },
        }, options);
        this.$body.collection(options);
    }

    private get settings(): JQueryCollectionOptions
    {
        return this.$body.data('collection-settings');
    }

    private get $entries(): JQuery
    {
        return this.$body.find(this.settings.elements_selector!);
    }

    private updateEntriesCount(): void
    {
        this.$element.attr('data-entries-count', this.$entries.length);
    }
}
