interface GritterOptions
{
    position?: string,
    class_name?: string,
    fade_in_speed?: number|string,
    fade_out_speed?: number|string,
    time?: number;
    title?: string;
    text?: string;
}

interface Gritter
{
    options: GritterOptions;
    add(options: GritterOptions): void;
}

interface JQueryStatic
{
    gritter: Gritter;
}
