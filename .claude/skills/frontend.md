# Slim4MVC Frontend Rules

## Template Engine
- Use **BladeOne** (via `eftec/bladeone`) with Blade syntax: `@extends`, `@section`, `@yield`, `@foreach`, `@if`, `@csrf`, `@method`.

## CSS Framework
- Use **Tailwind CSS** compiled locally. NEVER use CDN links.
- Install via npm: `npm install -D tailwindcss postcss autoprefixer` and build into `public/css/app.css`.
- NEVER use Bootstrap. Replace any Bootstrap classes with Tailwind equivalents.
- Responsive design is mandatory: use Tailwind responsive prefixes (`sm:`, `md:`, `lg:`, `xl:`).

## Layouts
- Use `resources/views/layouts/app.blade.php` for public pages.
- Use `resources/views/layouts/admin.blade.php` for admin/dashboard pages.
- Admin layout must include a collapsible sidebar, top nav, and main content area.

## Blade View Rules
- **ZERO business logic in Blade views.** No raw PHP blocks (`<?php ... ?>`), no model queries, no calculations.
- Only these Blade directives are allowed in views:
  - `@extends`, `@section`, `@yield`, `@endsection`
  - `@foreach`, `@forelse`, `@empty`, `@endforeach`
  - `@if`, `@elseif`, `@else`, `@endif`
  - `@csrf`, `@method`
  - `@include`, `@stack`, `@push`
- Pass only pre-computed data from controllers:
  ```php
  return view('products.index', $response, [
      'products' => $products,
      'categories' => $categories,
  ]);
  ```
- Use Tailwind utility classes for all styling:
  - Cards: `bg-white rounded-xl shadow-sm border border-gray-100`
  - Tables: `w-full text-left border-collapse`
  - Buttons: `inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition`
  - Forms: `block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500`

## Design Principles
- **Minimalist**: Clean whitespace, no unnecessary borders or shadows.
- **Responsive**: Mobile-first approach; tables become cards on small screens.
- **Accessible**: Use proper form labels, focus rings, and ARIA attributes where needed.
- **Icons**: Use Heroicons or Lucide (SVG), never FontAwesome or Bootstrap Icons.

## Asset Management
- **ALL libraries MUST be installed locally via npm/yarn.** NEVER use CDN links for CSS or JS.
- Compile Tailwind and any custom CSS into `public/css/app.css`.
- Compile/bundle JS into `public/js/app.js`.
- Use a build tool (Vite, Laravel Mix, or plain PostCSS CLI) to process assets.
- Blade layouts must reference only local assets: `<link rel="stylesheet" href="{{ asset('css/app.css') }}">`

## JavaScript
- Keep JavaScript minimal and inline only when necessary.
- Prefer vanilla JS over heavy frameworks for simple interactions (dropdowns, modals, confirmation dialogs).
- If using icon libraries (Heroicons/Lucide), install via npm and import SVGs directly.
