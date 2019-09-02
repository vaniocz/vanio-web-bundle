import {component} from 'jquery-ts-components';

@component('EuCookiesBar')
export default class EuCookiesBar
{
    private $element: JQuery;
    private $button: JQuery;

    public constructor(element: JQuery|HTMLFormElement|string)
    {
        this.$element = $(element);
        this.$button = this.$element.find('.eu-cookies-bar__button');
        this.$button.click(this.onButtonClick.bind(this));
    }

    private onButtonClick(event: JQueryEventObject): void
    {
        const date = new Date;
        date.setFullYear(date.getFullYear() + 10);
        document.cookie = 'eu-cookies=1; path=/; expires=' + date.toUTCString();
        this.$element.remove();
    }
}
