import {component} from 'jquery-ts-components';

let titlesRemoved = false;

@component('Gallery')
export default class Gallery
{
    private $element: JQuery;
    private options: JQueryPhotoSwipeOptions;

    public constructor(element: JQuery | HTMLFormElement | string, options: JQueryPhotoSwipeOptions)
    {
        this.$element = $(element);
        this.options = options;
        this.$element.photoSwipe(options);
        this.removePhotoSwipeTitles();
    }

    private removePhotoSwipeTitles(): void
    {
        if (!titlesRemoved) {
            titlesRemoved = true;
            $('.pswp [title]', this.$element.prop('ownerDocument')).removeAttr('title');
        }
    }
}
