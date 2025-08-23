xxxxxxxxxxxxxxxxxxx
@echo off
setlocal enabledelayedexpansion

:: erster Parameter = Ordner der Datei, die PSPad gerade übergibt
set startDir=%~1

if "%startDir%"=="" (
    echo Fehler: Kein Verzeichnis übergeben.
    pause
    exit /b 1
)

:: Suche nach .git Ordner (geht von Dateiordner nach oben)
set searchDir=%startDir%
:searchloop
if exist "!searchDir!\.git" (
    cd /d "!searchDir!"
    goto found
)
cd ..
set searchDir=%cd%
if "%searchDir%"=="%SystemDrive%\" goto notfound
goto searchloop

:found
echo Repository gefunden: %cd%
set /p msg=Commit-Text eingeben: 
if "%msg%"=="" set msg=Update von %date% %time%

git add .
git commit -m "%msg%"
git push
goto end

:notfound
echo Kein Git-Repository gefunden!
pause
exit /b 1

:end
pause
