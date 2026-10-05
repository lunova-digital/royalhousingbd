import { NextResponse } from 'next/server'
import type { ZodType } from 'zod'

/** A field-keyed validation failure, serialised like Laravel's 422 payload: { message, errors: { field: [msg] } }. */
export class ValidationError extends Error {
  constructor(public errors: Record<string, string[]>) {
    super(Object.values(errors)[0]?.[0] ?? 'The given data was invalid.')
  }

  static of(field: string, message: string): ValidationError {
    return new ValidationError({ [field]: [message] })
  }
}

export class HttpError extends Error {
  constructor(
    public status: number,
    message: string,
  ) {
    super(message)
  }
}

export function json(data: unknown, init: number | ResponseInit = 200): NextResponse {
  return NextResponse.json(data, typeof init === 'number' ? { status: init } : init)
}

export function notFound(message = 'Not found.'): never {
  throw new HttpError(404, message)
}

/** Validate a body against a zod schema; path segments are joined with dots ("plots.0.polygon"). */
export function validate<T>(schema: ZodType<T>, input: unknown): T {
  const result = schema.safeParse(input)
  if (result.success) return result.data
  const errors: Record<string, string[]> = {}
  for (const issue of result.error.issues) {
    const key = issue.path.map(String).join('.') || '_'
    ;(errors[key] ??= []).push(issue.message)
  }
  throw new ValidationError(errors)
}

export async function readJson(request: Request): Promise<unknown> {
  try {
    return await request.json()
  } catch {
    throw new ValidationError({ _: ['The request body must be JSON.'] })
  }
}

/** The Next.js route handler signature: the request plus the dynamic segment params (a promise since Next 15). */
export type RouteParams = Record<string, string | string[]>
export type RouteContext<P extends RouteParams = Record<string, string>> = { params: Promise<P> }

type Handler<P extends RouteParams> = (request: Request, params: P) => Promise<Response> | Response

/**
 * Wraps a handler so thrown ValidationError / HttpError become the JSON responses the admin expects,
 * and anything else a 500 (logged, never leaked).
 */
export function handle<P extends RouteParams = Record<string, string>>(fn: Handler<P>) {
  return async (request: Request, context: RouteContext<P>): Promise<Response> => {
    try {
      return await fn(request, await context.params)
    } catch (error) {
      if (error instanceof ValidationError) {
        return json({ message: error.message, errors: error.errors }, 422)
      }
      if (error instanceof HttpError) {
        return json({ message: error.message }, error.status)
      }
      console.error(error)
      return json({ message: 'Server error.' }, 500)
    }
  }
}

export function intParam(value: string, name = 'id'): number {
  const n = Number(value)
  if (!Number.isInteger(n) || n < 1) throw new HttpError(404, `No ${name} ${value}.`)
  return n
}
