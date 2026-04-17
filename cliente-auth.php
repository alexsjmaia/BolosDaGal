<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

const CLIENTE_SESSION_TIMEOUT_SECONDS = 300;

function clienteEstaLogado(): bool
{
    return isset($_SESSION['cliente_id']) && (int) $_SESSION['cliente_id'] > 0;
}

function exigirClienteLogado(): void
{
    if (!clienteEstaLogado()) {
        $_SESSION['cliente_login_erro'] = 'Faca login para acessar o cardapio.';
        header('Location: cliente-login.php');
        exit;
    }

    $agora = time();
    $ultimoAcesso = (int) ($_SESSION['cliente_ultimo_acesso'] ?? 0);

    if ($ultimoAcesso > 0 && ($agora - $ultimoAcesso) > CLIENTE_SESSION_TIMEOUT_SECONDS) {
        limparSessaoCliente();
        $_SESSION['cliente_login_erro'] = 'Sessao encerrada por inatividade. Faca login novamente.';
        header('Location: cliente-login.php');
        exit;
    }

    $_SESSION['cliente_ultimo_acesso'] = $agora;
}

function limparSessaoCliente(): void
{
    unset(
        $_SESSION['cliente_id'],
        $_SESSION['cliente_nome'],
        $_SESSION['cliente_whatsapp'],
        $_SESSION['cliente_saldo_cashback'],
        $_SESSION['cliente_ultimo_acesso']
    );
}

function renderClienteIdleLogoutScript(): void
{
    $tempoMs = CLIENTE_SESSION_TIMEOUT_SECONDS * 1000;
    echo <<<HTML
<script>
    (function () {
        const idleLimitMs = {$tempoMs};
        let idleTimer;

        function resetIdleTimer() {
            clearTimeout(idleTimer);
            idleTimer = setTimeout(function () {
                window.location.href = 'cliente-logout.php?motivo=expirado';
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
