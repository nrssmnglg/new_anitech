import { computed, ref } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import { submitOfficeLogin } from '../../../Services/auth/loginService';

export function useOfficeLogin(loginUrl) {
    const page = usePage();
    const showPassword = ref(false);
    const form = useForm({
        email: '',
        password: '',
        remember: false,
    });

    const flashStatus = computed(() => page.props.flash?.status ?? page.props.flash?.success ?? null);
    const errorList = computed(() => {
        const errors = page.props.errors ?? {};

        return Object.values(errors).flat().filter(Boolean);
    });

    function submit() {
        return submitOfficeLogin(form, loginUrl);
    }

    return {
        errorList,
        flashStatus,
        form,
        showPassword,
        submit,
    };
}
