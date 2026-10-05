import { randomBytes, scrypt as scryptCb, timingSafeEqual } from 'node:crypto'
import { promisify } from 'node:util'

const scrypt = promisify(scryptCb)

/** "scrypt$<salt>$<hash>", both base64url. */
export async function hashPassword(password: string): Promise<string> {
  const salt = randomBytes(16)
  const hash = (await scrypt(password, salt, 64)) as Buffer
  return `scrypt$${salt.toString('base64url')}$${hash.toString('base64url')}`
}

export async function verifyPassword(password: string, stored: string): Promise<boolean> {
  const [scheme, salt, hash] = stored.split('$')
  if (scheme !== 'scrypt' || !salt || !hash) return false
  const expected = Buffer.from(hash, 'base64url')
  const actual = (await scrypt(password, Buffer.from(salt, 'base64url'), expected.length)) as Buffer
  return actual.length === expected.length && timingSafeEqual(actual, expected)
}
