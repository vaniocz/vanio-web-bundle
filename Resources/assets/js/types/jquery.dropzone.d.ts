import {DropzoneOptions} from 'dropzone';

declare global
{
    interface JQuery
    {
        dropzone(options?: DropzoneOptions): JQuery;
    }
}
