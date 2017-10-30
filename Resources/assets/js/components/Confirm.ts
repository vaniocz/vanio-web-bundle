import {component} from 'jquery-ts-components';

interface ConfirmOptions extends ModalOptions
{
    content: string;
    template?: string;
    submitButtonText?: string;
    cancelButtonText?: string;
    submitButtonClass?: string;
}

const TEMPLATE = `
    <div tabindex="-1" role="dialog" class="modal fade">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button data-dismiss="modal" aria-hidden="true" class="close"></button>
                </div>

                <div class="modal-body text-center"></div>

                <div class="modal-footer">
                    <button type="button" data-dismiss="modal" class="btn btn-cancel btn-default"></button>
                    <button type="button" data-dismiss="modal" class="btn btn-confirm"></button>
                </div>
            </div>
        </div>
    </div>
`;

const TRANSLATIONS: {[language: string]: {[property: string]: string}} = {
    en: {
        cancelButtonText: 'Cancel',
        submitButtonText: 'Yes',
    },
    cs: {
        cancelButtonText: 'Zrušit',
        submitButtonText: 'Ano',
    },
};

@component('Confirm')
export default class Confirm
{
    private $element: JQuery;
    private options: ConfirmOptions;
    private $modal: JQuery;

    public constructor(element: JQuery|HTMLElement|string, options: ConfirmOptions|string)
    {
        this.$element = $(element);
        const language = this.$element.prop('ownerDocument').documentElement.lang;
        const translation = TRANSLATIONS[language] || TRANSLATIONS.en;

        if (typeof options === 'string') {
            options = {content: options};
        }

        this.options = $.extend(
            {
                template: TEMPLATE,
                backdrop: false,
                show: false,
                cancelButtonText: options.cancelButtonText || translation.cancelButtonText,
                submitButtonText: options.submitButtonText
                    || $.trim(this.$element.text())
                    || translation.submitButtonText,
                submitButtonClass: (` ${this.$element.attr('class')} `.match(/\sbtn-(?:warning|danger)\s/) || []).join()
                    || 'btn-primary',
            },
            options
        );
        this.options.show = false;
        this.$element.on('click', this.onClick.bind(this));
    }

    private onClick(event: JQueryEventObject): void
    {
        event.preventDefault();

        if (!this.$modal) {
            this.$modal = this.createModal();
        }

        this.$modal.modal('show');
    }

    private createModal(): JQuery
    {
        const $modal = $(this.options.template);
        $modal.find('.btn-confirm')
            .addClass(this.options.submitButtonClass!)
            .text(this.options.submitButtonText!)
            .on('click', this.redirectConfirmed.bind(this));
        $modal.find('.btn-cancel').text(this.options.cancelButtonText!);
        $modal.find('.modal-body').append(this.options.content);
        $modal.modal(this.options);

        return $modal;
    }

    private redirectConfirmed(confirmed: boolean): void
    {
        const link = this.$element.attr('href');

        if (link) {
            location.href = link;
        }
    }
}
