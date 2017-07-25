import {component} from 'jquery-ts-components';
import {DropzoneOptions, DropzoneFile} from 'dropzone';

interface AjaxResponse
{
    id: string,
}

interface FileInfo extends DropzoneFile
{
    id?: string;
    key?: string;
}

@component('Dropzone')
export default class DropzoneComponent
{
    private dropzone: Dropzone;
    private options: DropzoneOptions;
    private $element: JQuery;
    private $target: JQuery;
    private $submit: JQuery;

    public constructor(element: JQuery|HTMLFormElement|string, options: DropzoneOptions)
    {
        this.options = options;
        this.options.addRemoveLinks = true;
        this.options.dictRemoveFile = '';
        this.options.dictCancelUpload = '';
        this.$element = $(element);
        this.$element.addClass('dropzone');
        this.$target = $(this.$element.data('dropzoneTarget'));
        this.$submit = $(':submit', this.$target.prop('form'));
        let self = this;

        options.init = function (this: Dropzone) {
            self.dropzone = this;
            self.initialize();
        };

        this.$element.dropzone(options);
    }

    private initialize(): void
    {
        this.dropzone.on('addedfile', this.onFileAdded.bind(this));
        this.dropzone.on('removedfile', this.onFileRemoved.bind(this));
        this.dropzone.on('success', this.onFileUploadSuccess.bind(this));
        this.dropzone.on('queuecomplete', this.onQueueComplete.bind(this));
        let files = this.$element.data('dropzoneFiles');
        const value = this.$target.val() as string;
        let selected = value === '' ? [] : JSON.parse(value);

        $.each(files, (key: string) => {
            if (selected.indexOf('uploaded:' + key) !== -1) {
                this.addUploadedFile(files[key]);
            }
        });
    }

    private onFileAdded(file: DropzoneFile): void
    {
        if (this.options.maxFiles && (this.dropzone.files.length > this.options.maxFiles)) {
            this.dropzone.removeFile(this.dropzone.files[0]);
        }

        this.$submit.button('loading');
    }

    private onFileRemoved(file: DropzoneFile): void
    {
        this.updateTargetValue();
    }

    private onFileUploadSuccess(file: FileInfo, response: AjaxResponse): void
    {
        file.id = response.id;
        this.updateTargetValue();
    }

    private onQueueComplete(): void
    {
        this.$submit.button('reset');
    }

    private addUploadedFile(file: any): void
    {
        this.dropzone.files.push(file);
        this.dropzone.emit('addedfile', file);

        if (file.url && file.mime && file.mime.indexOf('image/') === 0) {
            this.dropzone.emit('thumbnail', file, file.url);
        }

        this.dropzone.emit('complete', file);
    }

    private updateTargetValue(): void
    {
        let files: string[] = [];
        let file: FileInfo;

        for (file of this.dropzone.files) {
            if (file.status === 'success' || file.status === 'uploaded') {
                files.push(`${file.status}:${file.status === 'success' ? file.id : file.key}`);
            }
        }

        this.$target.val(files.length === 0 ? '' : JSON.stringify(files));
    }
}
