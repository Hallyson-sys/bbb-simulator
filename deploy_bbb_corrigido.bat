@echo off
setlocal EnableExtensions EnableDelayedExpansion
title BBB Simulator - Deploy GitHub + Render

REM =========================================================
REM CONFIGURACAO DO PROJETO
REM =========================================================
set "PROJECT_DIR=C:\xampp\htdocs\bbb-simulator"

echo ==========================================
echo        BBB SIMULATOR - DEPLOY
echo ==========================================
echo.

REM Vai direto para a pasta correta do projeto
if not exist "%PROJECT_DIR%" (
    echo [ERRO] A pasta do projeto nao foi encontrada:
    echo %PROJECT_DIR%
    echo.
    pause
    exit /b 1
)

cd /d "%PROJECT_DIR%"

echo Pasta atual:
cd
echo.

REM Verifica se o Git esta instalado
git --version >nul 2>&1
if errorlevel 1 (
    echo [ERRO] Git nao foi encontrado no computador.
    echo Instale o Git e tente novamente.
    echo.
    pause
    exit /b 1
)

REM =========================================================
REM PRIMEIRA CONFIGURACAO DO GIT
REM =========================================================

git rev-parse --is-inside-work-tree >nul 2>&1
if errorlevel 1 (
    echo Repositorio Git ainda nao inicializado.
    echo Inicializando agora...
    git init
    if errorlevel 1 (
        echo [ERRO] Nao foi possivel executar git init.
        pause
        exit /b 1
    )

    git branch -M main
    echo.
)

REM Verifica se existe remote origin
git remote get-url origin >nul 2>&1
if errorlevel 1 (
    echo O projeto ainda nao esta ligado ao GitHub.
    echo.
    set /p "REPO_URL=Cole aqui a URL HTTPS do seu repositorio GitHub: "

    if "!REPO_URL!"=="" (
        echo.
        echo [ERRO] Nenhuma URL foi informada.
        pause
        exit /b 1
    )

    git remote add origin "!REPO_URL!"
    if errorlevel 1 (
        echo.
        echo [ERRO] Nao foi possivel adicionar o repositorio remoto.
        pause
        exit /b 1
    )
)

REM Garante branch main
git branch -M main

REM =========================================================
REM IDENTIDADE DO GIT PARA COMMITS
REM =========================================================

for /f "delims=" %%N in ('git config user.name 2^>nul') do set "GIT_NAME=%%N"
if not defined GIT_NAME (
    set /p "GIT_NAME=Digite seu nome para os commits: "
    if not "!GIT_NAME!"=="" git config user.name "!GIT_NAME!"
)

for /f "delims=" %%E in ('git config user.email 2^>nul') do set "GIT_EMAIL=%%E"
if not defined GIT_EMAIL (
    set /p "GIT_EMAIL=Digite o email usado no GitHub: "
    if not "!GIT_EMAIL!"=="" git config user.email "!GIT_EMAIL!"
)

echo.
echo Repositorio conectado:
git remote get-url origin
echo.
echo Branch:
git branch --show-current
echo.

REM =========================================================
REM DEPLOY
REM =========================================================

echo ------------------------------------------
echo Arquivos alterados:
echo ------------------------------------------
git status --short
echo.

REM Adiciona tudo
git add .
if errorlevel 1 (
    echo [ERRO] Falha no git add.
    pause
    exit /b 1
)

REM Verifica se existe algo para commitar
git diff --cached --quiet
if not errorlevel 1 (
    echo Nenhuma alteracao nova para enviar.
    echo.
    pause
    exit /b 0
)

set "COMMIT_MSG="
set /p "COMMIT_MSG=Mensagem da atualizacao: "

if "!COMMIT_MSG!"=="" (
    set "COMMIT_MSG=Atualizacao do BBB Simulator"
)

echo.
echo Criando commit...
git commit -m "!COMMIT_MSG!"
if errorlevel 1 (
    echo.
    echo [ERRO] Nao foi possivel criar o commit.
    pause
    exit /b 1
)

echo.
echo Enviando para o GitHub...
git push -u origin main

if errorlevel 1 (
    echo.
    echo ==========================================
    echo O PUSH NAO FOI CONCLUIDO.
    echo ==========================================
    echo.
    echo Isso pode acontecer se o repositorio do GitHub
    echo ja tiver arquivos que nao existem nesta pasta.
    echo.
    echo NAO use force push.
    echo Tire um print desta tela e me envie para eu
    echo te passar o comando seguro para sincronizar.
    echo.
    pause
    exit /b 1
)

echo.
echo ==========================================
echo      DEPLOY ENVIADO COM SUCESSO!
echo ==========================================
echo.
echo GitHub atualizado.
echo.
echo Se o Render estiver conectado a branch main
echo e com Auto-Deploy habilitado, ele iniciara
echo o deploy automaticamente.
echo.
pause
endlocal
