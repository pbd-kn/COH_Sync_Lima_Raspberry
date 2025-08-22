@echo off
cd /d C:\wampneu\www\co5\co5Bundles\COH_Sync_Lima_Raspberry

set /p msg=Commit-Text eingeben: 

git add .
git commit -m "%msg%"
git push origin master

pause
