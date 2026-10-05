/**
 * Build a wa.me click-to-chat link. Spaces are %20 (not +) because WhatsApp
 * shows a literal plus otherwise.
 */
export function whatsappLink(number: string | null | undefined, text = ''): string {
  const digits = (number ?? '').replace(/\D+/g, '')
  let link = `https://wa.me/${digits}`
  if (text !== '') link += `?text=${encodeURIComponent(text)}`
  return link
}
