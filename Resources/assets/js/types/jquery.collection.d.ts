interface JQueryCollectionOptions
{
    elements_selector?: string;
    maintain_indices?: Function;
    prefix?: string;
}

interface JQuery
{
    collection(options?: JQueryCollectionOptions): JQuery;
}
