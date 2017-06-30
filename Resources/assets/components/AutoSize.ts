import autosize from 'autosize';
import {component} from 'jquery-ts-components';

@component('AutoSize')
export default class AutoSize
{
    private $element: JQuery;

    public constructor(element: JQuery | HTMLFormElement | string)
    {
        this.$element = $(element);
        autosize(this.$element[0]);
    }
}
