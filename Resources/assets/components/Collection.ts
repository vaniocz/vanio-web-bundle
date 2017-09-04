import {component} from 'jquery-ts-components';

@component('Collection')
export default class Collection
{
    private $element: JQuery;

    public constructor(element: JQuery|HTMLElement|string, options: JQueryCollectionOptions = {})
    {
        this.$element = $(element);
        this.$element.collection(options);
    }
}
