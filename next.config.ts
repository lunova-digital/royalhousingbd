import type { NextConfig } from 'next'

const nextConfig: NextConfig = {
  // better-sqlite3 and sharp are native modules: load them from node_modules at runtime instead of bundling.
  serverExternalPackages: ['better-sqlite3', 'sharp'],
  async redirects() {
    return [{ source: '/listings', destination: '/projects', permanent: false }]
  },
}

export default nextConfig
