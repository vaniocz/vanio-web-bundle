interface FpJsFormValidator
{
    errorClass: string;
    inputGroupClass: string;
    hasErrorClass: string;
    insertMethod: string;

    getElementValue(element: FpJsFormElement): any;
    isValueEmpty(value: any): boolean;
    getValueLength(value: any): number;
}

declare class FpJsFormElement
{
    domNode: HTMLInputElement;
    children: {[name: string]: FpJsFormElement};

    get(path: string): FpJsFormElement;
    getData(): any;
    getValue(): any;
    onValidate(this: HTMLElement, errors: Object, event: Event): void
}

declare class FpJsFormError
{
    greeting: string;
    atPath: string;

    constructor(message: string, atPath?: string);
}

interface FpJsDomUtility
{
    previousElementSibling(element: Element): HTMLElement;
    nextElementSibling(element: Element): HTMLElement;
    hasClass(element: Element, className: string): boolean;
    removeClass(element: Element, className: string): void;
    addClass(element: Element, className: string): void;
    getActiveElement(): HTMLElement;
}

interface ConstraintValidator
{
    validate(value: any, element: FpJsFormElement): (FpJsFormError|string)[];
}

declare const FpJsFormValidator: FpJsFormValidator;
declare const FpJsDomUtility: FpJsDomUtility;
