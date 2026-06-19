import { createRouter, createWebHistory } from 'vue-router'
import { useAuth } from '../composables/useAuth.js'

import LoginView from '../views/LoginView.vue'
import RegisterView from '../views/RegisterView.vue'
import PublicDashboardView from '../views/PublicDashboardView.vue'
import DashboardView from '../views/DashboardView.vue'
import HouseholdList from '../views/households/HouseholdList.vue'
import HouseholdCreatePage from '../views/households/HouseholdCreatePage.vue'
import TrackingView from '../views/TrackingView.vue'
import QuotaList from '../views/quotas/QuotaList.vue'
import AllocationList from '../views/allocations/AllocationList.vue'
import FollowupList from '../views/followups/FollowupList.vue'
import ReportView from '../views/reports/ReportView.vue'
import ProfileView from '../views/ProfileView.vue'
import LoginHistoryView from '../views/LoginHistoryView.vue'
import UserManagementView from '../views/admin/UserManagementView.vue'

const routes = [
    // Public landing (no auth)
    { path: '/app',          component: PublicDashboardView, meta: { public: true } },
    { path: '/app/public',   component: PublicDashboardView, meta: { public: true } },
    { path: '/app/login',    component: LoginView, meta: { guest: true } },
    { path: '/app/register', component: RegisterView, meta: { public: true } },

    // Auth-required app shell
    {
        path: '/app',
        component: () => import('../views/MainLayout.vue'),
        meta: { requiresAuth: true },
        children: [
            { path: 'dashboard', component: DashboardView },

            // Households
            { path: 'households',         component: HouseholdList },
            { path: 'households/create',  component: HouseholdCreatePage },

            // Tracking
            { path: 'tracking', component: TrackingView },

            // Mushroom (parent group)
            { path: 'mushroom', redirect: '/app/mushroom/quotas' },
            { path: 'mushroom/quotas',                component: QuotaList },
            { path: 'mushroom/allocations',           component: AllocationList },
            { path: 'mushroom/followups',             component: FollowupList },

            // Backwards compat (old paths)
            { path: 'quotas',      redirect: '/app/mushroom/quotas' },
            { path: 'allocations', redirect: '/app/mushroom/allocations' },
            { path: 'followups',   redirect: '/app/mushroom/followups' },

            // Marketplace backoffice
            { path: 'marketing', redirect: '/app/market' },
            { path: 'market',                component: () => import('../views/market/MarketDashboard.vue') },
            { path: 'market/products',       component: () => import('../views/market/ProductManagement.vue') },
            { path: 'market/categories',     component: () => import('../views/market/CategoryManagement.vue') },
            { path: 'market/orders',         component: () => import('../views/market/OrderManagement.vue') },
            { path: 'market/payments',       component: () => import('../views/market/PaymentConfirmation.vue') },
            { path: 'market/returns',        component: () => import('../views/market/ReturnManagement.vue') },
            { path: 'market/reviews',        component: () => import('../views/market/ReviewManagement.vue') },
            { path: 'market/comments',       component: () => import('../views/market/CommentManagement.vue') },
            { path: 'market/seller-groups',  component: () => import('../views/market/SellerGroupManagement.vue') },

            // Reports
            { path: 'reports',   component: ReportView },

            // User account
            { path: 'profile',        component: ProfileView },
            { path: 'login-history',  component: LoginHistoryView },

            // Admin
            { path: 'admin/users', component: UserManagementView },
        ],
    },
    // ===== Storefront (public shop at /shop) =====
    {
        path: '/shop',
        component: () => import('../views/shop/StorefrontLayout.vue'),
        meta: { public: true },
        children: [
            { path: '',                       component: () => import('../views/shop/ShopHome.vue') },
            { path: 'products',               component: () => import('../views/shop/ProductCatalog.vue') },
            { path: 'products/:slug',         component: () => import('../views/shop/ProductDetail.vue') },
            { path: 'cart',                   component: () => import('../views/shop/CartView.vue') },
            { path: 'login',                  component: () => import('../views/shop/CustomerLogin.vue'), meta: { guestShop: true } },
            { path: 'register',               component: () => import('../views/shop/CustomerRegister.vue'), meta: { guestShop: true } },
            { path: 'checkout',               component: () => import('../views/shop/CheckoutView.vue'), meta: { customerAuth: true } },
            { path: 'account/orders',         component: () => import('../views/shop/AccountOrders.vue'), meta: { customerAuth: true } },
            { path: 'account/orders/:orderNo', component: () => import('../views/shop/OrderDetail.vue'), meta: { customerAuth: true } },
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

    if (user.value === null && (to.meta.requiresAuth || to.meta.guest || to.meta.customerAuth || to.meta.guestShop)) {
        await fetchUser()
    }
    if (to.meta.requiresAuth && !user.value) return '/app/login'
    if (to.meta.guest && user.value) return '/app/dashboard'

    // Storefront customer guards
    if (to.meta.customerAuth && !user.value) {
        return { path: '/shop/login', query: { redirect: to.fullPath } }
    }
    if (to.meta.guestShop && user.value) return '/shop'
})

export default router
