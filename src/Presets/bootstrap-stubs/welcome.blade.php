<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Laravel') }}</title>
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg bg-white border-bottom">
        <div class="container py-2">
            <a class="navbar-brand fw-semibold d-flex align-items-center gap-2" href="{{ url('/') }}">
                <span class="d-inline-flex align-items-center justify-content-center rounded bg-danger text-white fw-bold" style="width: 2rem; height: 2rem">L</span>
                {{ config('app.name', 'Laravel') }}
            </a>
            <div class="d-flex align-items-center gap-2">
                @if (Route::has('login'))
                    @auth
                        <a href="{{ url('/home') }}" class="btn btn-outline-secondary btn-sm">Dashboard</a>
                    @else
                        <a href="{{ route('login') }}" class="btn btn-link link-secondary text-decoration-none">Log in</a>

                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="btn btn-danger btn-sm">Register</a>
                        @endif
                    @endauth
                @endif
            </div>
        </div>
    </nav>

    <main class="container py-5">
        <div class="row justify-content-center py-lg-5">
            <div class="col-lg-10 col-xl-9">
                <div class="text-center mb-5">
                    <span class="badge rounded-pill bg-danger bg-opacity-10 text-danger mb-3">Laravel + Bootstrap 5</span>
                    <h1 class="display-5 fw-bold text-body-emphasis">Your application is ready.</h1>
                    <p class="lead text-secondary mx-auto" style="max-width: 42rem">
                        Laravel provides a strong foundation for your application. Start building with the tools and resources below.
                    </p>
                </div>

                <div class="row g-4">
                    <div class="col-md-6">
                        <a class="card h-100 text-decoration-none shadow-sm" href="https://laravel.com/docs">
                            <div class="card-body p-4">
                                <div class="d-flex align-items-center justify-content-between mb-3">
                                    <h2 class="h5 card-title text-body mb-0">Documentation</h2>
                                    <span class="text-danger fs-4" aria-hidden="true">↗</span>
                                </div>
                                <p class="card-text text-secondary mb-0">Explore Laravel's documentation and learn about the features available to you.</p>
                            </div>
                        </a>
                    </div>
                    <div class="col-md-6">
                        <a class="card h-100 text-decoration-none shadow-sm" href="https://laracasts.com">
                            <div class="card-body p-4">
                                <div class="d-flex align-items-center justify-content-between mb-3">
                                    <h2 class="h5 card-title text-body mb-0">Video tutorials</h2>
                                    <span class="text-danger fs-4" aria-hidden="true">↗</span>
                                </div>
                                <p class="card-text text-secondary mb-0">Follow practical lessons and build your Laravel skills with the community.</p>
                            </div>
                        </a>
                    </div>
                    <div class="col-md-6">
                        <a class="card h-100 text-decoration-none shadow-sm" href="https://laravel-news.com">
                            <div class="card-body p-4">
                                <div class="d-flex align-items-center justify-content-between mb-3">
                                    <h2 class="h5 card-title text-body mb-0">Laravel News</h2>
                                    <span class="text-danger fs-4" aria-hidden="true">↗</span>
                                </div>
                                <p class="card-text text-secondary mb-0">Keep up with releases, tutorials, and news from the Laravel ecosystem.</p>
                            </div>
                        </a>
                    </div>
                    <div class="col-md-6">
                        <a class="card h-100 text-decoration-none shadow-sm" href="https://laravel.com/docs/deployment">
                            <div class="card-body p-4">
                                <div class="d-flex align-items-center justify-content-between mb-3">
                                    <h2 class="h5 card-title text-body mb-0">Deployment</h2>
                                    <span class="text-danger fs-4" aria-hidden="true">↗</span>
                                </div>
                                <p class="card-text text-secondary mb-0">When you are ready, learn how to deploy your application to production.</p>
                            </div>
                        </a>
                    </div>
                </div>

                <p class="text-center text-secondary small mt-5 mb-0">Built with Laravel and Bootstrap 5.</p>
            </div>
        </div>
    </main>
</body>
</html>
