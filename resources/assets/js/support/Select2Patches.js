/**
 * Patch select2 keyboard navigation[TAB] issue.
 *
 * @param  jQuery  node
 * @return void
 */
export function applyKeyboardNavigationPatch(node)
{
    node.off('select2:close').on('select2:close', function () {
        $(this).trigger('focus');
    });
}
