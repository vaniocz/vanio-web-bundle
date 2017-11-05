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
    mimeType?: string;
    path?: string;
    id?: string;
    key?: string;
}

interface FileInfo extends DropzoneFile, FileMetadata
{}

@component('UploadedFile')
export default class UploadedFile
{
    private dropzone: Dropzone;
    private options: UploadedFileOptions;
    private $element: JQuery;
    private $target: JQuery;
    private $submit: JQuery;

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

        this.$submit.button('loading');
    }

    private onFileUploadSuccess(file: FileInfo, response: FileMetadata): void
    {
        file.id = response.id;
        file.path = response.path;
        file.mimeType = response.mimeType;
        this.addThumbnail(file);
        this.updateTargetValue();
    }

    private onQueueComplete(): void
    {
        this.$submit.button('reset');
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
        if (file.path && file.mimeType && file.mimeType.indexOf('image/') === 0) {
            this.dropzone.emit('thumbnail', file, file.path);
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
                path: file.path,
                name: file.name,
                size: file.size,
                mimeType: file.mimeType,
            });
        }

        this.$target.val(files.length === 0 ? '' : JSON.stringify(files));
    }
}
