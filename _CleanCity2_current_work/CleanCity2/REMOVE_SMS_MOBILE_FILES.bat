@echo off
REM Запускать из корня Android-проекта CleanCity2
if exist "app\src\main\java\com\example\cleancity\models\VerifyPhoneRequest.java" del /Q "app\src\main\java\com\example\cleancity\models\VerifyPhoneRequest.java"
echo SMS mobile files removed if they existed.
