REM SPDX-FileCopyrightText: 2010-2026 the Friendica project
REM
REM SPDX-License-Identifier: CC0-1.0

@echo OFF
:: in case DelayedExpansion is on and a path contains !
setlocal DISABLEDELAYEDEXPANSION
php "%~dp0console.php" %*
