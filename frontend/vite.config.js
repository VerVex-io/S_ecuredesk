import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'

// In Docker the API is reachable as http://backend; locally use http://localhost:8080
const target = process.env.API_TARGET || 'http://localhost:8080'

export default defineConfig({
  plugins: [react()],
  server: { proxy: { '/api': { target, changeOrigin: true } } },
})
