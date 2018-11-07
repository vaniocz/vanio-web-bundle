import {component} from 'jquery-ts-components';

const KEY_CODE_ENTER = 13;
const KEY_CODE_ARROW_DOWN = 40;

@component('Location')
export default class Location
{
    private element!: HTMLElement;
    private $element!: JQuery;
    private address!: HTMLInputElement;
    private $address!: JQuery;
    private $latitude!: JQuery;
    private $longitude!: JQuery;
    private $form!: JQuery;
    private $submit!: JQuery;
    private autocomplete!: google.maps.places.Autocomplete;
    private submitted = false;
    private supportsValidationApi!: boolean;
    private usesValidationApi!: boolean;

    public constructor(element: JQuery|HTMLElement|string)
    {
        if (typeof google === 'undefined') {
            return;
        }

        this.$element = $(element);
        this.element = this.$element[0] as HTMLFormElement;
        this.$address = this.$element.find('.location__address');
        this.address = this.$address[0] as HTMLInputElement;
        this.$latitude = this.$element.find('.location__latitude').attr('type', 'hidden');
        this.$longitude = this.$element.find('.location__longitude').attr('type', 'hidden');
        this.$form = $(this.address.form!);
        this.$submit = this.$form.find(':submit');
        this.autocomplete = new google.maps.places.Autocomplete(this.address);
        this.autocomplete.addListener('place_changed', this.onPlaceChange.bind(this));
        this.$address.keydown(this.onAddressKeyDown.bind(this));
        this.$address.change(this.onAddressChange.bind(this));
        this.supportsValidationApi = 'checkValidity' in this.address;
        this.usesValidationApi = this.supportsValidationApi && !this.$form.prop('noValidate');
        this.$submit.on('click', this.onFormSubmit.bind(this));
    }

    private onPlaceChange(): void
    {
        this.clearLocation();
        const place = this.autocomplete.getPlace();

        if (place && place.geometry) {
            this.$latitude.val(place.geometry.location.lat());
            this.$longitude.val(place.geometry.location.lng());
        } else {
            this.invalidateAddress();
        }
    }

    private clearAddressValidity(): void
    {
        this.$element.removeClass('has-error');

        if (this.supportsValidationApi) {
            this.address.setCustomValidity('');
        }
    }

    private invalidateAddress(): void
    {
        if (this.supportsValidationApi) {
            this.address.setCustomValidity(this.$element.data('validationMessage') || 'Location not found');
        }
    }

    private onAddressKeyDown(event: JQueryEventObject): void
    {
        if (event.keyCode === KEY_CODE_ENTER) {
            event.preventDefault();
            this.$address.blur();
            this.selectHighlightedOrFirstResult();
        }
    }

    private onAddressChange(): void
    {
        this.clearAddressValidity();
        this.clearLocation();
    }

    private onFormSubmit(event: JQueryEventObject): void
    {
        this.$submit.removeAttr('disabled');

        if (this.isAddressEmpty() || !this.isLocationEmpty()) {
            this.removeAddressPlaceholderValue();

            return;
        } else if (!this.usesValidationApi) {
            event.preventDefault();
            event.stopImmediatePropagation(); // To prevent FpJsFormValidator submitting it
        }

        if (this.submitted) {
            this.submitted = false;
            this.$element.addClass('has-error');
        } else {
            // Cancels the submission, waits for the autocomplete result and resubmits the form again
            this.submitted = true;
            event.preventDefault();
            this.clearAddressValidity();
            this.$submit.attr('disabled', 'disabled');
            google.maps.event.addListenerOnce(
                this.autocomplete,
                'place_changed', () => this.$submit.first().click()
            );

            // IE mostly doesn't notice the validity change thus doesn't show the validation bubble without this timeout
            window.setTimeout(this.selectHighlightedOrFirstResult.bind(this), 0);
        }
    }

    private isAddressEmpty(): boolean
    {
        return this.$address.val() === '' || this.$address.is('.pac-placeholder');
    }

    private isLocationEmpty(): boolean
    {
        return this.$latitude.val() === '' || this.$longitude.val() === '';
    }

    private removeAddressPlaceholderValue(): void
    {
        if (this.$address.is('.pac-placeholder')) {
            this.$address.val('');
        }
    }

    private clearLocation(): void
    {
        this.$latitude.val('');
        this.$longitude.val('');
    }

    private selectHighlightedOrFirstResult(): void
    {
        if ($('.pac-item-selected').length === 0) {
            google.maps.event.trigger(this.address, 'keydown', {keyCode: KEY_CODE_ARROW_DOWN});
        }

        google.maps.event.trigger(this.address, 'focus');
        google.maps.event.trigger(this.address, 'keydown', {keyCode: KEY_CODE_ENTER});
    }
}
