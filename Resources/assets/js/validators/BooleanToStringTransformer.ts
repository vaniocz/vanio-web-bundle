import global from '@vanio_web/js/global';

@global('VanioWebBundleFormBooleanToStringTransformer')
class BooleanToStringTransformer
{
    private falseValues: string[];

    public reverseTransform(value: string|null): boolean|null
    {
        return value == null ? null : this.falseValues.indexOf(value) === -1;
    }
}
