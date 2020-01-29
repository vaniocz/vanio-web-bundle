import '@fp_js_form_validator';

function resolveTabLinkTarget(link: JQuery|HTMLElement): string
{
    const $link = $(link);
    const href = $link.attr('href') || '';
    let target = $link.data('target');

    return target || href.trim()[0] !== '#' ? target : href;
}

function findTabsByPanes($panes: JQuery, parent?: JQuery|HTMLElement): JQuery
{
    let $selectedTabs = $();
    const $links = $('.nav-tabs li a', parent);

    $panes.each((index: number, pane: HTMLElement) => {
        const $pane = $(pane);

        $links.each((index: number, link: HTMLElement) => {
            if ($pane.is(resolveTabLinkTarget(link))) {
                $selectedTabs = $selectedTabs.add($(link).closest('li'));
            }
        });
    });

    return $selectedTabs;
}

function findPanesByLinks($links: JQuery, parent?: JQuery|HTMLElement): JQuery
{
    let $selectedPanes = $();
    const $panes = $('.tab-pane', parent);

    $panes.each((index: number, pane: HTMLElement) => {
        const $pane = $(pane);

        $links.each((index: number, link: HTMLElement) => {
            const $link = $(link);

            if ($pane.is(resolveTabLinkTarget(link))) {
                $selectedPanes = $selectedPanes.add($pane);
            }
        });
    });

    return $selectedPanes;
}

function selectSpecificOrFirstInvalidTab($specificTabs: JQuery, $invalidTabs: JQuery): void
{
    $invalidTabs = $invalidTabs.add($specificTabs);
    $invalidTabs.each((index: number, invalidTab: HTMLElement) => {
        const $invalidTab = $(invalidTab);
        const $link = $(invalidTab).find('a');

        if (
            $invalidTab.is($specificTabs) || (
                !$invalidTab.prevAll().filter($invalidTabs).length
                && !$invalidTab.siblings().filter($specificTabs).length
            )
        ) {
            $invalidTab.addClass(FpJsFormValidator.hasErrorClass);
            $link.click();
        }
    });
}

function compareDocumentPosition(a: HTMLElement, b: HTMLElement): number
{
    if (a === b) {
        return 0;
    }

    return a.compareDocumentPosition(b) & Node.DOCUMENT_POSITION_FOLLOWING ? -1 : 1;
}

FpJsFormValidator.insertMethod = 'after';

$(() => {
    window.setTimeout(() => {
        const $invalidFieldToFocus = $(`
            .${FpJsFormValidator.hasErrorClass}:input,
            .${FpJsFormValidator.hasErrorClass} :input
        `).first();

        const $tabsToFocus = findTabsByPanes($invalidFieldToFocus.parents('.tab-pane'));
        const $invalidTabs = findTabsByPanes(findPanesByLinks($(`.nav-tabs li.${FpJsFormValidator.hasErrorClass} a`)));
        selectSpecificOrFirstInvalidTab($tabsToFocus, $invalidTabs);
        $invalidFieldToFocus.focus();
    }, 0);
});

$(document.body).on('fp_js_form_validator_validate', (event: JQuery.Event) => {
    const errors: {[id: string]: string[]} = (event.detail as any).errors;
    const $form = $(event.target);
    let $invalidFieldToFocus = $();
    let $invalidTabs = $();
    let invalidFields = [];

    for (const id of Object.keys(errors)) {
        const $element = $(document.getElementById(id)!);
        const $field = $element.is(':input') ? $element : $element.find(':input');
        invalidFields.push($field[0]);

        if (!$invalidFieldToFocus.length && $field.is(':focus')) {
            $invalidFieldToFocus = $field;
        }

        $invalidTabs = $invalidTabs.add(findTabsByPanes($element.parents('.tab-pane'), $form));
    }

    if (!$invalidFieldToFocus.length && invalidFields.length) {
        $invalidFieldToFocus = $(invalidFields.sort(compareDocumentPosition)[0]);
    }

    const $tabsToFocus = findTabsByPanes($invalidFieldToFocus.parents('.tab-pane'), $form);
    $form.find('.nav-tabs li').removeClass(FpJsFormValidator.hasErrorClass);
    selectSpecificOrFirstInvalidTab($tabsToFocus, $invalidTabs);
    $invalidTabs.addClass(FpJsFormValidator.hasErrorClass);
    $invalidFieldToFocus.focus();
});
