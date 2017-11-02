import {component} from 'jquery-ts-components';

@component('FileInput')
export default class FileInput
{
    private $element: JQuery;
    private $input: JQuery;
    private inputPlaceholder: string;
    private inputText: string;
    private filename?: string;

    public constructor(element: JQuery|HTMLFormElement|string)
    {
        this.$element = $(element);
        this.$input = this.$element.find('input');
        this.$input.on('change', this.showSelectedFiles.bind(this));
        this.inputPlaceholder = this.$element.data('inputPlaceholder') || 'Choose file…';
        this.inputText = this.$element.data('inputText') || '{{ filename }}';
        this.filename = this.$element.data('filename');
        this.showSelectedFiles();
    }

    private get files(): File[]
    {
        const files = [];

        for (const file of this.$input.prop('files')) {
            files.push(file);
        }

        return files;
    }

    private showSelectedFiles(): void
    {
        const filename = this.files.length
            ? this.files.map((file: File) => file.name).join(', ')
            : this.filename;

        if (filename == null) {
            this.$element.removeAttr('data-input-value');
        } else {
            const value = this.inputText
                .replace('{{ filename }}', filename)
                .replace('{{ count }}', this.files.length.toString());
            this.$element.attr('data-input-value', value);
        }
    }
}
