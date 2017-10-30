interface JQueryPhotoSwipeOptions
{
    link?: JQuery | HTMLFormElement | string,
    captionEl?: boolean,
    fullscreenEl?: boolean,
    shareEl?: boolean,
    bgOpacity?: number,
    tapToClose?: boolean,
    tapToToggleControls?: boolean,
    mainClass?: string,
    template?: string,
    afterChange?: Function,
    barsSize?: {
        top: number,
        bottom: number | 'auto',
    },
}

interface JQuery
{
    photoSwipe(options?: JQueryPhotoSwipeOptions): JQuery;
}
