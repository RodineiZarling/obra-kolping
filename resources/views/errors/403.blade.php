<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Acesso negado — 403</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script>
        function voltar() {
            if (window.history.length > 1) {
                window.history.back();
            } else {
                window.location.href = '/';
            }
        }
    </script>
    <style>
        :root { color-scheme: light dark; }
        html, body { height: 100%; }
        body {
            margin: 0;
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, "Apple Color Emoji", "Segoe UI Emoji";
            display: grid;
            place-items: center;
            background: #0b1020;
            color: #e5e7eb;
        }
        .card {
            width: 100%;
            max-width: 34rem;
            background: #111827;
            border: 1px solid #1f2937;
            border-radius: 0.75rem;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.35);
        }
        .code {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 3rem;
            height: 3rem;
            border-radius: 9999px;
            background: #1f2937;
            color: #f59e0b;
            font-weight: 700;
        }
        h1 { font-size: 1.5rem; margin: 1rem 0 0.5rem; }
        p { margin: 0 0 1.5rem; color: #9ca3af; }
        .actions { display: flex; gap: .75rem; flex-wrap: wrap; }
        .btn {
            appearance: none;
            border: 1px solid #374151;
            background: #0ea5e9;
            color: #0b1020;
            font-weight: 600;
            padding: .625rem 1rem;
            border-radius: .5rem;
            cursor: pointer;
            text-decoration: none;
        }
        .btn.secondary { background: transparent; color: #e5e7eb; }
        .btn:hover { filter: brightness(1.05); }
        .hint { font-size: .875rem; color: #6b7280; margin-top: 1rem; }
    </style>
</head>
<body>
<div class="card" role="alert" aria-live="assertive">
    <div class="code" aria-hidden="true">403</div>
    <h1>Acesso negado</h1>
    <p>Você não tem permissão para acessar este recurso.</p>

    <div class="actions">
        <button class="btn" onclick="voltar()">Voltar</button>
        <a class="btn secondary" href="/">Ir para a página inicial</a>
        <form method="POST" action="{{ filament()->getLogoutUrl() }}" style="display:inline;">
            @csrf
            <button type="submit" class="btn secondary">Sair e fazer login com outra conta</button>
        </form>
    </div>

    <div class="hint">Se você acredita que isso é um engano, entre em contato com o administrador do sistema.</div>
</div>
</body>
</html>
