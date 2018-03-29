import '@fp_js_form_validator';
import '@vanio_web/js/validators/UserPassword';

function findTabsByPanes($panes: JQuery): JQuery
{
    let $tabs = $();

    $panes.each((index: number, pane: HTMLAnchorElement) => {
        const $pane = $(pane);

        $pane.closest('.tabbable').find('> .nav-tabs > li > a').each((index: number, link: HTMLAnchorElement) => {
            const $link = $(link);

            if ($pane.is($link.data('target') || $link.attr('href'))) {
                $tabs = $tabs.add($link.parent());

                return false;
            }
        });
    });

    return $tabs;
}

FpJsFormElement.prototype.onValidate = function (errors: any[]): void
{
    let $invalidFieldToFocus = $();
    let $invalidTabs = $();

    for (const id of Object.keys(errors)) {
        const $element = $(document.getElementById(id)!);
        const $field = $element.is(':input') ? $element : $element.find(':input')

        if (!$invalidFieldToFocus.length || $field.is(':focus')) {
            $invalidFieldToFocus = $field;
        }

        $invalidTabs = $invalidTabs.add(findTabsByPanes($element.closest('.tab-pane')));
    }

    const $tabsToFocus = findTabsByPanes($invalidFieldToFocus.parents('.tab-pane'));
    $invalidTabs.closest('.tabbable').find('> .nav-tabs > li').removeClass(FpJsFormValidator.hasErrorClass);

    $invalidTabs.each((index: number, invalidTab: HTMLElement) => {
        const $invalidTab = $(invalidTab);
        const $link = $(invalidTab).find('> a');

        if (
            $invalidTab.is($tabsToFocus) || (
                !$invalidTab.prevAll().filter($invalidTabs).length
                && !$invalidTab.siblings().filter($tabsToFocus).length
            )
        ) {
            $link.click();
        }
    });

    $invalidTabs.addClass(FpJsFormValidator.hasErrorClass);
    $invalidFieldToFocus.focus();
};

FpJsFormValidator.insertMethod = 'after';
