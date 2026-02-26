@echo off
echo Starting CA Dashboard Environment...

:: Start Laravel Server in a new window
start "Laravel Server" cmd /k "php artisan serve"

:: Start Vite Asset Server in a new window
start "Vite Dev Server" cmd /k "npm run dev"

:: Wait for servers to initialize
timeout /t 3 >nul

:: Open in Default Browser
start http://127.0.0.1:8000

echo.
echo ========================================================
echo   App started! 
echo   - Backend: http://127.0.0.1:8000
echo   - Frontend: Running in background
echo ========================================================
echo.
