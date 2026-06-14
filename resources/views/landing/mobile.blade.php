<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Чистый город — мобильное приложение</title>
    <meta name="description" content="Мобильное приложение для обращений о загрязнениях, работы служб ЖКХ, карты заявок и маршрутов исполнителей.">
    <style>
        :root {
            --green: #0b6535;
            --green-2: #0f7a43;
            --green-3: #e9f5ee;
            --dark: #09100d;
            --text: #15211c;
            --muted: #65736d;
            --line: rgba(14, 38, 26, .12);
            --card: rgba(255, 255, 255, .86);
            --shadow: 0 30px 80px rgba(5, 30, 17, .18);
        }
        * { box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body {
            margin: 0;
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            color: var(--text);
            background:
                radial-gradient(circle at 18% 8%, rgba(42, 177, 93, .22), transparent 28rem),
                radial-gradient(circle at 82% 8%, rgba(11, 101, 53, .18), transparent 32rem),
                linear-gradient(180deg, #f5faf7 0%, #eef5f1 44%, #ffffff 100%);
            overflow-x: hidden;
        }
        a { color: inherit; text-decoration: none; }
        .shell { width: min(1180px, calc(100% - 40px)); margin: 0 auto; }
        .nav {
            position: sticky;
            top: 0;
            z-index: 30;
            backdrop-filter: blur(18px);
            background: rgba(246, 251, 248, .76);
            border-bottom: 1px solid rgba(9, 16, 13, .08);
        }
        .nav-inner { height: 74px; display: flex; align-items: center; justify-content: space-between; gap: 24px; }
        .brand { display: inline-flex; align-items: center; gap: 12px; font-weight: 900; letter-spacing: -.04em; font-size: 20px; }
        .brand-mark {
            width: 42px; height: 42px; border-radius: 14px;
            display: grid; place-items: center;
            background: linear-gradient(145deg, #0b7a3d, #064725);
            color: #fff; box-shadow: 0 14px 28px rgba(11, 101, 53, .26);
            font-weight: 900;
        }
        .nav-links { display: flex; align-items: center; gap: 24px; color: #405047; font-weight: 700; font-size: 14px; }
        .button {
            border: 0;
            cursor: pointer;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 48px;
            padding: 0 24px;
            font-weight: 900;
            letter-spacing: -.02em;
            transition: transform .2s ease, box-shadow .2s ease, opacity .2s ease;
        }
        .button:hover { transform: translateY(-2px); }
        .button-primary { background: var(--green); color: white; box-shadow: 0 18px 42px rgba(11, 101, 53, .28); }
        .button-soft { background: #fff; color: var(--green); border: 1px solid rgba(11, 101, 53, .14); }
        .hero { padding: 84px 0 70px; position: relative; }
        .hero-grid { display: grid; grid-template-columns: 1.05fr .95fr; gap: 54px; align-items: center; }
        .eyebrow {
            display: inline-flex; align-items: center; gap: 9px;
            padding: 8px 12px;
            border: 1px solid rgba(11, 101, 53, .14);
            background: rgba(255,255,255,.7);
            border-radius: 999px;
            color: var(--green);
            font-weight: 900;
            font-size: 13px;
            margin-bottom: 22px;
        }
        .dot { width: 8px; height: 8px; border-radius: 50%; background: #19a75a; box-shadow: 0 0 0 6px rgba(25,167,90,.12); }
        h1 {
            margin: 0;
            font-size: clamp(54px, 8vw, 104px);
            line-height: .91;
            letter-spacing: -.085em;
            color: var(--dark);
        }
        .hero-text { margin: 26px 0 0; max-width: 650px; color: #53625c; font-size: clamp(18px, 2vw, 23px); line-height: 1.55; }
        .hero-actions { display: flex; gap: 14px; flex-wrap: wrap; margin-top: 34px; }
        .stats-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-top: 46px; max-width: 650px; }
        .mini-stat {
            padding: 18px 20px; border-radius: 24px; background: var(--card); border: 1px solid var(--line); box-shadow: 0 18px 48px rgba(5, 30, 17, .08);
        }
        .mini-stat strong { display: block; font-size: 30px; letter-spacing: -.06em; color: #07110d; }
        .mini-stat span { color: var(--muted); font-weight: 700; font-size: 14px; }
        .phone-stage { position: relative; min-height: 660px; display: grid; place-items: center; }
        .glow {
            position: absolute; inset: 7% 0 2%; border-radius: 56px;
            background: radial-gradient(circle at 50% 35%, rgba(20, 156, 78, .38), transparent 24rem), linear-gradient(145deg, rgba(16,116,61,.12), rgba(3, 33, 18, .08));
            filter: blur(0px);
            transform: rotate(-4deg);
        }
        .phone {
            width: min(295px, 44vw);
            aspect-ratio: 9 / 19.5;
            background: #111;
            border-radius: 42px;
            padding: 10px;
            box-shadow: var(--shadow), 0 0 0 1px rgba(255,255,255,.18) inset;
            position: absolute;
        }
        .phone img { width: 100%; height: 100%; object-fit: cover; border-radius: 34px; display: block; }
        .phone-main { right: 10%; top: 2%; transform: rotate(4deg); z-index: 2; }
        .phone-second { left: 2%; bottom: 0; transform: rotate(-7deg) scale(.92); opacity: .98; z-index: 1; }
        .floating-card {
            position: absolute; right: 0; bottom: 16%; z-index: 5;
            width: min(270px, 46vw);
            padding: 20px; border-radius: 28px;
            background: rgba(255,255,255,.88); border: 1px solid rgba(255,255,255,.82); backdrop-filter: blur(14px); box-shadow: var(--shadow);
        }
        .floating-card strong { font-size: 30px; letter-spacing: -.06em; display: block; }
        .floating-card span { color: var(--muted); font-weight: 700; }
        .section { padding: 72px 0; }
        .section-head { display: flex; align-items: end; justify-content: space-between; gap: 24px; margin-bottom: 28px; }
        .section h2 { margin: 0; font-size: clamp(34px, 5vw, 64px); letter-spacing: -.075em; line-height: 1; color: var(--dark); }
        .section-lead { max-width: 520px; color: var(--muted); font-size: 18px; line-height: 1.55; margin: 0; font-weight: 650; }
        .feature-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 18px; }
        .feature-card {
            min-height: 235px;
            padding: 28px;
            border-radius: 34px;
            background: rgba(255,255,255,.82);
            border: 1px solid var(--line);
            box-shadow: 0 24px 70px rgba(5, 30, 17, .08);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .feature-number { color: var(--green); font-weight: 950; letter-spacing: -.05em; font-size: 17px; }
        .feature-card h3 { margin: 30px 0 10px; font-size: 24px; line-height: 1.05; letter-spacing: -.05em; }
        .feature-card p { margin: 0; color: var(--muted); line-height: 1.5; font-weight: 650; }
        .wide-panel {
            border-radius: 46px;
            background: linear-gradient(135deg, #06120d 0%, #0b6535 100%);
            color: white;
            overflow: hidden;
            position: relative;
            box-shadow: var(--shadow);
        }
        .wide-panel::before {
            content: ""; position: absolute; width: 480px; height: 480px; border-radius: 50%; right: -120px; top: -160px;
            background: rgba(255,255,255,.12);
        }
        .wide-grid { position: relative; display: grid; grid-template-columns: 1fr 1fr; gap: 30px; align-items: center; padding: 58px; }
        .wide-panel h2 { color: white; }
        .wide-panel p { color: rgba(255,255,255,.76); font-size: 19px; line-height: 1.6; font-weight: 650; }
        .flow { display: grid; gap: 14px; }
        .flow-item { display: flex; gap: 14px; align-items: center; padding: 17px; border-radius: 22px; background: rgba(255,255,255,.10); border: 1px solid rgba(255,255,255,.12); }
        .flow-badge { flex: none; width: 38px; height: 38px; border-radius: 14px; display: grid; place-items: center; background: rgba(255,255,255,.16); font-weight: 950; }
        .screens { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 20px; align-items: stretch; }
        .screen-card { border-radius: 42px; background: #fff; border: 1px solid var(--line); padding: 24px; box-shadow: 0 24px 70px rgba(5, 30, 17, .08); display: grid; grid-template-columns: 230px 1fr; gap: 26px; align-items: center; overflow: hidden; }
        .screen-phone { background: #111; border-radius: 34px; padding: 8px; box-shadow: 0 20px 50px rgba(0,0,0,.20); }
        .screen-phone img { display: block; width: 100%; border-radius: 27px; }
        .screen-card h3 { margin: 0 0 10px; font-size: 30px; line-height: 1.06; letter-spacing: -.06em; }
        .screen-card p { margin: 0; color: var(--muted); line-height: 1.55; font-weight: 650; }
        .download { padding: 92px 0 96px; }
        .download-card { text-align: center; border-radius: 52px; padding: 78px 34px; background: linear-gradient(180deg, #fff, #eef7f2); border: 1px solid var(--line); box-shadow: var(--shadow); }
        .download-card h2 { margin: 0; font-size: clamp(42px, 7vw, 84px); letter-spacing: -.085em; line-height: .96; color: var(--dark); }
        .download-card p { margin: 20px auto 30px; max-width: 640px; color: var(--muted); font-size: 20px; line-height: 1.55; font-weight: 650; }
        footer { padding: 34px 0; color: #66736c; font-weight: 700; border-top: 1px solid var(--line); }
        .footer-inner { display: flex; justify-content: space-between; gap: 20px; flex-wrap: wrap; }
        @media (max-width: 980px) {
            .hero-grid, .wide-grid { grid-template-columns: 1fr; }
            .phone-stage { min-height: 720px; }
            .feature-grid { grid-template-columns: repeat(2, 1fr); }
            .screens { grid-template-columns: 1fr; }
            .nav-links { display: none; }
        }
        @media (max-width: 620px) {
            .shell { width: min(100% - 24px, 1180px); }
            .nav-inner { height: 64px; }
            .hero { padding: 52px 0 42px; }
            .hero-actions, .stats-row { display: grid; grid-template-columns: 1fr; }
            .phone-stage { min-height: 560px; }
            .phone { width: 228px; border-radius: 34px; padding: 8px; }
            .phone img { border-radius: 27px; }
            .phone-main { right: 0; }
            .phone-second { left: 0; bottom: 20px; transform: rotate(-7deg) scale(.82); }
            .floating-card { right: 4px; bottom: 80px; width: 210px; }
            .feature-grid { grid-template-columns: 1fr; }
            .wide-grid { padding: 34px 24px; }
            .section-head { display: block; }
            .section-lead { margin-top: 16px; }
            .screen-card { grid-template-columns: 1fr; }
            .screen-phone { max-width: 245px; margin: 0 auto; }
        }
    </style>
</head>
<body>
    <nav class="nav">
        <div class="shell nav-inner">
            <a class="brand" href="/">
                <span class="brand-mark">ЧГ</span>
                <span>Чистый город</span>
            </a>
            <div class="nav-links">
                <a href="#features">Возможности</a>
                <a href="#screens">Приложение</a>
                <a href="#download">Скачать</a>
            </div>
            <a class="button button-soft" href="/admin/login">Админка</a>
        </div>
    </nav>

    <main>
        <section class="hero">
            <div class="shell hero-grid">
                <div>
                    <div class="eyebrow"><span class="dot"></span> Мобильное приложение для города</div>
                    <h1>Чистый город<br>в одном касании</h1>
                    <p class="hero-text">Жители сообщают о проблемах с фото и точкой на карте, службы ЖКХ получают задачи, маршруты и подтверждают выполненную работу.</p>
                    <div class="hero-actions">
                        <a class="button button-primary" href="{{ asset('downloads/CleanCity.apk') }}" download="CleanCity.apk">Скачать</a>
                        <a class="button button-soft" href="#screens">Посмотреть интерфейс</a>
                    </div>
                    <div class="stats-row">
                        <div class="mini-stat"><strong>2</strong><span>режима приложения</span></div>
                        <div class="mini-stat"><strong>GPS</strong><span>точка обращения</span></div>
                        <div class="mini-stat"><strong>Фото</strong><span>до и после</span></div>
                    </div>
                </div>
                <div class="phone-stage" aria-hidden="true">
                    <div class="glow"></div>
                    <div class="phone phone-second"><img src="{{ asset('landing/login.jpg') }}" alt="Экран входа приложения"></div>
                    <div class="phone phone-main"><img src="{{ asset('landing/tasks.jpg') }}" alt="Экран задач исполнителя"></div>
                    <div class="floating-card"><strong>Маршрут</strong><span>задачи ЖКХ открываются сразу в навигаторе</span></div>
                </div>
            </div>
        </section>

        <section class="section" id="features">
            <div class="shell">
                <div class="section-head">
                    <h2>Всё, что нужно для обращения</h2>
                    <p class="section-lead">Минимум действий для жителя и понятная работа для исполнителя: заявка, карта, статус, маршрут и фото результата.</p>
                </div>
                <div class="feature-grid">
                    <article class="feature-card">
                        <span class="feature-number">01</span>
                        <div><h3>Заявка с фото</h3><p>Категория, комментарий, снимок и координаты фиксируются в одном сценарии.</p></div>
                    </article>
                    <article class="feature-card">
                        <span class="feature-number">02</span>
                        <div><h3>Карта обращений</h3><p>Точки проблем видны на карте, а маршрут до места открывается в навигаторе.</p></div>
                    </article>
                    <article class="feature-card">
                        <span class="feature-number">03</span>
                        <div><h3>Работа ЖКХ</h3><p>Исполнитель принимает задачу, начинает работу и закрывает её с фото.</p></div>
                    </article>
                    <article class="feature-card">
                        <span class="feature-number">04</span>
                        <div><h3>Контроль статуса</h3><p>История обращения остаётся в системе: создано, принято, в работе, выполнено.</p></div>
                    </article>
                </div>
            </div>
        </section>

        <section class="section">
            <div class="shell wide-panel">
                <div class="wide-grid">
                    <div>
                        <h2>Для жителей и служб ЖКХ</h2>
                        <p>Приложение связывает человека, который заметил проблему, и службу, которая должна её устранить. Без лишних звонков, бумажек и потерянных обращений.</p>
                    </div>
                    <div class="flow">
                        <div class="flow-item"><span class="flow-badge">1</span><strong>Житель создаёт обращение</strong></div>
                        <div class="flow-item"><span class="flow-badge">2</span><strong>Админ ЖКХ назначает исполнителя</strong></div>
                        <div class="flow-item"><span class="flow-badge">3</span><strong>Сотрудник закрывает задачу с фото</strong></div>
                    </div>
                </div>
            </div>
        </section>

        <section class="section" id="screens">
            <div class="shell">
                <div class="section-head">
                    <h2>Интерфейс приложения</h2>
                    <p class="section-lead">Зелёный стиль, крупные карточки, нижнее меню и отдельные сценарии для жителей и работников.</p>
                </div>
                <div class="screens">
                    <article class="screen-card">
                        <div class="screen-phone"><img src="{{ asset('landing/login.jpg') }}" alt="Вход в приложение"></div>
                        <div><h3>Вход в систему</h3><p>Чистый экран авторизации без перегруза. Пользователь входит в свой режим по роли.</p></div>
                    </article>
                    <article class="screen-card">
                        <div class="screen-phone"><img src="{{ asset('landing/tasks.jpg') }}" alt="Задачи исполнителя"></div>
                        <div><h3>Задачи исполнителя</h3><p>Новые, принятые, в работе и завершённые заявки. Маршрут и закрытие с фото доступны прямо из карточки.</p></div>
                    </article>
                </div>
            </div>
        </section>

        <section class="download" id="download">
            <div class="shell download-card">
                <h2>Скачать приложение</h2>
                <p>Приложение для Android</p>
                <a class="button button-primary" href="{{ asset('downloads/CleanCity.apk') }}" download="CleanCity.apk">Скачать</a>
            </div>
        </section>
    </main>

    <footer>
        <div class="shell footer-inner">
            <span>© {{ date('Y') }} Чистый город</span>
            <span>Мобильная система обращений и обработки заявок</span>
        </div>
    </footer>

</body>
</html>
