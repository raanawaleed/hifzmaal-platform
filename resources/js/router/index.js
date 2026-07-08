import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useFamilyStore } from '@/stores/family'

const routes = [
  // ── Guest ──
  { path: '/login', name: 'login', component: () => import('@/views/auth/LoginView.vue'), meta: { guest: true } },
  { path: '/register', name: 'register', component: () => import('@/views/auth/RegisterView.vue'), meta: { guest: true } },
  { path: '/forgot-password', name: 'forgot-password', component: () => import('@/views/auth/ForgotPasswordView.vue'), meta: { guest: true } },
  { path: '/reset-password', name: 'reset-password', component: () => import('@/views/auth/ResetPasswordView.vue'), meta: { guest: true } },

  // ── Public regardless of auth state ──
  { path: '/', name: 'landing', component: () => import('@/views/LandingView.vue'), meta: { public: true } },
  { path: '/terms', name: 'terms', component: () => import('@/views/legal/TermsView.vue'), meta: { public: true } },
  { path: '/privacy', name: 'privacy', component: () => import('@/views/legal/PrivacyView.vue'), meta: { public: true } },
  {
    path: '/invitations/accept',
    name: 'invitation-accept',
    component: () => import('@/views/invitations/AcceptInvitationView.vue'),
    meta: { public: true },
  },

  // ── Authenticated ──
  { path: '/dashboard', name: 'dashboard', component: () => import('@/views/DashboardView.vue') },
  { path: '/profile', name: 'profile', component: () => import('@/views/ProfileView.vue') },
  { path: '/billing', name: 'billing', component: () => import('@/views/billing/BillingView.vue') },

  // Accounts
  { path: '/accounts', name: 'accounts', component: () => import('@/views/accounts/AccountsList.vue') },
  { path: '/accounts/create', name: 'accounts-create', component: () => import('@/views/accounts/AccountForm.vue') },
  { path: '/accounts/:id/edit', name: 'accounts-edit', component: () => import('@/views/accounts/AccountForm.vue'), props: true },

  // Transactions
  { path: '/transactions', name: 'transactions', component: () => import('@/views/transactions/TransactionsList.vue') },
  { path: '/transactions/create', name: 'transactions-create', component: () => import('@/views/transactions/TransactionForm.vue') },
  { path: '/transactions/:id/edit', name: 'transactions-edit', component: () => import('@/views/transactions/TransactionForm.vue'), props: true },
  { path: '/transactions/pending', name: 'transactions-pending', component: () => import('@/views/transactions/PendingApprovals.vue') },

  // Bills
  { path: '/bills', name: 'bills', component: () => import('@/views/bills/BillsList.vue') },
  { path: '/bills/create', name: 'bills-create', component: () => import('@/views/bills/BillForm.vue') },
  { path: '/bills/:id/edit', name: 'bills-edit', component: () => import('@/views/bills/BillForm.vue'), props: true },
  { path: '/bills/statistics', name: 'bills-statistics', component: () => import('@/views/bills/BillsStatistics.vue') },

  // Budgets
  { path: '/budgets', name: 'budgets', component: () => import('@/views/budgets/BudgetsList.vue') },
  { path: '/budgets/create', name: 'budgets-create', component: () => import('@/views/budgets/BudgetForm.vue') },
  { path: '/budgets/:id/edit', name: 'budgets-edit', component: () => import('@/views/budgets/BudgetForm.vue'), props: true },

  // Categories
  { path: '/categories', name: 'categories', component: () => import('@/views/categories/CategoriesList.vue') },
  { path: '/categories/create', name: 'categories-create', component: () => import('@/views/categories/CategoryForm.vue') },
  { path: '/categories/:id/edit', name: 'categories-edit', component: () => import('@/views/categories/CategoryForm.vue'), props: true },

  // Families
  { path: '/families', name: 'families', component: () => import('@/views/families/FamiliesList.vue') },
  { path: '/families/create', name: 'families-create', component: () => import('@/views/families/FamilyForm.vue') },
  { path: '/families/:id/edit', name: 'families-edit', component: () => import('@/views/families/FamilyForm.vue'), props: true },

  // Family Members
  { path: '/family-members', name: 'family-members', component: () => import('@/views/members/MembersList.vue') },
  { path: '/family-members/create', name: 'family-members-create', component: () => import('@/views/members/MemberForm.vue') },
  { path: '/family-members/:id/edit', name: 'family-members-edit', component: () => import('@/views/members/MemberForm.vue'), props: true },

  // Savings Goals
  { path: '/savings-goals', name: 'savings-goals', component: () => import('@/views/savings/SavingsList.vue') },
  { path: '/savings-goals/create', name: 'savings-create', component: () => import('@/views/savings/SavingsForm.vue') },
  { path: '/savings-goals/:id/edit', name: 'savings-edit', component: () => import('@/views/savings/SavingsForm.vue'), props: true },

  // Zakat
  { path: '/zakat', name: 'zakat', component: () => import('@/views/zakat/ZakatList.vue') },
  { path: '/zakat/create', name: 'zakat-create', component: () => import('@/views/zakat/ZakatForm.vue') },
  { path: '/zakat/recipients', name: 'zakat-recipients', component: () => import('@/views/zakat/RecipientsList.vue') },
  { path: '/zakat/recipients/create', name: 'zakat-recipients-create', component: () => import('@/views/zakat/RecipientForm.vue') },
  { path: '/zakat/recipients/:id/edit', name: 'zakat-recipients-edit', component: () => import('@/views/zakat/RecipientForm.vue'), props: true },
  { path: '/zakat/:id', name: 'zakat-detail', component: () => import('@/views/zakat/ZakatDetail.vue'), props: true },

  // ── Superadmin panel ──
  { path: '/admin', name: 'admin-dashboard', component: () => import('@/views/admin/AdminDashboard.vue'), meta: { superadmin: true } },
  { path: '/admin/users', name: 'admin-users', component: () => import('@/views/admin/AdminUsersList.vue'), meta: { superadmin: true } },
  { path: '/admin/users/:id', name: 'admin-user-detail', component: () => import('@/views/admin/AdminUserDetail.vue'), props: true, meta: { superadmin: true } },
  { path: '/admin/families', name: 'admin-families', component: () => import('@/views/admin/AdminFamiliesList.vue'), meta: { superadmin: true } },
  { path: '/admin/categories', name: 'admin-categories', component: () => import('@/views/admin/AdminCategoriesList.vue'), meta: { superadmin: true } },
  { path: '/admin/settings', name: 'admin-settings', component: () => import('@/views/admin/AdminSettings.vue'), meta: { superadmin: true } },

  // 404
  { path: '/:pathMatch(.*)*', name: 'not-found', component: () => import('@/views/ErrorView.vue') },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior() {
    return { top: 0 }
  },
})

router.beforeEach(async (to) => {
  const authStore = useAuthStore()

  // Accessible regardless of auth state — e.g. the invitation preview,
  // which needs to work for someone who doesn't have an account yet.
  if (to.meta.public) {
    return
  }

  if (!to.meta.guest && !authStore.token) {
    return { name: 'login', query: { redirect: to.fullPath } }
  }

  if (to.meta.guest && authStore.token) {
    return { name: authStore.isSuperAdmin ? 'admin-dashboard' : 'dashboard' }
  }

  if (to.meta.superadmin && !authStore.isSuperAdmin) {
    return { name: 'dashboard' }
  }

  // Resolve families BEFORE any family-scoped view mounts. This kills the
  // race where views checked hasFamily() while the list was still loading
  // and rendered permanently empty.
  if (!to.meta.guest && !to.meta.superadmin && authStore.token) {
    const familyStore = useFamilyStore()
    await familyStore.ensureLoaded()
  }
})

export default router
