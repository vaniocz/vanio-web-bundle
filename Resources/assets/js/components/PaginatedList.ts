import {component, register} from 'jquery-ts-components';

@component('PaginatedList')
export default class PaginatedList
{
    private $element: JQuery;
    private $content: JQuery;
    private $topPagination: JQuery;
    private $bottomPagination: JQuery;
    private snippet: string;

    public constructor(element: JQuery|HTMLFormElement|string, snippet: string)
    {
        this.$element = $(element);
        this.$content = this.findListContent(this.$element);
        this.$topPagination = this.$element.find('.paginated-list-top-pagination');
        this.$bottomPagination = this.$element.find('.paginated-list-bottom-pagination');
        this.$element.on('click', '.paginator-previous, .paginator-next', this.onPageClick.bind(this));
        this.snippet = snippet;
        this.updatePaginatorCount();
    }

    private onPageClick(event: JQueryEventObject): void
    {
        const isPrevious = $(event.target).is('.paginator-previous');
        const url = this.updatePageQueryStringParameter(isPrevious);
        event.preventDefault();

        if (window.history && history.pushState) {
            this.loadPage(isPrevious);
            history.pushState({}, '', url);
        } else {
            location.href = url;
        }
    }

    private loadPage(isPrevious: boolean): void
    {
        const $paginator = $('.sibling-paginator', isPrevious ? this.$topPagination : this.$bottomPagination);
        const url = $paginator.find(isPrevious ? '.paginator-previous' : '.paginator-next').attr('href') as string;
        $paginator.addClass('loading');
        $.get(this.updateQueryStringParameter(url, '_snippet', this.snippet), this.onPageLoaded.bind(this, isPrevious));
    }

    private onPageLoaded(isPrevious: boolean, data: string): void
    {
        const $data = $(data);
        const $content = this.findListContent($data).find('> *');

        if (isPrevious) {
            this.$content.prepend($content);
            this.$topPagination.html($data.find('.paginated-list-top-pagination').html());
        } else {
            this.$content.append($content);
            this.$bottomPagination.html($data.find('.paginated-list-bottom-pagination').html());
        }

        this.updatePaginatorCount();
        register($content);
    }

    private findListContent($element: JQuery): JQuery
    {
        let $list = $element.find('.paginated-list-content');

        return $list.length ? $list : $element.find('.paginated-list-list');
    }

    private updatePaginatorCount(): void
    {
        const countValue = this.$content.find('.paginated-list-record').length;
        this.$topPagination.add(this.$bottomPagination).find('.paginator-info-count').text(countValue);
    }

    private updatePageQueryStringParameter(isPrevious: boolean): string
    {
        return this.updateQueryStringParameter(window.location.href, 'page', (pageRange: string) => {
            let [fromValue, toValue] = String(pageRange).split('-');
            let fromPage = Number(fromValue) || 1;
            let toPage = Number(toValue) || fromPage;

            if (isPrevious) {
                fromPage--;
            } else {
                toPage++;
            }

            return `${fromPage}-${toPage}`;
        });
    }

    private updateQueryStringParameter(url: string, parameter: string, value: Function|string): string
    {
        let [updatedUrl, hash] = url.split('#');
        parameter = parameter.replace(/[\-\[\]\/\{\}\(\)\*\+\?\.\\\^\$\|]/g, '\\$&');
        const pattern = new RegExp(`([?&])${parameter}=(.*)?(&|$)`, 'i');
        let matches = updatedUrl.match(pattern);

        if (typeof value === 'function') {
            value = (value as Function)(matches ? decodeURIComponent(matches[2]) : undefined);
        }

        value = encodeURIComponent(value as string);

        if (matches) {
            updatedUrl = updatedUrl.replace(pattern, `$1${parameter}=${value}$3`);
        } else {
            updatedUrl += updatedUrl.indexOf('?') === -1 ? '?' : '&';
            updatedUrl += `${parameter}=${value}`;
        }

        if (hash !== undefined) {
            updatedUrl = `${updatedUrl}#${hash}`;
        }

        return updatedUrl;
    }
}
