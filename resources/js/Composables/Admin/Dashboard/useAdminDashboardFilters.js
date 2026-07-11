import { computed, reactive } from 'vue';

export function useAdminDashboardFilters(filters) {
    const state = reactive({
        year: String(filters.selectedYear ?? ''),
        barangayId: filters.selectedBarangayId ? String(filters.selectedBarangayId) : '',
    });

    const selectedBarangayName = computed(() => {
        if (!state.barangayId) {
            return 'All barangays';
        }

        return filters.barangays.find((barangay) => String(barangay.id) === state.barangayId)?.name ?? 'All barangays';
    });

    function apply() {
        const url = new URL(filters.baseUrl, window.location.origin);

        if (state.year) {
            url.searchParams.set('year', state.year);
        }

        if (state.barangayId) {
            url.searchParams.set('barangay_id', state.barangayId);
        }

        window.location.assign(url.toString());
    }

    return {
        apply,
        selectedBarangayName,
        state,
    };
}
