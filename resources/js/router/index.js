import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const routes = [
  // ── Guest ──
  { path: '/login', name: 'login', component: () => import('@/views/auth/LoginView.vue'), meta: { guest: true } },
  { path: '/register', name: 'register', component: () => import('@/views/auth/RegisterView.vue'), meta: { guest: true } },

  // ── Authenticated ──
  { path: '/', redirect: '/dashboard' },
  { path: '/dashboard', name: 'dashboard', component: () => import('@/views/DashboardView.vue') },
  { path: '/profile', name: 'profile', component: () => import('@/views/ProfileView.vue') },

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

router.beforeEach((to) => {
  const authStore = useAuthStore()

  if (!to.meta.guest && !authStore.token) {
    return { name: 'login' }
  }
  if (to.meta.guest && authStore.token) {
    return { name: 'dashboard' }
  }
})

export default router
