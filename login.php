<?php
require_once 'includes/sessao.php';
require_once 'includes/conexao.php';

if (isset($_SESSION['utilizador_id'])) {
    header("Location: /majo_system/pages/dashboard.php");
    exit();
}

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';

    if (empty($email) || empty($senha)) {
        $erro = 'Por favor preenche o email e a senha.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = 'Formato de email inválido.';
    } else {
        usleep(300000);

        $stmt = $conn->prepare("SELECT id, nome, senha, perfil FROM utilizadores WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res->num_rows === 1) {
            $u = $res->fetch_assoc();
            if (password_verify($senha, $u['senha'])) {
                session_regenerate_id(true);
                $_SESSION['utilizador_id'] = $u['id'];
                $_SESSION['nome']          = $u['nome'];
                $_SESSION['perfil']        = $u['perfil'];
                $_SESSION['login_time']    = time();
                header("Location: /majo_system/pages/dashboard.php");
                exit();
            }
        }
        $erro = 'Email ou senha incorretos. Tenta novamente.';
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MAJO, LDA — Entrar</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }

        body {
            font-family: 'Segoe UI', sans-serif;
            min-height: 100vh;
            display: flex;
            background: #0f1e2e;
        }

        .login-esquerda {
            flex: 1;
            background: linear-gradient(145deg, #0f2744 0%, #1a3c5e 40%, #1e4d7b 70%, #2563a8 100%);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 48px;
            position: relative;
            overflow: hidden;
        }
        .login-esquerda::before {
            content: '';
            position: absolute;
            width: 600px; height: 600px;
            border-radius: 50%;
            border: 1px solid rgba(245,158,11,0.12);
            top: -200px; left: -200px;
        }
        .login-esquerda::after {
            content: '';
            position: absolute;
            width: 400px; height: 400px;
            border-radius: 50%;
            border: 1px solid rgba(255,255,255,0.06);
            bottom: -100px; right: -100px;
        }
        .circulo-deco {
            position: absolute;
            width: 300px; height: 300px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(37,99,168,0.3), transparent);
            bottom: 10%; left: 5%;
        }

        .logo-grande {
            position: relative; z-index: 1; text-align: center;
        }
        .logo-svg-wrap {
            width: 100px; height: 100px; margin: 0 auto 24px;
            filter: drop-shadow(0 8px 20px rgba(0,0,0,0.4));
        }
        .logo-svg-wrap svg { width: 100%; height: 100%; }
        .nome-empresa {
            font-size: 42px; font-weight: 900;
            color: white; letter-spacing: 6px;
            text-shadow: 0 2px 10px rgba(0,0,0,0.3);
        }
        .linha-dourada {
            width: 80px; height: 4px;
            background: linear-gradient(90deg, #f59e0b, #fbbf24);
            border-radius: 2px; margin: 16px auto;
        }
        .slogan {
            font-size: 14px; color: rgba(255,255,255,0.65);
            letter-spacing: 1.5px; text-transform: uppercase;
        }

        .login-direita {
            width: 460px; flex-shrink: 0;
            background: #ffffff;
            display: flex; align-items: center; justify-content: center;
            padding: 48px 44px;
        }
        .form-wrap { width: 100%; max-width: 360px; }

        .form-titulo {
            font-size: 26px; font-weight: 800;
            color: #1a3c5e; margin-bottom: 6px;
        }
        .form-sub {
            font-size: 13px; color: #64748b; margin-bottom: 36px;
        }

        .campo { margin-bottom: 20px; }
        .campo label {
            display: block; font-size: 11px; font-weight: 700;
            color: #475569; text-transform: uppercase;
            letter-spacing: 0.8px; margin-bottom: 8px;
        }
        .campo-input {
            position: relative;
            display: flex;
            align-items: center;
        }
        .campo-input i.ico-campo {
            position: absolute; left: 14px; top: 50%;
            transform: translateY(-50%);
            color: #94a3b8; font-size: 15px; pointer-events: none;
            z-index: 1;
        }
        .campo-input input {
            width: 100%; padding: 13px 44px 13px 42px;
            border: 2px solid #e2e8f0; border-radius: 12px;
            font-size: 14px; color: #1e293b;
            background: #f8fafc; transition: all 0.2s;
        }
        .campo-input input:focus {
            outline: none; border-color: #2563a8;
            background: white;
            box-shadow: 0 0 0 4px rgba(37,99,168,0.1);
        }
        .btn-olho {
            position: absolute; right: 12px; top: 50%;
            transform: translateY(-50%);
            background: none; border: none;
            cursor: pointer; color: #94a3b8;
            font-size: 16px; padding: 4px;
            transition: color 0.2s;
        }
        .btn-olho:hover { color: #1a3c5e; }

        .btn-login {
            width: 100%; padding: 15px;
            background: linear-gradient(135deg, #1a3c5e, #2563a8);
            color: white; border: none; border-radius: 12px;
            font-size: 15px; font-weight: 700; cursor: pointer;
            display: flex; align-items: center; justify-content: center; gap: 10px;
            transition: all 0.2s; margin-top: 10px;
            letter-spacing: 0.3px;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(26,60,94,0.38);
        }
        .btn-login:active { transform: translateY(0); }

        .erro-box {
            background: #fee2e2; color: #991b1b;
            border-left: 4px solid #ef4444;
            padding: 13px 16px; border-radius: 10px;
            font-size: 13px; display: flex; align-items: center; gap: 10px;
            margin-bottom: 22px;
        }

        .rodape-login {
            text-align: center; margin-top: 32px;
            font-size: 11px; color: #94a3b8;
            border-top: 1px solid #f1f5f9; padding-top: 20px;
        }

        .slogan-wrap {
            position: relative; z-index: 1;
            margin-top: 56px; text-align: center;
            max-width: 360px;
        }
        .slogan-quote {
            font-size: 80px; line-height: 0.5;
            color: rgba(245,158,11,0.4);
            font-family: Georgia, serif;
            margin-bottom: 16px;
        }
        .slogan-texto {
            font-size: 20px; font-weight: 700;
            color: rgba(255,255,255,0.9);
            line-height: 1.5; font-style: italic;
            text-shadow: 0 2px 8px rgba(0,0,0,0.2);
        }
        .slogan-sub {
            margin-top: 14px; font-size: 13px;
            color: rgba(255,255,255,0.5);
            letter-spacing: 1px;
        }

        @media (max-width: 768px) {
            .login-esquerda { display: none; }
            .login-direita { width: 100%; }
        }
    </style>
</head>
<body>

<!-- LADO ESQUERDO -->
<div class="login-esquerda">
    <div class="circulo-deco"></div>

    <div class="logo-grande">
        <div class="logo-svg-wrap">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 120 120">
                <defs>
                    <linearGradient id="g1" x1="0%" y1="0%" x2="100%" y2="100%">
                        <stop offset="0%" style="stop-color:#1a3c5e"/>
                        <stop offset="100%" style="stop-color:#2563a8"/>
                    </linearGradient>
                    <linearGradient id="g2" x1="0%" y1="0%" x2="100%" y2="100%">
                        <stop offset="0%" style="stop-color:#f59e0b"/>
                        <stop offset="100%" style="stop-color:#fbbf24"/>
                    </linearGradient>
                    <filter id="sh"><feDropShadow dx="0" dy="4" stdDeviation="6" flood-color="rgba(0,0,0,0.35)"/></filter>
                </defs>
                <rect x="4" y="4" width="112" height="112" rx="26" fill="url(#g1)" filter="url(#sh)"/>
                <rect x="4" y="4" width="18" height="112" rx="0" fill="url(#g2)" opacity="0.25"/>
                <rect x="4" y="4" width="112" height="18" rx="0" fill="url(#g2)" opacity="0.12"/>
                <text x="62" y="83" font-family="Georgia, 'Times New Roman', serif"
                      font-weight="900" font-size="72" fill="white" text-anchor="middle">M</text>
                <rect x="25" y="90" width="70" height="5" rx="2.5" fill="url(#g2)"/>
            </svg>
        </div>
        <div class="nome-empresa">MAJO</div>
        <div class="linha-dourada"></div>
        <div class="slogan">Gestão de Produtos</div>
    </div>

    <div class="slogan-wrap">
        <div class="slogan-quote">"</div>
        <div class="slogan-texto">Organizar é o primeiro passo para crescer.</div>
        <div class="slogan-sub">Gerir bem hoje, liderar amanhã.</div>
    </div>
</div>

<!-- LADO DIREITO -->
<div class="login-direita">
    <div class="form-wrap">
        <div class="form-titulo">Bem-vindo de volta!</div>
        <div class="form-sub">Introduz as tuas credenciais para entrar no sistema.</div>

        <?php if ($erro): ?>
        <div class="erro-box">
            <i class="fas fa-exclamation-circle"></i>
            <?= htmlspecialchars($erro) ?>
        </div>
        <?php endif; ?>

        <form method="POST" autocomplete="off">

            <!-- Campo Email -->
            <div class="campo">
                <label>Endereço de Email</label>
                <div class="campo-input">
                    <i class="fas fa-envelope ico-campo"></i>
                    <input type="email" name="email"
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                           required autofocus>
                </div>
            </div>

            <!-- Campo Senha com olhinho -->
            <div class="campo">
                <label>Senha</label>
                <div class="campo-input">
                    <i class="fas fa-lock ico-campo"></i>
                    <input type="password" name="senha" id="campo-senha" required>
                    <button type="button" class="btn-olho"
                            onclick="toggleSenha()"
                            title="Mostrar / ocultar senha">
                        <i class="fas fa-eye" id="icon-olho"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn-login">
                <i class="fas fa-sign-in-alt"></i>
                Entrar no Sistema
            </button>

        </form>

        <div class="rodape-login">
            MAJO, LDA &copy; <?= date('Y') ?> &mdash; Todos os direitos reservados
        </div>
    </div>
</div>

<script>
// Mostrar / ocultar senha — função única e limpa
function toggleSenha() {
    const input = document.getElementById('campo-senha');
    const icon  = document.getElementById('icon-olho');
    if (input.type === 'password') {
        input.type     = 'text';
        icon.className = 'fas fa-eye-slash';
    } else {
        input.type     = 'password';
        icon.className = 'fas fa-eye';
    }
}
</script>

</body>
</html>