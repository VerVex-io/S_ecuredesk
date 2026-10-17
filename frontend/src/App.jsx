import { useEffect, useState } from 'react'
import { api } from './api.js'

function AuthForm({ onDone }) {
  const [mode, setMode] = useState('login')
  const [form, setForm] = useState({ name: '', email: '', password: '' })
  const [error, setError] = useState('')
  const set = (k) => (e) => setForm({ ...form, [k]: e.target.value })

  async function submit(e) {
    e.preventDefault()
    setError('')
    try {
      if (mode === 'register') await api.register(form.name, form.email, form.password)
      onDone(await api.login(form.email, form.password))
    } catch (err) { setError(err.message) }
  }

  return (
    <form className="card auth" onSubmit={submit}>
      <h1>SecureDesk</h1>
      {mode === 'register' && <input placeholder="Name" value={form.name} onChange={set('name')} required />}
      <input type="email" placeholder="Email" value={form.email} onChange={set('email')} required />
      <input type="password" placeholder="Password" value={form.password} onChange={set('password')} required />
      {error && <p className="error" role="alert">{error}</p>}
      <button>{mode === 'login' ? 'Sign in' : 'Create account'}</button>
      <button type="button" className="link" onClick={() => setMode(mode === 'login' ? 'register' : 'login')}>
        {mode === 'login' ? 'Need an account? Register' : 'Have an account? Sign in'}
      </button>
    </form>
  )
}

function TicketForm({ onCreated }) {
  const empty = { title: '', description: '', priority: 'medium' }
  const [t, setT] = useState(empty)
  const [error, setError] = useState('')

  async function submit(e) {
    e.preventDefault()
    setError('')
    try { await api.createTicket(t); setT(empty); onCreated() }
    catch (err) { setError(err.message) }
  }

  return (
    <form className="card" onSubmit={submit}>
      <h2>New ticket</h2>
      <input placeholder="Title" value={t.title} onChange={(e) => setT({ ...t, title: e.target.value })} required />
      <textarea placeholder="Describe the problem" value={t.description} onChange={(e) => setT({ ...t, description: e.target.value })} required />
      <select value={t.priority} onChange={(e) => setT({ ...t, priority: e.target.value })}>
        <option value="low">Low</option><option value="medium">Medium</option><option value="high">High</option>
      </select>
      {error && <p className="error" role="alert">{error}</p>}
      <button>Create ticket</button>
    </form>
  )
}

function TicketList({ user }) {
  const [tickets, setTickets] = useState([])
  const [q, setQ] = useState('')
  const staff = user.role !== 'user'

  const load = () => api.tickets(q ? `?q=${encodeURIComponent(q)}` : '').then(setTickets).catch(() => setTickets([]))
  useEffect(() => { load() }, [q])

  async function changeStatus(id, status) { await api.setStatus(id, status); load() }

  return (
    <>
      <TicketForm onCreated={load} />
      <div className="card">
        <h2>{staff ? 'All tickets' : 'Your tickets'}</h2>
        <input placeholder="Search by title" value={q} onChange={(e) => setQ(e.target.value)} />
        {tickets.length === 0 && <p className="muted">No tickets yet. Create the first one above.</p>}
        <ul className="tickets">
          {tickets.map((t) => (
            <li key={t.id}>
              <div>
                {/* React escapes text by default: no XSS via {t.title} */}
                <strong>{t.title}</strong>
                <span className="muted"> by {t.author}, {t.priority} priority</span>
              </div>
              {staff ? (
                <select value={t.status} onChange={(e) => changeStatus(t.id, e.target.value)}>
                  <option value="open">Open</option><option value="in_progress">In progress</option><option value="closed">Closed</option>
                </select>
              ) : <span className={`badge ${t.status}`}>{t.status.replace('_', ' ')}</span>}
            </li>
          ))}
        </ul>
      </div>
    </>
  )
}

export default function App() {
  const [user, setUser] = useState(null)
  const [ready, setReady] = useState(false)

  useEffect(() => { api.me().then(setUser).catch(() => {}).finally(() => setReady(true)) }, [])
  if (!ready) return null
  if (!user) return <AuthForm onDone={setUser} />

  return (
    <main>
      <header>
        <h1>SecureDesk</h1>
        <span>{user.name} ({user.role}) <button className="link" onClick={() => api.logout().then(() => setUser(null))}>Sign out</button></span>
      </header>
      <TicketList user={user} />
    </main>
  )
}
