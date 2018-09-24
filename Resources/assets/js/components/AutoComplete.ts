import {component} from 'jquery-ts-components';

export interface AutoCompleteSuggestion
{
    value: string;
    viewValue: string;
    data: any;
    html?: string;
}

interface AutoCompleteOptions
{
    searchSelector: string;
    entitySelector: string;
    ajaxField: string;
    ajax: string;
    allowUnsuggested?: boolean;
}

interface AutoCompleteSuggestionList extends Array<AutoCompleteSuggestion>
{
    totalCount?: number;
}

interface AutocompleteInstance
{
    options: JQueryAutocompleteOptions;
    suggestionsContainer: HTMLElement;
    noSuggestionsContainer: HTMLElement;
    selectedIndex: number;
    suggestions: AutoCompleteSuggestionList;
    visible?: boolean;
    currentRequest?: JQueryXHR;
    currentValue: string;
    select(index: number): void;
    abortAjax(): void;
    fixPosition(): void;
    onValueChange(): void;
}

@component('AutoComplete')
export default class AutoComplete
{
    private $element: JQuery;
    private options: AutoCompleteOptions;
    private $search: JQuery;
    private $entity: JQuery;
    private $ajax: JQuery;
    private $form: JQuery;
    private $loading: JQuery;
    private $remainingCount: JQuery;
    private autocomplete: AutocompleteInstance;
    private attr: string[];
    private invalid = false;
    private currentSearch: string;

    public constructor(element: JQuery|HTMLElement|string, options: AutoCompleteOptions)
    {
        this.$element = $(element);
        this.options = options;
        this.$search = $(options.searchSelector);
        this.$entity = $(options.entitySelector);
        this.$ajax = $(options.ajaxField);
        this.$form = this.$element.closest('form');
        this.$search.devbridgeAutocomplete({
            serviceUrl: this.$form.attr('action'),
            type: this.$form.attr('method'),
            groupBy: '_group',
            deferRequestBy: 50,
            showNoSuggestionNotice: true,
            noSuggestionNotice: Translator.trans('autoComplete.noSuggestions', {}, 'components'),
            orientation: 'auto',
            onSearchStart: this.onSearchStart.bind(this),
            onSearchComplete: this.onSearchComplete.bind(this),
            onHide: this.onHide.bind(this),
            onSearchError: this.onSearchError.bind(this),
            onSelect: this.onSelect.bind(this),
            onInvalidateSelection: this.onInvalidateSelection.bind(this),
            transformResult: this.transformResult.bind(this),
            formatResult: this.formatResult.bind(this),
            ajaxSettings: {
                beforeSend: this.onBeforeSend.bind(this),
            },
        });
        this.autocomplete = this.$search.data('autocomplete');
        this.$loading = $('<div class="autocomplete-loading"/>')
            .text(Translator.trans('autoComplete.loading', {}, 'components'));
        this.$remainingCount = $('<div class="autocomplete-remaining-count"/>');
        this.attr = this.$element.prop('attributes');
        this.currentSearch = String(this.$search.val());
        this.$search
            .off('focus.autocomplete')
            .on('focus', this.onFocus.bind(this))
            .on('change', this.onChange.bind(this));
        $(this.autocomplete.suggestionsContainer).on('mousedown', this.onSuggestionsMouseDown.bind(this));
    }

    private onSearchStart(): void
    {
        $(this.autocomplete.suggestionsContainer)
            .append(this.$loading)
            .addClass('is-loading')
            .show()
        this.autocomplete.visible = true;
        this.autocomplete.fixPosition();
        this.$element.addClass('is-loading');
    }

    private onSearchComplete(search: string, suggestions: AutoCompleteSuggestionList): void
    {
        this.invalid = true;
        const remainingCount = (suggestions.totalCount || 0) - suggestions.length;

        if (remainingCount > 0) {
            const remainingCountText = Translator.transChoice(
                'autoComplete.remainingCount',
                remainingCount,
                {},
                'components'
            );
            $(this.autocomplete.suggestionsContainer).append(this.$remainingCount.text(remainingCountText));
        } else {
            this.$remainingCount.remove();
        }

        $(this.autocomplete.suggestionsContainer).removeClass('is-loading');
        this.autocomplete.fixPosition();
        this.$element.removeClass('is-loading');
    }

    private onSearchError(): void
    {
        this.$element.removeClass('is-loading');
    }

    private onBeforeSend(xhr: JQueryXHR, settings: JQueryAjaxSettings): void
    {
        const data = this.$form.serializeArray()
        data.push({
            name: this.options.ajaxField,
            value: '1',
        });
        settings.data = jQuery.param(data);
    }

    private transformResult(data: any): AutocompleteResponse
    {
        const response = typeof data === 'string' ? $.parseJSON(data) : data;
        response.suggestions.totalCount = response.totalCount;

        return response;
    }

    private onFocus(): void
    {
        if (this.options.allowUnsuggested && this.autocomplete.suggestions.length) {
            $(this.autocomplete.suggestionsContainer).show();
        }
    }

    private onChange(): void
    {
        if (!this.options.allowUnsuggested && this.$entity.val() !== '' && this.$search.val() === '') {
            this.$entity.val('');
            this.$element.trigger('auto-complete', [null]);
            this.currentSearch = '';
        }
    }

    /**
     * @see https://github.com/devbridge/jQuery-Autocomplete/pull/609
     */
    private onSuggestionsMouseDown(event: JQuery.Event): void
    {
        event.preventDefault();
    }

    private onHide(): void
    {
        if (!this.options.allowUnsuggested && this.$entity.val() !== '') {
            if (this.invalid || this.autocomplete.currentRequest || this.$search.val() === '') {
                if (this.$search.val() === '') {
                    this.$entity.val('');
                    this.$element.trigger('auto-complete', [null]);
                    this.currentSearch = '';
                } else if (this.$search.val() !== this.currentSearch) {
                    this.$search.val(this.currentSearch);
                    this.autocomplete.currentValue = this.currentSearch;
                }
            }
        }

        if (!this.options.allowUnsuggested && this.$entity.val() === '' && this.$search.val() !== '') {
            this.$search.val('');
            this.autocomplete.onValueChange();
        }

        this.autocomplete.abortAjax();
    }

    private onSelect(suggestion: AutoCompleteSuggestion): void
    {
        this.invalid = false;
        this.$entity.val(suggestion.viewValue);
        this.$element.trigger('auto-complete', suggestion);
        this.currentSearch = suggestion.value;
    }

    private formatResult(suggestion: AutoCompleteSuggestion, search: string): string
    {
        return suggestion.html == null
            ? $.Autocomplete.defaults.formatResult!(suggestion, search)
            : suggestion.html;
    }

    private onInvalidateSelection(): void
    {
        this.invalid = true;
    }
}