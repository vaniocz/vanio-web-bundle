import {component} from 'jquery-ts-components';
import noUiSlider from 'nouislider';

function isInteger(value: any): boolean
{
    return !isNaN(value) && parseInt(String(Number(value))) == value && !isNaN(parseInt(value, 10));
}

@component('RangeSlider')
export default class RangeSlider
{
    private $element: JQuery;
    private $values: JQuery;
    private noUiSlider: noUiSlider.noUiSlider;
    private isIe: boolean;
    private isEdge: boolean;

    public constructor(element: JQuery|HTMLElement|string, options: noUiSlider.Options)
    {
        this.$element = $(element);
        this.$values = this.$element.find('.range-slider__value');

        if (options.range.min === options.range.max) {
            (options.range.min as number)--;
            (options.range.max as number)++;
            this.$element.attr('disabled', 'disabled');
            this.$values.attr('readonly', 'readonly');
        }

        if (!options.format) {
            options.format = {
                from: Number,
                to: (value: number) => value.toLocaleString().replace(/ /g, ''),
            };
        }

        this.noUiSlider = noUiSlider.create(this.$element[0], options) as any;
        this.isIe = /Trident\/|MSIE /.test(window.navigator.userAgent);
        this.isEdge = /Edge\//.test(window.navigator.userAgent);
        this.noUiSlider.on('update', this.onSliderUpdate.bind(this));
        this.noUiSlider.on('change', this.onSliderChange.bind(this));
        this.$values
            .on('keypress', this.onValueKeyPress.bind(this))
            .on('change', this.onValueChange.bind(this));
        this.$values.filter(':input').on('input', this.resizeValues.bind(this));
        this.resizeValues();
    }

    private onSliderUpdate(values: string[]): void
    {
        this.$values.each((index: number, element: HTMLElement) => {
            const $value = $(element);
            const value = isInteger(values[index]) ? String(parseInt(values[index], 10)) : values[index];

            if ($value.is('input')) {
                $value.val(value);
                this.resizeValues();
            } else {
                $value.text(value);
            }
        });
    }

    private onSliderChange(values: string[], index: number): void
    {
        this.$values.eq(index).trigger('range_slider.change');
    }

    private onValueKeyPress(event: JQuery.Event): void
    {
        if (event.key! < '0' || event.key! > '9') {
            event.preventDefault();
        }
    }

    private onValueChange(event: JQuery.Event): void
    {
        const $value = $(event.target);
        let values = this.noUiSlider.get();

        if (Array.isArray(values)) {
            values[this.$values.index($value)] = $value.val() as string;
        } else {
            values = $value.val() as string;
        }

        this.noUiSlider.set(values as any);
    }

    private resizeValues(): void
    {
        this.$values.each((index: number, element: HTMLElement) => {
            const $value = $(element);
            let width = `${String($value.val()).length * (this.isIe ? 1.5 : 1)}ch`;

            if (this.isEdge) {
                width = `calc(${width} + 2px)`;
            }

            $value.css('width', width);
        });
    }
}
