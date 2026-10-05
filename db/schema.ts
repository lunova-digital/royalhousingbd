// Every table the application has, gathered from the modules so one Drizzle
// client knows them all (relations need the whole graph). Add a module's
// schema here when it gets one; drizzle.config.ts globs the same files.
export { users, sessions, usersRelations, sessionsRelations } from '@/modules/auth/schema'
export { settings } from '@/modules/settings/schema'
export { listings, listingImages, listingsRelations, listingImagesRelations } from '@/modules/listing/schema'
export { masterplans, plots, masterplansRelations, plotsRelations } from '@/modules/masterplan/schema'
export { tickerItems, videos } from '@/modules/content/schema'
