<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

const SESSION_TIMEOUT_SECONDS = 300;
$ignorarTimeoutSessao = defined('DISABLE_SESSION_TIMEOUT') && DISABLE_SESSION_TIMEOUT === true;

$agora = time();

if (!isset($_SESSION['usuario'])) {
    header('Location: index.php');
    exit;
}

if (
    !$ignorarTimeoutSessao &&
    isset($_SESSION['ultimo_acesso']) &&
    ($agora - (int) $_SESSION['ultimo_acesso']) > SESSION_TIMEOUT_SECONDS
) {
    session_unset();
    session_destroy();
    header('Location: logout.php?motivo=expirado');
    exit;
}

$_SESSION['ultimo_acesso'] = $agora;

function currentUserIsRoot(): bool
{
    return isset($_SESSION['usuario']) && $_SESSION['usuario'] === 'root';
}

function currentUserCanUploadPhotos(): bool
{
    $usuario = strtolower(trim((string) ($_SESSION['usuario'] ?? '')));
    return in_array($usuario, ['bolos', 'root'], true);
}

function renderIdleLogoutScript(): void
{
    if (defined('DISABLE_SESSION_TIMEOUT') && DISABLE_SESSION_TIMEOUT === true) {
        return;
    }

    $tempoMs = SESSION_TIMEOUT_SECONDS * 1000;
    echo <<<HTML
<script>
    (function () {
        const idleLimitMs = {$tempoMs};
        let idleTimer;

        function resetIdleTimer() {
            clearTimeout(idleTimer);
            idleTimer = setTimeout(function () {
                window.location.href = 'logout.php?motivo=expirado';
            }, idleLimitMs);
        }

        ['click', 'keydown', 'mousemove', 'scroll', 'touchstart'].forEach(function (eventName) {
            document.addEventListener(eventName, resetIdleTimer, { passive: true });
        });

        resetIdleTimer();
    })();
</script>
HTML;
}
