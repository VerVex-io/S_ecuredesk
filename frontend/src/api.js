async function request(path, options = {}) {
  const res = await fetch(`/api${path}`, {
    credentials: 'include',
    headers: { 'Content-Type': 'application/json' },
    ...options,
    body: options.body ? JSON.stringify(options.body) : undefined,
  })
  const data = await res.json().catch(() => ({}))
  if (!res.ok) throw new Error(data.error || 'Request failed')
  return data
}

export const api = {
  me: () => request('/me'),
  login: (email, password) => request('/login', { method: 'POST', body: { email, password } }),
  register: (name, email, password) => request('/register', { method: 'POST', body: { name, email, password } }),
  logout: () => request('/logout', { method: 'POST', body: {} }),
  tickets: (params = '') => request(`/tickets${params}`),
  createTicket: (body) => request('/tickets', { method: 'POST', body }),
  ticket: (id) => request(`/tickets/${id}`),
  setStatus: (id, status) => request(`/tickets/${id}`, { method: 'PATCH', body: { status } }),
  comment: (id, body) => request(`/tickets/${id}/comments`, { method: 'POST', body: { body } }),
}
