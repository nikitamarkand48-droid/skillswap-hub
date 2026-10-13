@echo off
title SkillSwap - Local Development Server
color 0B

echo ===================================================
echo     SkillSwap - Campus Skill Exchange Platform
echo ===================================================
echo.
echo Starting local PHP development server on:
echo   http://localhost:8000
echo.
echo Press Ctrl + C anytime in this window to stop the server.
echo ===================================================
echo.

where php >nul 2>nul
if %ERRORLEVEL% EQU 0 (
    echo Using PHP from system PATH...
    php -S localhost:8000
) else if exist "C:\xampp\php\php.exe" (
    echo Using PHP from C:\xampp\php\php.exe...
    "C:\xampp\php\php.exe" -S localhost:8000
) else (
    echo [ERROR] Could not find php.exe.
    echo Please make sure XAMPP is installed at C:\xampp or PHP is added to your PATH.
    pause
)
