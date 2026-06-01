@echo off
echo Starting CA Dashboard Environment...

:: Start Laravel Server in a new window
start "Laravel Server" cmd /k ".\.tools\php\php.exe -S 127.0.0.1:8000 -t public"

:: Start Vite Asset Server in a new window
start "Vite Dev Server" cmd /k "npm run dev"

:: Start Laravel Schedule Worker for Automated Reminders
start "Laravel Scheduler" cmd /k "php artisan schedule:work"

:: Wait for servers to initialize
ping 127.0.0.1 -n 4 > nul

:: Open in Default Browser
start http://127.0.0.1:8000

echo.
echo ========================================================
echo   App started! 
echo   - Backend: http://127.0.0.1:8000
echo   - Frontend: Running in background
echo ========================================================
echo.
