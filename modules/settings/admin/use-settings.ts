'use client'

import { useCallback, useEffect, useState } from 'react'

import { api } from '@/modules/admin/lib/api'

export type Settings = Record<string, string>

// useSettings loads the site settings once and saves a subset of keys.
export function useSettings() {
  const [settings, setSettings] = useState<Settings | null>(null)
  const [error, setError] = useState<string | null>(null)

  useEffect(() => {
    api<{ settings: Settings }>('/admin/settings')
      .then(({ settings }) => setSettings(settings))
      .catch(() => setError('Could not load settings.'))
  }, [])

  const save = useCallback(async (values: Settings) => {
    const { settings } = await api<{ settings: Settings }>('/admin/settings', {
      method: 'PUT',
      body: JSON.stringify({ settings: values }),
    })
    setSettings(settings)
  }, [])

  return { settings, error, save }
}
