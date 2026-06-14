<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход — Чистый город</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 24px;
            font-family: Inter, Arial, Helvetica, sans-serif;
            background:
                radial-gradient(circle at 15% 10%, rgba(74, 222, 128, .22), transparent 32%),
                linear-gradient(135deg, #0b4f33 0%, #0f6d44 44%, #f4f7f5 44%);
            color: #17251d;
        }
        .login-shell {
            width: min(980px, 100%);
            display: grid;
            grid-template-columns: 1fr 420px;
            border-radius: 32px;
            overflow: hidden;
            background: #fff;
            box-shadow: 0 30px 90px rgba(8, 50, 31, .28);
        }
        .login-info {
            padding: 52px;
            color: #fff;
            background: linear-gradient(180deg, #0f7a4b, #0b4f33);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .badge {
            display: inline-flex;
            width: fit-content;
            padding: 8px 13px;
            border-radius: 999px;
            background: rgba(255,255,255,.14);
            color: rgba(255,255,255,.88);
            font-size: 13px;
            font-weight: 800;
            margin-bottom: 24px;
        }
        h1 { margin: 0; font-size: 42px; letter-spacing: -.04em; }
        .login-info p { max-width: 440px; line-height: 1.65; color: rgba(255,255,255,.82); }
        .login-form { padding: 48px 42px; }
        h2 { margin: 0 0 8px; font-size: 30px; letter-spacing: -.03em; }
        .subtitle { color: #68766e; margin-bottom: 28px; line-height: 1.5; }
        label {
            display: block;
            margin: 16px 0 7px;
            color: #405047;
            font-weight: 800;
            font-size: 13px;
        }
        input {
            width: 100%;
            height: 48px;
            border-radius: 14px;
            border: 1px solid #d7e2dc;
            padding: 0 14px;
            font: inherit;
            color: #17251d;
            background: #fff;
        }
        input:focus { outline: none; border-color: #0f7a4b; box-shadow: 0 0 0 4px rgba(15,122,75,.13); }
        button {
            width: 100%;
            height: 50px;
            margin-top: 24px;
            border: 0;
            border-radius: 14px;
            background: #0b5e3a;
            color: #fff;
            font: inherit;
            font-weight: 800;
            cursor: pointer;
        }
        .error {
            padding: 12px 14px;
            border-radius: 14px;
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
            margin-bottom: 18px;
            font-weight: 700;
        }
        .hint {
            margin-top: 20px;
            padding: 14px;
            border-radius: 14px;
            background: #f0fdf4;
            color: #166534;
            border: 1px solid #bbf7d0;
            font-size: 13px;
            line-height: 1.55;
        }
        @media (max-width: 860px) {
            .login-shell { grid-template-columns: 1fr; }
            .login-info { display: none; }
        }
    </style>
</head>
<body>
<div class="login-shell">
    <section class="login-info">
        <div>
            <div class="badge">Административная панель</div>
            <h1>Чистый город</h1>
            <p>
                Сервис для приёма, обработки и контроля обращений жителей о загрязнениях городской среды.
                Фото, координаты, исполнители и история статусов собраны в одной системе.
            </p>
        </div>
        <p>Доступ к панели имеют только сотрудники администрации и ответственные операторы.</p>
    </section>

    <section class="login-form">
        <h2>Вход</h2>
        <div class="subtitle">Введите данные администратора.</div>

        @if($errors->any())
            <div class="error">
                @foreach($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('admin.login.submit') }}">
            @csrf
            <label>Email</label>
            <input type="email" name="email" value="{{ old('email') }}" required autofocus>

            <label>Пароль</label>
            <input type="password" name="password" required>

            <button type="submit">Войти</button>
        </form>

       
    </section>
</div>
</body>
</html>
