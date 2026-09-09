import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import FarmerShellLayout from '../layouts/FarmerShellLayout.vue';
import LoginPage from '../pages/LoginPage.vue';
import DashboardPage from '../pages/DashboardPage.vue';
import ProfilePage from '../pages/ProfilePage.vue';
import MembershipPage from '../pages/MembershipPage.vue';
import RenewalsPage from '../pages/RenewalsPage.vue';
import PaymentsPage from '../pages/PaymentsPage.vue';
import AdvisoriesPage from '../pages/AdvisoriesPage.vue';
import AdvisoryDetailPage from '../pages/AdvisoryDetailPage.vue';
import InquiriesPage from '../pages/InquiriesPage.vue';
import InquiryDetailPage from '../pages/InquiryDetailPage.vue';
import NotificationsPage from '../pages/NotificationsPage.vue';
import AccountSetupPage from '../pages/AccountSetupPage.vue';
import ForgotPasswordPage from '../pages/ForgotPasswordPage.vue';
import ResetVerifyPage from '../pages/ResetVerifyPage.vue';
import NewPasswordPage from '../pages/NewPasswordPage.vue';
import ApplyPage from '../pages/ApplyPage.vue';
import UploadPage from '../pages/UploadPage.vue';
import TrackPage from '../pages/TrackPage.vue';
import UploadCompletePage from '../pages/UploadCompletePage.vue';
import ApplicationPaymentQrPage from '../pages/ApplicationPaymentQrPage.vue';
import { resolveFarmerAppBasePath } from '../utils/paths';

const appBasePath = `${resolveFarmerAppBasePath()}/`;

const router = createRouter({
    history: createWebHistory(appBasePath),
    routes: [
        {
            path: '/apply',
            name: 'apply',
            component: ApplyPage,
        },
        {
            path: '/upload',
            name: 'upload',
            component: UploadPage,
        },
        {
            path: '/upload-complete',
            name: 'upload-complete',
            component: UploadCompletePage,
        },
        {
            path: '/track',
            name: 'track',
            component: TrackPage,
        },
        {
            path: '/track-status',
            name: 'track-status',
            component: TrackPage,
        },
        {
            path: '/payment/qr',
            name: 'application-payment-qr',
            component: ApplicationPaymentQrPage,
        },
        {
            path: '/login',
            name: 'login',
            component: LoginPage,
            meta: { guestOnly: true },
        },
        {
            path: '/setup-account',
            name: 'account-setup',
            component: AccountSetupPage,
        },
        {
            path: '/forgot-password',
            name: 'forgot-password',
            component: ForgotPasswordPage,
            meta: { guestOnly: true },
        },
        {
            path: '/reset-password/verify',
            name: 'reset-verify',
            component: ResetVerifyPage,
            meta: { guestOnly: true },
            beforeEnter: (to) => {
                if (!String(to.query.email ?? '').trim()) {
                    return { name: 'forgot-password' };
                }

                return true;
            },
        },
        {
            path: '/reset-password/new',
            name: 'reset-new-password',
            component: NewPasswordPage,
            meta: { guestOnly: true },
            beforeEnter: (to) => {
                if (!String(to.query.email ?? '').trim()) {
                    return { name: 'forgot-password' };
                }

                return true;
            },
        },
        {
            path: '/',
            component: FarmerShellLayout,
            meta: { requiresAuth: true },
            children: [
                { path: '', name: 'dashboard', component: DashboardPage },
                { path: 'profile', name: 'profile', component: ProfilePage },
                { path: 'membership', name: 'membership', component: MembershipPage },
                { path: 'renewals', name: 'renewals', component: RenewalsPage },
                { path: 'payments', name: 'payments', component: PaymentsPage },
                { path: 'advisories', name: 'advisories', component: AdvisoriesPage },
                { path: 'advisories/:advisoryId', name: 'advisory-detail', component: AdvisoryDetailPage, props: true },
                { path: 'inquiries', name: 'inquiries', component: InquiriesPage },
                { path: 'inquiries/:inquiryId', name: 'inquiry-detail', component: InquiryDetailPage, props: true },
                { path: 'notifications', name: 'notifications', component: NotificationsPage },
            ],
        },
    ],
});

router.beforeEach(async (to) => {
    const auth = useAuthStore();

    if (!auth.booted) {
        return true;
    }

    if (!auth.initialized) {
        await auth.restore();
    }

    if (to.meta.requiresAuth && !auth.isAuthenticated) {
        return {
            name: 'login',
            query: {
                redirect: to.fullPath,
            },
        };
    }

    if (to.meta.guestOnly && auth.isAuthenticated) {
        return { name: 'dashboard' };
    }

    return true;
});

export default router;
