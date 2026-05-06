import { createRouter, createWebHistory } from 'vue-router'
import { useAuth } from '../composables/useAuth.js'

import LoginView from '../views/LoginView.vue'
import DashboardView from '../views/DashboardView.vue'
import HouseholdList from '../views/households/HouseholdList.vue'
import TrackingView from '../views/TrackingView.vue'
import QuotaList from '../views/quotas/QuotaList.vue'
import AllocationList from '../views/allocations/AllocationList.vue'
import AllocationForm from '../views/allocations/AllocationForm.vue'
import FollowupList from '../views/followups/FollowupList.vue'
import FollowupForm from '../views/followups/FollowupForm.vue'
import MarketingHome from '../views/marketing/MarketingHome.vue'
import ReportView from '../views/reports/ReportView.vue'
import ProfileView from '../views/ProfileView.vue'
import LoginHistoryView from '../views/LoginHistoryView.vue'
import UserManagementView from '../views/admin/UserManagementView.vue'

const routes = [
    { path: '/app/login', component: LoginView, meta: { guest: true } },
    {
        path: '/app',
        component: () => import('../views/MainLayout.vue'),
        meta: { requiresAuth: true },
        children: [
            { path: '', redirect: '/app/dashboard' },
            { path: 'dashboard', component: DashboardView },

            // Households
            { path: 'households',         component: HouseholdList },
            { path: 'households/create',  component: HouseholdList, props: { autoCreate: true } },

            // Tracking
            { path: 'tracking', component: TrackingView },

            // Mushroom (parent group)
            { path: 'mushroom', redirect: '/app/mushroom/quotas' },
            { path: 'mushroom/quotas',                component: QuotaList },
            { path: 'mushroom/allocations',           component: AllocationList },
            { path: 'mushroom/allocations/create',    component: AllocationForm },
            { path: 'mushroom/allocations/:id/edit',  component: AllocationForm },
            { path: 'mushroom/followups',             component: FollowupList },
            { path: 'mushroom/followups/create',      component: FollowupForm },
            { path: 'mushroom/followups/:id/edit',    component: FollowupForm },

            // Backwards compat (old paths)
            { path: 'quotas',      redirect: '/app/mushroom/quotas' },
            { path: 'allocations', redirect: '/app/mushroom/allocations' },
            { path: 'followups',   redirect: '/app/mushroom/followups' },

            // Marketing & Reports
            { path: 'marketing', component: MarketingHome },
            { path: 'reports',   component: ReportView },

            // User account
            { path: 'profile',        component: ProfileView },
            { path: 'login-history',  component: LoginHistoryView },

            // Admin
            { path: 'admin/users', component: UserManagementView },
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
    if (to.meta.requiresAuth && !user.value) return '/app/login'
    if (to.meta.guest && user.value) return '/app/dashboard'
})

export default router
