import { bindAssociationFilter } from './shared/association-select';

const bootFarmerForm = () => {
    const form = document.querySelector('form[action$="/admin/farmers"], form[action*="/admin/farmers/"]');
    const barangaySelect = document.getElementById('barangay_id');
    const associationSelect = document.getElementById('association_id');

    if (!form || form.dataset.farmerFormBound === 'true') {
        return;
    }

    form.dataset.farmerFormBound = 'true';

    bindAssociationFilter(barangaySelect, associationSelect);

    const firstEditableField = form.querySelector(
        'input:not([type="hidden"]):not([readonly]), select, textarea'
    );

    if (firstEditableField && !document.querySelector('.flash-error')) {
        firstEditableField.focus({ preventScroll: true });
    }

    form.addEventListener('submit', (event) => {
        if (form.dataset.submitting === 'true') {
            event.preventDefault();
            return;
        }

        if (typeof form.checkValidity === 'function' && !form.checkValidity()) {
            return;
        }

        form.dataset.submitting = 'true';

        window.setTimeout(() => {
            form.querySelectorAll('button[type="submit"]').forEach((button) => {
                button.disabled = true;
            });
        }, 0);
    });
};

document.addEventListener('DOMContentLoaded', bootFarmerForm);
