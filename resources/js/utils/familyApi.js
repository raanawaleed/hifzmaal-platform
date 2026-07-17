import api from '@/utils/api'
import { useFamilyStore } from '@/stores/family'

// Family-scoped API helper — prefixes every path with /families/{currentFamilyId}
export function useFamilyApi() {
  const familyStore = useFamilyStore()

  const base = () => {
    if (!familyStore.currentFamilyId) {
      throw new Error('No family selected')
    }
    return `/families/${familyStore.currentFamilyId}`
  }

  return {
    get: (path, config) => api.get(`${base()}${path}`, config),
    post: (path, data, config) => api.post(`${base()}${path}`, data, config),
    put: (path, data, config) => api.put(`${base()}${path}`, data, config),
    delete: (path, config) => api.delete(`${base()}${path}`, config),
    hasFamily: () => !!familyStore.currentFamilyId,
    // A plain <a href> would authenticate fine now (cookies ride along on
    // same-origin navigation), but it can't read the Content-Disposition
    // header for the real filename — fetch as a blob and save manually.
    download: async (path, fallbackFilename) => {
      const res = await api.get(`${base()}${path}`, { responseType: 'blob' })
      downloadBlob(res, fallbackFilename)
    },
  }
}

function downloadBlob(res, fallbackFilename) {
  const disposition = res.headers?.['content-disposition'] || ''
  const match = disposition.match(/filename="?([^";]+)"?/)
  const filename = match ? match[1] : fallbackFilename

  const url = URL.createObjectURL(new Blob([res.data]))
  const link = document.createElement('a')
  link.href = url
  link.download = filename
  document.body.appendChild(link)
  link.click()
  link.remove()
  URL.revokeObjectURL(url)
}

// Extract items from Laravel API/paginated responses
export function items(res) {
  const d = res.data
  if (Array.isArray(d)) return d
  if (Array.isArray(d?.data)) return d.data
  if (Array.isArray(d?.data?.data)) return d.data.data
  return []
}

// Extract a single record
export function record(res) {
  return res.data?.data ?? res.data
}

// Extract Laravel validation errors into { field: message }
export function extractErrors(err) {
  const out = {}
  const errors = err.response?.data?.errors
  if (errors) {
    for (const [k, v] of Object.entries(errors)) {
      out[k] = Array.isArray(v) ? v[0] : v
    }
  }
  out._message = err.response?.data?.message || 'Something went wrong.'
  return out
}
