import { createRouter, createWebHistory } from 'vue-router'
import { useAuth } from '../composables/useAuth.js'

import LoginView from '../views/LoginView.vue'
import DashboardView from '../views/DashboardView.vue'
import QuotaList from '../views/quotas/QuotaList.vue'
import QuotaForm from '../views/quotas/QuotaForm.vue'
import AllocationList from '../views/allocations/AllocationList.vue'
import AllocationForm from '../views/allocations/AllocationForm.vue'
import FollowupList from '../views/followups/FollowupList.vue'
import FollowupForm from '../views/followups/FollowupForm.vue'
import ReportView from '../views/reports/ReportView.vue'

const routes = [
    { path: '/app/login', component: LoginView, meta: { guest: true } },
    {
        path: '/app',
        component: () => import('../views/MainLayout.vue'),
        meta: { requiresAuth: true },
        children: [
            { path: '', redirect: '/app/dashboard' },
            { path: 'dashboard', component: DashboardView },
            { path: 'quotas', component: QuotaList },
            { path: 'quotas/create', component: QuotaForm },
            { path: 'quotas/:id/edit', component: QuotaForm },
            { path: 'allocations', component: AllocationList },
            { path: 'allocations/create', component: AllocationForm },
            { path: 'allocations/:id/edit', component: AllocationForm },
            { path: 'followups', component: FollowupList },
            { path: 'followups/create', component: FollowupForm },
            { path: 'followups/:id/edit', component: FollowupForm },
            { path: 'reports', component: ReportView },
        ],
    },
    { path: '/:pathMatch(.*)*', redirect: '/app' },
]

const router = createRouter({
    history: createWebHistory(),
    routes,
})

router.beforeEach(async (to) => {
    const { user, fetchUser } = useAuth()

    if (user.value === null && to.meta.requiresAuth) {
        await fetchUser()
    }

    if (to.meta.requiresAuth && !user.value) {
        return '/app/login'
    }

    if (to.meta.guest && user.value) {
        return '/app/dashboard'
    }
})

export default router
