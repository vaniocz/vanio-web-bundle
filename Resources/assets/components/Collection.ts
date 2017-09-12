import {component} from 'jquery-ts-components';

interface Settings
{
    [option: string]: any;
}

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
        }, options);
        this.$body.collection(options);
    }

    private get settings(): Settings
    {
        return this.$body.data('collection-settings');
    }

    private get entriesCount(): number
    {
        return this.$body.find(this.settings.elements_selector).length;
    }

    private updateEntriesCount(): void
    {
        this.$element.attr('data-entries-count', this.entriesCount);
    }
}
