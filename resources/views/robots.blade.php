# robots.txt – Agilify
# https://www.robotstxt.org/robotstxt.html

User-agent: *
Allow: /

# Block API routes
Disallow: /api/
# Block auth utility pages
Disallow: /email/verify/
Disallow: /password/reset/
Disallow: /otp/copy/
# Block framework internals
Disallow: /vendor/
Disallow: /_ignition/
Disallow: /telescope/

Sitemap: {{ config('app.url') }}/sitemap.xml
