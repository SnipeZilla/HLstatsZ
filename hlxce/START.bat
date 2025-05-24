SETLOCAL ENABLEEXTENSIONS
CD "%~dp0"
:LBL1
START /WAIT "HLSTATS" "%~dp0Strawberry\perl\bin\perl.exe" "%~dp0hlstats.pl"
GOTO LBL1

