@echo off
:: Wechselt automatisch in den Ordner, in dem die Batch liegt
cd /d "%~dp0"

set /p msg=Commit-Text eingeben: 

git add .
git commit -m "%msg%"
git push origin master

pause
