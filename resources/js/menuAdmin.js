import {
  mdiMonitor,
  mdiAccountMultiple,
  mdiHomeGroup,
  mdiTagMultiple,
  mdiCog,
  mdiArrowLeft,
} from '@mdi/js'

export const menuAdminMain = [
  {
    to: '/admin',
    icon: mdiMonitor,
    label: 'Overview',
  },
  {
    to: '/admin/users',
    icon: mdiAccountMultiple,
    label: 'Users',
  },
  {
    to: '/admin/families',
    icon: mdiHomeGroup,
    label: 'Families',
  },
  {
    to: '/admin/categories',
    icon: mdiTagMultiple,
    label: 'System Categories',
  },
  {
    to: '/admin/settings',
    icon: mdiCog,
    label: 'Zakat Settings',
  },
]

export const menuAdminBottom = [
  {
    to: '/dashboard',
    icon: mdiArrowLeft,
    label: 'Back to App',
  },
]
