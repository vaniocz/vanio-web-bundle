import global from '@vanio_web/js/global';

@global('SymfonyComponentSecurityCoreValidatorConstraintsUserPassword')
export default class UserPassword implements ConstraintValidator
{
    public message: string;

    public validate(value: any, element: FpJsFormElement): string[]
    {
        return FpJsFormValidator.getValueLength(value) < 2 ? [this.message] : [];
    }
}
