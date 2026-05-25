@echo off
cd /d "%~dp0"
start "" http://127.0.0.1:8000
"C:\xampp\php\php.exe" -S 127.0.0.1:8000 -t "%~dp0"
