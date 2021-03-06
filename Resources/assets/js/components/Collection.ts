import {component, register} from 'jquery-ts-components';

@component('Collection')
export default class Collection
{
    private $element: JQuery;
    private $body: JQuery;

    public constructor(element: JQuery|HTMLElement|string, options: JQueryCollectionOptions = {})
    {
        this.$element = $(element);
        const prefix = options.prefix || 'collection';
        this.$body = this.$element.find(`> .${prefix}__body`);
        options = $.extend({
            fade_in: false,
            fade_out: false,
            after_init: this.updateEntriesCount.bind(this),
            before_add: this.onBeforeAdd.bind(this),
            after_add: this.onAfterAdd.bind(this),
            before_remove: this.onBeforeRemove.bind(this),
            after_remove: this.onAfterRemove.bind(this),
            drag_drop_options: {
                handle: `.${prefix}-move`,
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

    private onBeforeAdd($collection: JQuery): boolean|undefined
    {
        const event = $.Event('beforeAdd');
        this.$element.trigger(event);

        if (event.isDefaultPrevented()) {
            return false;
        }
    }

    private onAfterAdd($collection: JQuery, $entry: JQuery): void
    {
        $entry.trigger('afterAdd');
        register($entry);
        this.updateEntriesCount();
    }

    private onBeforeRemove($collection: JQuery, $entry: JQuery): boolean|undefined
    {
        const event = $.Event('beforeRemove');
        $entry.trigger(event);

        if (event.isDefaultPrevented()) {
            return false;
        }
    }

    private onAfterRemove($collection: JQuery, $entry: JQuery): void
    {
        $entry.trigger('afterRemove');
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
