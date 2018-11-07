import {component} from 'jquery-ts-components';
import {DropzoneOptions, DropzoneFile} from 'dropzone';

interface UploadedFileOptions extends DropzoneOptions
{
    target: string;
    thumbnailFilter?: string;
}

interface FileMetadata {
    readonly size: number;
    readonly name: string;
    url?: string;
    thumbnailUrl?: string;
    id?: string;
    key?: string;
}

interface FileInfo extends DropzoneFile, FileMetadata
{}

@component('UploadedFile')
export default class UploadedFile
{
    private dropzone!: Dropzone;
    private options: UploadedFileOptions;
    private $element: JQuery;
    private $target: JQuery;
    private $submit: JQuery;
    private busy = false;

    public constructor(element: JQuery|HTMLFormElement|string, options: UploadedFileOptions)
    {
        this.options = options;
        this.options.createImageThumbnails = false;
        this.options.addRemoveLinks = true;
        this.options.dictRemoveFile = '';
        this.options.dictCancelUpload = '';
        this.$element = $(element);
        this.$element.addClass('dropzone');
        this.$target = $(options.target);
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
        const uploadedFiles = this.$submit.data('uploadedFiles') || [];

        if (!uploadedFiles.length) {
            this.$submit.data('initialDisabled', this.$submit.is('disabled'));
        }

        uploadedFiles.push(this);
        this.$submit.data('uploadedFiles', uploadedFiles)
        this.dropzone.on('addedfile', this.onFileAdded.bind(this));
        this.dropzone.on('removedfile', this.updateTargetValue.bind(this));
        this.dropzone.on('success', this.onFileUploadSuccess.bind(this));
        this.dropzone.on('queuecomplete', this.onQueueComplete.bind(this));
        const value = this.$target.val() as string;
        const files = value === '' ? [] : JSON.parse(value);
        files.forEach(this.addUploadedFile.bind(this));
    }

    private onFileAdded(file: DropzoneFile): void
    {
        if (this.options.maxFiles && (this.dropzone.files.length > this.options.maxFiles)) {
            this.dropzone.removeFile(this.dropzone.files[0]);
        }

        this.busy = true;
        this.$submit.attr('disabled', 'disabled');
    }

    private onFileUploadSuccess(file: FileInfo, response: FileMetadata): void
    {
        file.id = response.id;
        file.url = response.url;
        file.thumbnailUrl = response.thumbnailUrl;
        this.addThumbnail(file);
        this.updateTargetValue();
    }

    private onQueueComplete(): void
    {
        this.busy = false;

        if (this.$submit.data('initialDisabled')) {
            return;
        }

        let uploadedFile: UploadedFile;

        for (uploadedFile of this.$submit.data('uploadedFiles')) {
            if (uploadedFile.busy) {
                return;
            }
        }

        this.$submit.removeAttr('disabled');
    }

    private addUploadedFile(file: FileMetadata): void
    {
        this.dropzone.files.push(file as DropzoneFile);
        this.dropzone.emit('addedfile', file);
        this.addThumbnail(file);
        this.dropzone.emit('complete', file);
    }

    private addThumbnail(file: FileMetadata): void
    {
        if (file.thumbnailUrl) {
            this.dropzone.emit('thumbnail', file, file.thumbnailUrl);
        }
    }

    private updateTargetValue(): void
    {
        let files: FileMetadata[] = [];
        let file: FileInfo;

        for (file of this.dropzone.files) {
            files.push({
                id: file.id,
                key: file.key,
                url: file.url,
                thumbnailUrl: file.thumbnailUrl,
                name: file.name,
                size: file.size,
            });
        }

        this.$target.val(files.length === 0 ? '' : JSON.stringify(files));
    }
}
