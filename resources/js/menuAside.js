import {
  mdiMonitor,
  mdiWallet,
  mdiSwapHorizontal,
  mdiClockOutline,
  mdiReceiptText,
  mdiChartPie,
  mdiPiggyBank,
  mdiHandCoin,
  mdiHomeGroup,
  mdiAccountGroup,
  mdiTagMultiple,
} from '@mdi/js'

export const menuAsideMain = [
  {
    to: '/dashboard',
    icon: mdiMonitor,
    label: 'Dashboard',
  },
  {
    to: '/accounts',
    icon: mdiWallet,
    label: 'Accounts',
  },
  {
    to: '/transactions',
    icon: mdiSwapHorizontal,
    label: 'Transactions',
  },
  {
    to: '/transactions/pending',
    icon: mdiClockOutline,
    label: 'Pending Approvals',
  },
  {
    to: '/bills',
    icon: mdiReceiptText,
    label: 'Bills',
  },
  {
    to: '/budgets',
    icon: mdiChartPie,
    label: 'Budgets',
  },
  {
    to: '/savings-goals',
    icon: mdiPiggyBank,
    label: 'Savings Goals',
  },
  {
    to: '/zakat',
    icon: mdiHandCoin,
    label: 'Zakat',
  },
]

export const menuAsideBottom = [
  {
    to: '/families',
    icon: mdiHomeGroup,
    label: 'Families',
  },
  {
    to: '/family-members',
    icon: mdiAccountGroup,
    label: 'Members',
  },
  {
    to: '/categories',
    icon: mdiTagMultiple,
    label: 'Categories',
  },
]
