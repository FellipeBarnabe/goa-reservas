<?php
// Autenticação do painel administrativo: sessão, login, logout e proteção CSRF

const TEMPO_SESSAO = 1800; // 30 minutos sem atividade encerram o login

// Inicia a sessão com cookie mais seguro. Pode ser chamada várias vezes.
function iniciarSessao(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    session_set_cookie_params([
        'lifetime' => 0,          // some quando o navegador fecha
        'path'     => '/',
        'secure'   => $https,     // só por HTTPS, quando o site usar HTTPS
        'httponly' => true,       // JavaScript não lê o cookie
        'samesite' => 'Lax',      // não vai em POSTs vindos de outros sites
    ]);
    ini_set('session.use_strict_mode', '1'); // rejeita IDs de sessão inventados
    session_start();
}

// Devolve os dados do admin logado ou null (também derruba sessão parada há muito tempo)
function usuarioLogado(): ?array
{
    iniciarSessao();
    if (empty($_SESSION['usuario'])) {
        return null;
    }
    if (time() - ($_SESSION['ultimo_acesso'] ?? 0) > TEMPO_SESSAO) {
        encerrarSessao();

        return null;
    }
    $_SESSION['ultimo_acesso'] = time();
    return $_SESSION['usuario'];
}

// Use no topo de toda página do painel
function exigirLogin(): array
{
    $usuario = usuarioLogado();
    if (!$usuario) {
        redirecionar('admin/login.php');
    }
    return $usuario;
}

// Confere e-mail e senha. Devolve true e abre a sessão, ou false.
function fazerLogin(PDO $pdo, string $email, string $senha): bool
{
    $stmt = $pdo->prepare('SELECT id, nome, email, senha_hash FROM usuarios WHERE email = :email');
    $stmt->execute(['email' => $email]);
    $u = $stmt->fetch();

    // Se o e-mail não existe, conferimos contra um hash falso mesmo assim:
    // o tempo de resposta fica parecido e não revela quais e-mails existem.
    $hash = $u['senha_hash'] ?? '$2y$10$usesomesillystringforsalt.abcdefghijklmnopqrstuvwxyzABC';
    $ok   = password_verify($senha, $hash);

    if (!$u || !$ok) {
        return false;
    }


    iniciarSessao();
    session_regenerate_id(true); // novo ID após o login (evita fixação de sessão)
    $_SESSION['usuario'] = [
        'id'    => (int) $u['id'],
        'nome'  => $u['nome'],
        'email' => $u['email'],
    ];
    $_SESSION['ultimo_acesso'] = time();
    return true;
}

// Apaga a sessão no servidor e o cookie no navegador
function encerrarSessao(): void
{
    iniciarSessao();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires'  => time() - 3600,
            'path'     => $p['path'],
            'secure'   => $p['secure'],
            'httponly' => $p['httponly'],
            'samesite' => $p['samesite'] ?? 'Lax',
        ]);
    }
    session_destroy();
}

// ----- CSRF: impede que outro site faça o admin enviar um formulário sem querer -----

function csrfToken(): string

{
    iniciarSessao();
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

// <input hidden> para colocar dentro de todo <form method="post"> do painel
function csrfCampo(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrfToken()) . '">';
}

// Confere o token recebido no POST. Se falhar, para tudo com erro 403.
function exigirCsrf(): void
{
    $enviado = $_POST['csrf'] ?? '';
    if (!is_string($enviado) || !hash_equals(csrfToken(), $enviado)) {
        http_response_code(403);
        exit('Requisição inválida. Volte e tente novamente.');
    }
}