/** URL slug like Laravel's Str::slug: lowercase ASCII words joined by "-". */
export function slugify(input: string): string {
  return input
    .normalize('NFKD')
    .replace(/[̀-ͯ]/g, '')
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-+|-+$/g, '')
}

export function limit(text: string, max: number, end = '...'): string {
  return text.length <= max ? text : text.slice(0, max).trimEnd() + end
}

export function ucfirst(text: string): string {
  return text.charAt(0).toUpperCase() + text.slice(1)
}

/** Split on blank lines, like the About page story. */
export function paragraphs(text: string): string[] {
  return text
    .trim()
    .split(/\n\s*\n/)
    .map((p) => p.trim())
    .filter(Boolean)
}
