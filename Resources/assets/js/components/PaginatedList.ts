import {component, register} from 'jquery-ts-components';
import {getState, setState} from '@vanio_web/js/history';

interface PaginatedListOptions
{
    snippet: string;
    stateId?: string;
}

@component('PaginatedList')
export default class PaginatedList
{
    private $element: JQuery;
    private $content: JQuery;
    private $topPagination: JQuery;
    private $bottomPagination: JQuery;
    private options: PaginatedListOptions;
    private stateVersion: number;

    public constructor(element: JQuery|HTMLFormElement|string, options: PaginatedListOptions|string)
    {
        this.$element = $(element);
        this.$content = this.findListContent(this.$element);
        this.$topPagination = this.$element.find('.paginated-list-top-pagination');
        this.$bottomPagination = this.$element.find('.paginated-list-bottom-pagination');
        this.$element.on('click', '.paginator-previous, .paginator-next', this.onPageClick.bind(this));
        this.options = $.extend(
            {stateId: 'paginated_list'},
            typeof options === 'string' ? {snippet: options} : options
        );
        this.updatePaginatorCount();
        $(window).off(`popstate.${this.options.stateId}`);
        $(window).on(`popstate.${this.options.stateId}`, this.onPopState.bind(this));
        this.stateVersion = setState(this.options.stateId!, this.$element.html());
    }

    private onPageClick(event: JQueryEventObject): void
    {
        const isPrevious = $(event.target).is('.paginator-previous');
        const url = this.updatePageQueryStringParameter(isPrevious);
        event.preventDefault();

        if (window.history && history.pushState) {
            const $paginator = $('.sibling-paginator', isPrevious ? this.$topPagination : this.$bottomPagination);
            let ajaxUrl = $paginator.find(isPrevious ? '.paginator-previous' : '.paginator-next').attr('href') as string;
            ajaxUrl = this.updateQueryStringParameter(ajaxUrl, '_snippet', this.options.snippet);
            $paginator.addClass('is-loading');
            $.get(ajaxUrl, this.onPageLoaded.bind(this, isPrevious, url));
        } else {
            location.href = url;
        }
    }

    private onPageLoaded(isPrevious: boolean, url: string, response: string): void
    {
        const $response = $(response);
        const $content = this.findListContent($response).find('> *');

        if (isPrevious) {
            this.$content.prepend($content);
            this.$topPagination.html($response.find('.paginated-list-top-pagination').html());
        } else {
            this.$content.append($content);
            this.$bottomPagination.html($response.find('.paginated-list-bottom-pagination').html());
        }

        this.$content.find('.paginated-list-placeholder')
            .appendTo(this.$content);
        this.updatePaginatorCount();
        this.stateVersion = setState(this.options.stateId!, this.$element.html(), url);
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

    private onPopState(event: JQuery.Event): void
    {
        const state = (event.originalEvent as PopStateEvent).state;
        const version = state && state.versions && state.versions[this.options.stateId!];

        if (this.stateVersion !== version) {
            const state = getState(this.options.stateId!, version);
            this.stateVersion = version;
            this.$element.html(state);
            this.$content = this.findListContent(this.$element);
            this.$topPagination = this.$element.find('.paginated-list-top-pagination');
            this.$bottomPagination = this.$element.find('.paginated-list-bottom-pagination');
            register(this.$element);
        }
    }
}
