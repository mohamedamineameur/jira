<!doctype html>
<html lang="en" prefix="og: https://ogp.me/ns#">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    {{-- ─── Primary SEO ──────────────────────────────────────────────── --}}
    <title>Agilify – Agile Project Management for Modern Teams</title>
    <meta name="description" content="Agilify is a powerful, intuitive project management platform. Plan sprints, track issues, manage Kanban boards and collaborate in real time – all in one place.">
    <meta name="keywords" content="project management, agile, scrum, kanban, sprint planning, issue tracking, jira alternative, team collaboration, task management, agilify">
    <meta name="author" content="Agilify">
    <meta name="robots" content="index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1">
    <meta name="theme-color" content="#1f1c2c">
    <link rel="canonical" href="{{ config('app.url') }}">

    {{-- ─── Open Graph (Facebook, LinkedIn, Discord…) ───────────────── --}}
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="Agilify">
    <meta property="og:title" content="Agilify – Agile Project Management for Modern Teams">
    <meta property="og:description" content="Plan sprints, track issues, manage Kanban boards and collaborate in real time. The smart Jira alternative built for speed and simplicity.">
    <meta property="og:url" content="{{ config('app.url') }}">
    <meta property="og:image" content="{{ config('app.url') }}/og-image.png">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="Agilify – Agile Project Management Dashboard">
    <meta property="og:locale" content="en_US">

    {{-- ─── Twitter / X Cards ────────────────────────────────────────── --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:site" content="@agilify">
    <meta name="twitter:creator" content="@agilify">
    <meta name="twitter:title" content="Agilify – Agile Project Management for Modern Teams">
    <meta name="twitter:description" content="Plan sprints, track issues, manage Kanban boards and collaborate in real time. The smart Jira alternative built for speed and simplicity.">
    <meta name="twitter:image" content="{{ config('app.url') }}/og-image.png">
    <meta name="twitter:image:alt" content="Agilify Dashboard Preview">

    {{-- ─── PWA / Mobile ─────────────────────────────────────────────── --}}
    <meta name="application-name" content="Agilify">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Agilify">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="msapplication-TileColor" content="#1f1c2c">
    <meta name="msapplication-config" content="/browserconfig.xml">

    {{-- ─── Icons & Manifest ─────────────────────────────────────────── --}}
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">
    <link rel="manifest" href="/site.webmanifest">

    {{-- ─── Fonts ─────────────────────────────────────────────────────── --}}
    <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>
    <link href="https://fonts.bunny.net/css?family=poppins:400,500,600,700&display=swap" rel="stylesheet">

    {{-- ─── JSON-LD Structured Data ──────────────────────────────────── --}}
    <script type="application/ld+json">
    {
        "@@context": "https://schema.org",
        "@@graph": [
            {
                "@@type": "Organization",
                "@@id": "{{ config('app.url') }}/#organization",
                "name": "Agilify",
                "url": "{{ config('app.url') }}",
                "logo": {
                    "@@type": "ImageObject",
                    "url": "{{ config('app.url') }}/favicon.svg",
                    "width": 128,
                    "height": 128
                },
                "sameAs": []
            },
            {
                "@@type": "WebSite",
                "@@id": "{{ config('app.url') }}/#website",
                "url": "{{ config('app.url') }}",
                "name": "Agilify",
                "description": "Agile project management for modern teams",
                "publisher": { "@@id": "{{ config('app.url') }}/#organization" },
                "inLanguage": "en-US"
            },
            {
                "@@type": "SoftwareApplication",
                "@@id": "{{ config('app.url') }}/#app",
                "name": "Agilify",
                "url": "{{ config('app.url') }}",
                "applicationCategory": "BusinessApplication",
                "applicationSubCategory": "Project Management",
                "operatingSystem": "Web, iOS, Android",
                "description": "Agilify is a powerful, intuitive project management platform. Plan sprints, track issues, manage Kanban boards and collaborate in real time.",
                "inLanguage": "en-US",
                "offers": {
                    "@@type": "Offer",
                    "price": "0",
                    "priceCurrency": "USD",
                    "availability": "https://schema.org/InStock"
                },
                "featureList": [
                    "Sprint Planning",
                    "Kanban Boards",
                    "Issue Tracking",
                    "Backlog Management",
                    "Team Collaboration",
                    "Real-time Updates",
                    "Role-based Access Control",
                    "Activity Audit Logs",
                    "Email Notifications",
                    "Organization Management"
                ],
                "screenshot": "{{ config('app.url') }}/og-image.png",
                "publisher": { "@@id": "{{ config('app.url') }}/#organization" }
            },
            {
                "@@type": "WebApplication",
                "@@id": "{{ config('app.url') }}/#webapp",
                "name": "Agilify",
                "url": "{{ config('app.url') }}",
                "browserRequirements": "Requires JavaScript. Requires HTML5.",
                "applicationCategory": "BusinessApplication",
                "operatingSystem": "All",
                "offers": {
                    "@@type": "Offer",
                    "price": "0",
                    "priceCurrency": "USD"
                }
            }
        ]
    }
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
<div id="app"></div>
</body>
</html>
