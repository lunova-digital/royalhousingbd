import type { CapacitorConfig } from '@capacitor/cli'

// FR-APP-001: the apps are a thin WebView over the live website — one
// codebase, one admin panel. Change `server.url` to the production domain
// before building store releases.
const config: CapacitorConfig = {
  appId: 'com.royalhousingbd.app',
  appName: 'Royal Housing BD',
  webDir: 'www',
  server: {
    url: 'https://royalhousingbd.com',
    cleartext: false,
  },
  android: {
    allowMixedContent: false,
  },
  plugins: {
    SplashScreen: {
      launchShowDuration: 1500,
      launchAutoHide: true,
      backgroundColor: '#ffffff',
    },
  },
}

export default config
