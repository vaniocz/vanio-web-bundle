interface JQueryCollectionOptions
{
    position_field_selector?: string|boolean;
}

interface JQuery
{
    collection(options?: JQueryCollectionOptions): JQuery;
}
