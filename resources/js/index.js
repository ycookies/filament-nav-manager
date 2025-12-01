const resolveRecordId = (row) => {
    if (!row) {
        return null;
    }

    const dataId = row.dataset?.recordId ?? row.dataset?.key ?? row.dataset?.id;

    if (dataId) {
        return dataId;
    }

    const wireKey = row.getAttribute('wire:key');

    if (!wireKey) {
        return null;
    }

    const segments = wireKey.split('.');

    return segments.pop() || null;
};

document.addEventListener('click', (event) => {
    const treeCell = event.target.closest('.tree-title');

    if (!treeCell) {
        return;
    }

    const treeRow = treeCell.closest('tr');

    if (!treeRow) {
        return;
    }

    const recordId = resolveRecordId(treeRow);

    if (!recordId) {
        return;
    }

    const childRows = document.querySelectorAll(`.pr-${recordId}`);

    if (!childRows.length) {
        return;
    }

    const isExpanded = Array.from(childRows).every((element) => ! element.classList.contains('hidden'));

    childRows.forEach((element) => {
        element.classList.toggle('hidden', isExpanded);
    });
});