<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="robots" content="noindex" />
    <title>Sign in | {{ config('app.name') }}</title>
    @vite('resources/css/app.css')
  </head>
  {{-- Styled with the admin's shadcn tokens, not the public theme: this is the door to /admin. --}}
  <body class="flex min-h-svh items-center justify-center bg-background p-4 text-foreground antialiased">
    <main class="w-full max-w-sm rounded-xl border bg-card p-6 text-card-foreground shadow-sm">
      <h1 class="font-semibold leading-none">Admin sign in</h1>
      <p class="mt-1.5 text-sm text-muted-foreground">{{ config('app.name') }} control panel.</p>

      <form method="post" action="{{ route('login') }}" class="mt-6 grid gap-4">
        @csrf
        <div class="grid gap-2">
          <label for="email" class="text-sm font-medium leading-none">Email</label>
          <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="username" required autofocus class="h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-3 focus-visible:ring-ring/50" />
        </div>
        <div class="grid gap-2">
          <label for="password" class="text-sm font-medium leading-none">Password</label>
          <input id="password" name="password" type="password" autocomplete="current-password" required class="h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-3 focus-visible:ring-ring/50" />
        </div>
        @error('email')
          <p class="text-sm text-destructive">{{ $message }}</p>
        @enderror
        @error('password')
          <p class="text-sm text-destructive">{{ $message }}</p>
        @enderror
        <button type="submit" class="inline-flex h-9 items-center justify-center rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground hover:bg-primary/90">Sign in</button>
        <p class="text-center text-sm text-muted-foreground"><a href="/" class="underline underline-offset-4">Back to website</a></p>
      </form>
    </main>
  </body>
</html>
