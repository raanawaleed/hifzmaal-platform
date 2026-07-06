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
  }
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
