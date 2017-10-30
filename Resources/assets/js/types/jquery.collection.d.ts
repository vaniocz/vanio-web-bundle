interface JQueryCollectionOptions
{
    elements_selector?: string;
    maintain_indices?: Function;
}

interface JQuery
{
    collection(options?: JQueryCollectionOptions): JQuery;
}
