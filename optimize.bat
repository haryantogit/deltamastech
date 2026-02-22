@echo off
echo Running Laravel Production Optimizations...
php artisan optimize
php artisan view:cache
php artisan config:cache
php artisan route:cache
php artisan icons:cache
php artisan filament:cache-components
echo DONE.
pause
