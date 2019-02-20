import {component, register} from 'jquery-ts-components';

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
            after_add: this.onAdd.bind(this),
            after_remove: this.updateEntriesCount.bind(this),
            drag_drop_options: {
                handle: '.collection-move',
                cursor: 's-resize',
                containment: 'parent',
                tolerance: 'pointer',
                forcePlaceholderSize: true,
                helper: this.createDragDropHelper.bind(this),
            },
            max: Infinity,
        }, options);
        this.$body.collection(options)
    }

    private get settings(): JQueryCollectionOptions
    {
        return this.$body.data('collection-settings');
    }

    private get $entries(): JQuery
    {
        return this.$body.find(this.settings.elements_selector!);
    }

    private onAdd($collection: JQuery, $entry: JQuery): void
    {
        register($entry);
        this.updateEntriesCount();
    }

    private createDragDropHelper(event: JQueryEventObject, $entry: JQuery): JQuery
    {
        if ($entry.css('display') !== 'table-row') {
            return $entry;
        }

        const $children = $entry.children();
        const $clone = $entry.clone();
        $clone.children().each((index: number, child: HTMLElement) => {
            const $child = $children.eq(index);
            const width = $child.css('box-sizing') === 'border-box' ? $child.outerWidth()! : $child.width()!;
            $(child).css('width', width);
        });

        return $clone;
    }

    private updateEntriesCount(): void
    {
        this.$element.attr('data-entries-count', this.$entries.length);
    }
}
