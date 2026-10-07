# Royal Housing BD — mobile apps (WebView)

Android and iOS shells that render the responsive website (FR-APP-001/002).
There is no app-specific code: the website is the single source of truth.

## One-time setup

```sh
cd mobile
npm install
# Put the client's brand assets here first:
#   resources/icon.png     1024×1024, no transparency
#   resources/splash.png   2732×2732, logo centred
npm run assets            # generates icons + splash screens for both platforms
npm run add:android       # creates android/  (needs Android Studio)
npm run add:ios           # creates ios/      (needs Xcode, macOS)
```

## Building

1. Set `server.url` in `capacitor.config.ts` to the production domain (HTTPS).
2. `npm run sync`
3. `npm run open:android` → Build → Generate signed bundle (AAB) for Play Store.
4. `npm run open:ios` → Product → Archive for App Store Connect.

Store developer accounts are client-paid (§12.6). The WhatsApp links open the
WhatsApp app through the normal `https://wa.me/...` handoff.
