export const syncAssociationOptions = (barangaySelect, associationSelect) => {
    const barangayId = barangaySelect.value;
    const visibleOptions = [];

    Array.from(associationSelect.options).forEach((option, index) => {
        if (index === 0) {
            option.hidden = false;
            return;
        }

        const matches = !barangayId || option.dataset.barangay === barangayId;
        option.hidden = !matches;

        if (matches) {
            visibleOptions.push(option);
        }
    });

    if (!barangayId) {
        associationSelect.value = '';
        return;
    }

    if (visibleOptions.length === 1) {
        associationSelect.value = visibleOptions[0].value;
        return;
    }

    const selectedOption = associationSelect.selectedOptions[0];
    const hasVisibleSelected = selectedOption && !selectedOption.hidden;

    if (!hasVisibleSelected) {
        associationSelect.value = '';
    }
};

export const bindAssociationFilter = (barangaySelect, associationSelect) => {
    if (!barangaySelect || !associationSelect) {
        return;
    }

    const handleSync = () => syncAssociationOptions(barangaySelect, associationSelect);

    barangaySelect.addEventListener('change', handleSync);
    handleSync();
};
