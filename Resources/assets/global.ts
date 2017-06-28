export default function global(property: string): any
{
    return (target: Function): void => {
        (window as any)[property] = target;
    }
}
