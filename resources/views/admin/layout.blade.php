<!DOCTYPE html>
<html lang="ru" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Панель') — Чистый город</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,400..900;1,14..32,400..700&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
    <script>
        (function(){
            const saved = localStorage.getItem('cleanCityAdminTheme');
            if (saved) document.documentElement.setAttribute('data-theme', saved);
        })();
    </script>
    @stack('head')
    <style>
        /* ===== Design tokens ===== */
        :root {
            --bg: #F3F6F1;
            --bg-deep: #ECF1E9;
            --surface: #FFFFFF;
            --surface-soft: #F7F9F5;
            --surface-strong: #EDF2EA;
            --text: #0F1614;
            --text-soft: #2A332E;
            --muted: #687468;
            --muted-soft: #8C988D;
            --line: #E3E8DD;
            --line-soft: #ECF0E6;
            --line-strong: #D2DACB;
            --primary: #0E7A42;
            --primary-deep: #0A5A30;
            --primary-soft: #DDEFE2;
            --primary-soft-2: #EAF4ED;
            --primary-ink: #073B1F;
            --mint: #CDE6D2;
            --c-blue: #2563EB;
            --c-blue-soft: #DEE9FE;
            --c-blue-ink: #1E3A8A;
            --c-amber: #C2670A;
            --c-amber-soft: #FBEACB;
            --c-amber-ink: #7A3E03;
            --c-orange: #DD6B20;
            --c-orange-soft: #FCDDC6;
            --c-orange-ink: #7A2E0A;
            --c-green: var(--primary);
            --c-green-soft: var(--primary-soft);
            --c-green-ink: var(--primary-ink);
            --c-red: #C0362F;
            --c-red-soft: #F8DAD6;
            --c-red-ink: #6A1612;
            --c-gray: #687874;
            --c-gray-soft: #E5E8E2;
            --c-gray-ink: #2C342F;
            --r-sm: 10px;
            --r: 12px;
            --r-md: 14px;
            --r-lg: 16px;
            --r-xl: 20px;
            --r-pill: 999px;
            --shadow-xs: 0 1px 0 rgba(15,22,18,.04);
            --shadow-sm: 0 1px 2px rgba(15,22,18,.04), 0 1px 0 rgba(15,22,18,.02);
            --shadow: 0 1px 0 rgba(15,22,18,.03), 0 8px 24px -8px rgba(15,22,18,.08);
            --shadow-md: 0 2px 0 rgba(15,22,18,.03), 0 18px 36px -12px rgba(15,22,18,.12);
            --shadow-lg: 0 30px 60px -20px rgba(15,22,18,.18);
            --font: "Inter", -apple-system, BlinkMacSystemFont, "Segoe UI", system-ui, sans-serif;
            --mono: "JetBrains Mono", ui-monospace, SFMono-Regular, Menlo, monospace;
            /* legacy compat aliases */
            --danger: var(--c-red);
            --radius: var(--r-lg);
            --primary-dark: var(--primary-deep);
            --border: var(--line);
            --shadow-soft: var(--shadow-sm);
        }
        [data-theme="dark"] {
            --bg: #0E1411;
            --bg-deep: #0A100D;
            --surface: #161D19;
            --surface-soft: #1B2420;
            --surface-strong: #232E28;
            --text: #E8EFE9;
            --text-soft: #C4CDC4;
            --muted: #8D998E;
            --muted-soft: #6B756B;
            --line: #243027;
            --line-soft: #1D2722;
            --line-strong: #324036;
            --primary: #3FB370;
            --primary-deep: #2E955A;
            --primary-soft: #163524;
            --primary-soft-2: #122A1C;
            --primary-ink: #B8E6C5;
            --mint: #1F4830;
            --c-blue-soft: #14223F;
            --c-blue-ink: #B7CBF6;
            --c-amber-soft: #3A2606;
            --c-amber-ink: #F2C97D;
            --c-orange-soft: #3F1E08;
            --c-orange-ink: #F2B486;
            --c-red-soft: #3B1410;
            --c-red-ink: #F0B4AE;
            --c-gray-soft: #232A26;
            --c-gray-ink: #BFC8C0;
            --shadow: 0 1px 0 rgba(0,0,0,.3), 0 12px 32px -10px rgba(0,0,0,.55);
            --shadow-sm: 0 1px 2px rgba(0,0,0,.35);
            --shadow-md: 0 2px 0 rgba(0,0,0,.3), 0 20px 40px -14px rgba(0,0,0,.55);
        }

        /* ===== Reset ===== */
        * { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; }
        html { background: var(--bg); }
        body {
            font-family: var(--font);
            color: var(--text);
            background: var(--bg);
            font-size: 14px;
            line-height: 1.5;
            -webkit-font-smoothing: antialiased;
            font-feature-settings: "cv11","ss01","ss03";
            min-height: 100vh;
        }
        a { color: inherit; text-decoration: none; }
        button { font: inherit; cursor: pointer; }
        input, select, textarea { font: inherit; color: inherit; }
        ::selection { background: var(--primary-soft); color: var(--primary-ink); }

        /* ===== Topbar ===== */
        .topbar {
            position: sticky; top: 0; z-index: 50;
            background: var(--surface);
            border-bottom: 1px solid var(--line);
        }
        .topbar-inner {
            max-width: 1480px; margin: 0 auto;
            padding: 0 28px; height: 60px;
            display: flex; align-items: center; gap: 24px;
        }
        .brand { display: flex; align-items: center; gap: 10px; font-weight: 700; letter-spacing: -0.01em; flex-shrink: 0; }
        .brand-mark {
            width: 30px; height: 30px; border-radius: 8px;
            background: linear-gradient(160deg, var(--primary) 0%, var(--primary-deep) 100%);
            color: #fff; display: grid; place-items: center;
            font-size: 11px; font-weight: 800;
            box-shadow: inset 0 1px 0 rgba(255,255,255,.18), 0 2px 6px rgba(10,90,48,.25);
        }
        .brand-name { font-size: 14.5px; font-weight: 700; color: var(--text); }
        .brand-meta { font-size: 11px; color: var(--muted); font-weight: 500; margin-top: -2px; }

        .nav-pills {
            display: flex; align-items: center; gap: 2px;
            background: var(--surface-soft);
            border: 1px solid var(--line);
            border-radius: var(--r-pill);
            padding: 3px;
            overflow-x: auto; scrollbar-width: none;
        }
        .nav-pills::-webkit-scrollbar { display: none; }
        .nav-pill {
            appearance: none; background: transparent; border: 0;
            padding: 7px 13px; border-radius: var(--r-pill);
            font-size: 13px; font-weight: 550; color: var(--muted);
            display: inline-flex; align-items: center; gap: 6px;
            transition: color .12s, background .12s;
            white-space: nowrap; text-decoration: none;
        }
        .nav-pill:hover { color: var(--text); }
        .nav-pill.active {
            background: var(--surface); color: var(--text);
            box-shadow: var(--shadow-sm); font-weight: 600;
        }

        .top-actions { margin-left: auto; display: flex; align-items: center; gap: 8px; }
        .icon-btn {
            width: 34px; height: 34px; border-radius: 10px;
            background: var(--surface); border: 1px solid var(--line);
            display: grid; place-items: center; color: var(--text-soft);
            transition: background .12s, border-color .12s; cursor: pointer;
        }
        .icon-btn:hover { background: var(--surface-soft); border-color: var(--line-strong); }

        /* ===== User chip ===== */
        .user-chip {
            display: flex; align-items: center; gap: 9px;
            height: 36px; padding: 0 12px 0 4px;
            background: var(--surface); border: 1px solid var(--line);
            border-radius: var(--r-pill); transition: background .12s; cursor: default;
        }
        .user-chip:hover { background: var(--surface-soft); }
        .avatar {
            width: 28px; height: 28px; border-radius: 50%;
            display: grid; place-items: center;
            background: var(--primary-soft); color: var(--primary-ink);
            font-weight: 700; font-size: 11px; letter-spacing: 0.01em;
            user-select: none; flex-shrink: 0;
        }
        .avatar.lg { width: 40px; height: 40px; font-size: 13px; }
        .avatar.sm { width: 24px; height: 24px; font-size: 10px; }
        .avatar.h1 { background: #E2EEFC; color: #1E40AF; }
        .avatar.h2 { background: #FCE3D6; color: #7A2E0A; }
        .avatar.h3 { background: #EFE2FC; color: #4C1D95; }
        .avatar.h4 { background: #DFEFE3; color: #073B1F; }
        .avatar.h5 { background: #FBE5EF; color: #7E1948; }
        .avatar.h6 { background: #FEF1C8; color: #7A4D02; }
        .avatar.h7 { background: #DDE9EB; color: #14454B; }
        .user-chip-name { font-size: 13px; font-weight: 600; line-height: 1; }
        .user-chip-role { font-size: 11px; color: var(--muted); margin-top: 2px; line-height: 1; }

        /* ===== Page layout ===== */
        .app-shell { display: flex; flex-direction: column; min-height: 100vh; }
        .page {
            max-width: 1480px; margin: 0 auto;
            padding: 28px 28px 64px; width: 100%;
        }
        .page-head {
            display: flex; align-items: flex-end; justify-content: space-between;
            gap: 24px; margin-bottom: 22px;
        }
        .page-head-left { min-width: 0; }
        .crumb {
            font-size: 12px; color: var(--muted); font-weight: 500;
            display: flex; align-items: center; gap: 6px; margin-bottom: 8px;
        }
        .h-page { margin: 0; font-size: 26px; font-weight: 700; letter-spacing: -0.025em; color: var(--text); }
        .page-sub { margin-top: 4px; font-size: 13.5px; color: var(--muted); max-width: 56ch; }
        .page-actions { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }

        /* ===== Cards ===== */
        .card {
            background: var(--surface); border: 1px solid var(--line);
            border-radius: var(--r-lg); box-shadow: var(--shadow-xs);
        }
        .card + .card { margin-top: 18px; }
        .card-pad { padding: 20px; }
        .card-pad-lg { padding: 24px; }
        .card-head {
            display: flex; align-items: center; justify-content: space-between;
            gap: 16px; padding: 16px 20px; border-bottom: 1px solid var(--line);
        }
        .card-title { margin: 0; font-size: 15px; font-weight: 650; letter-spacing: -0.01em; }
        .card-sub { font-size: 12px; color: var(--muted); margin-top: 2px; }
        .card-body { padding: 20px; }
        .card-foot { padding: 12px 20px; border-top: 1px solid var(--line); display: flex; align-items: center; justify-content: space-between; gap: 12px; }

        /* ===== Buttons ===== */
        .btn {
            display: inline-flex; align-items: center; justify-content: center; gap: 6px;
            height: 36px; padding: 0 14px;
            border-radius: var(--r); border: 1px solid transparent;
            background: var(--primary); color: #fff;
            font-size: 13px; font-weight: 600; letter-spacing: -0.005em;
            white-space: nowrap; cursor: pointer;
            transition: background .12s, border-color .12s, transform .04s;
            text-decoration: none;
        }
        .btn:hover { background: var(--primary-deep); }
        .btn:active { transform: translateY(1px); }
        .btn.sm { height: 30px; padding: 0 11px; font-size: 12.5px; gap: 5px; border-radius: 9px; }
        .btn.lg { height: 42px; padding: 0 18px; font-size: 14px; }
        .btn.ghost { background: var(--surface); color: var(--text-soft); border-color: var(--line); }
        .btn.ghost:hover { background: var(--surface-soft); border-color: var(--line-strong); }
        .btn.subtle { background: var(--surface-soft); color: var(--text-soft); border-color: transparent; }
        .btn.subtle:hover { background: var(--surface-strong); }
        .btn.danger { background: var(--c-red); color: #fff; }
        .btn.danger:hover { background: #962a25; }
        .btn.danger-ghost { background: var(--surface); color: var(--c-red); border-color: var(--c-red-soft); }
        .btn.danger-ghost:hover { background: var(--c-red-soft); color: var(--c-red-ink); }
        .btn:disabled { opacity: .5; cursor: not-allowed; }
        /* legacy compat */
        .btn-primary { background: var(--primary-deep); color: #fff; }
        .btn-secondary { background: #334155; color: #fff; }
        .btn-danger { background: var(--c-red); color: #fff; }
        .btn-light { background: var(--surface-soft); color: var(--text); border: 1px solid var(--line-strong); }
        .btn-sm { height: 30px; padding: 0 11px; font-size: 12.5px; border-radius: 9px; }

        /* ===== Inputs ===== */
        .field { display: flex; flex-direction: column; gap: 6px; min-width: 0; }
        .field-label, .field label {
            font-size: 11.5px; color: var(--muted);
            font-weight: 600; letter-spacing: 0.02em;
            display: block;
        }
        .input, .select, .textarea,
        input:not([type="checkbox"]):not([type="radio"]):not([type="file"]),
        select, textarea {
            height: 36px; border-radius: var(--r);
            border: 1px solid var(--line); background: var(--surface);
            padding: 0 12px; font-size: 13px; color: var(--text);
            transition: border-color .12s, box-shadow .12s;
            width: 100%;
        }
        textarea, .textarea {
            height: auto; min-height: 80px;
            padding: 10px 12px; resize: vertical;
        }
        input:hover, select:hover, textarea:hover { border-color: var(--line-strong); }
        input:focus, select:focus, textarea:focus {
            outline: none; border-color: var(--primary);
            box-shadow: 0 0 0 3px var(--primary-soft);
        }
        input::placeholder { color: var(--muted-soft); }
        select {
            appearance: none;
            background-image: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%23687468' stroke-width='2.4' stroke-linecap='round' stroke-linejoin='round'><polyline points='6 9 12 15 18 9'/></svg>");
            background-repeat: no-repeat; background-position: right 10px center;
            padding-right: 30px;
        }
        input[type="checkbox"], input[type="radio"] { width: auto; height: auto; }
        input[type="file"] { height: auto; padding: 6px 12px; }

        /* ===== Status pills ===== */
        .pill {
            display: inline-flex; align-items: center; gap: 6px;
            height: 22px; padding: 0 9px 0 8px;
            border-radius: var(--r-pill);
            font-size: 11.5px; font-weight: 600; letter-spacing: 0.005em;
            background: var(--c-gray-soft); color: var(--c-gray-ink);
            border: 1px solid transparent; white-space: nowrap;
        }
        .pill::before { content: ''; width: 6px; height: 6px; border-radius: 50%; background: currentColor; opacity: 0.85; }
        .pill.no-dot::before { display: none; }
        .pill-blue { background: var(--c-blue-soft); color: var(--c-blue-ink); }
        .pill-amber { background: var(--c-amber-soft); color: var(--c-amber-ink); }
        .pill-orange { background: var(--c-orange-soft); color: var(--c-orange-ink); }
        .pill-green { background: var(--c-green-soft); color: var(--c-green-ink); }
        .pill-red { background: var(--c-red-soft); color: var(--c-red-ink); }
        .pill-gray { background: var(--c-gray-soft); color: var(--c-gray-ink); }

        /* legacy status-pill compat */
        .status-pill { display: inline-flex; align-items: center; gap: 6px; height: 22px; padding: 0 9px 0 8px; border-radius: var(--r-pill); font-size: 11.5px; font-weight: 600; background: var(--c-gray-soft); color: var(--c-gray-ink); border: 1px solid transparent; white-space: nowrap; }
        .status-pill::before { content: ''; width: 6px; height: 6px; border-radius: 50%; background: currentColor; opacity: 0.85; }
        .status-created { background: var(--c-blue-soft); color: var(--c-blue-ink); }
        .status-assigned, .status-accepted { background: var(--c-blue-soft); color: var(--c-blue-ink); }
        .status-in_progress, .status-problem, .status-postponed { background: var(--c-orange-soft); color: var(--c-orange-ink); }
        .status-completed { background: var(--c-green-soft); color: var(--c-green-ink); }
        .status-rejected { background: var(--c-red-soft); color: var(--c-red-ink); }
        .status-duplicate { background: var(--c-gray-soft); color: var(--c-gray-ink); }

        /* ===== KPI cards ===== */
        .kpi-grid { display: grid; grid-template-columns: repeat(4, minmax(0,1fr)); gap: 14px; }
        .metric-grid { display: grid; grid-template-columns: repeat(4, minmax(0,1fr)); gap: 14px; margin-bottom: 18px; }
        .kpi {
            background: var(--surface); border: 1px solid var(--line);
            border-radius: var(--r-lg); padding: 18px 18px 16px;
            position: relative; overflow: hidden; transition: border-color .12s;
        }
        .kpi:hover { border-color: var(--line-strong); }
        .kpi.tint::before {
            content: ''; position: absolute; inset: 0;
            background: linear-gradient(180deg, var(--tint, var(--primary-soft-2)) 0%, transparent 60%);
            opacity: .55; pointer-events: none;
        }
        .kpi > * { position: relative; z-index: 1; }
        .kpi-head { display: flex; align-items: center; gap: 8px; margin-bottom: 10px; }
        .kpi-icon {
            width: 28px; height: 28px; border-radius: 8px;
            display: grid; place-items: center;
            background: var(--surface-soft); color: var(--text-soft);
            border: 1px solid var(--line);
        }
        .kpi.tint .kpi-icon { background: var(--surface); border-color: var(--line-strong); color: var(--tint-ink, var(--primary-ink)); }
        .kpi-label { font-size: 12px; color: var(--muted); font-weight: 600; letter-spacing: 0.005em; }
        .kpi-value { font-size: 30px; font-weight: 700; letter-spacing: -0.04em; line-height: 1; font-variant-numeric: tabular-nums; margin-top: 2px; }
        .kpi-foot { margin-top: 10px; display: flex; align-items: center; justify-content: space-between; font-size: 12px; }
        .kpi-trend { display: inline-flex; align-items: center; gap: 4px; font-weight: 600; padding: 2px 7px; border-radius: var(--r-pill); }
        .kpi-trend.up { background: var(--c-green-soft); color: var(--c-green-ink); }
        .kpi-trend.down { background: var(--c-red-soft); color: var(--c-red-ink); }
        .kpi-hint { color: var(--muted); }
        .tint-blue { --tint: var(--c-blue-soft); --tint-ink: var(--c-blue-ink); }
        .tint-amber { --tint: var(--c-amber-soft); --tint-ink: var(--c-amber-ink); }
        .tint-orange { --tint: var(--c-orange-soft); --tint-ink: var(--c-orange-ink); }
        .tint-green { --tint: var(--primary-soft); --tint-ink: var(--primary-ink); }
        .tint-red { --tint: var(--c-red-soft); --tint-ink: var(--c-red-ink); }
        /* legacy metric-card compat */
        .metric-card { padding: 18px; border-radius: var(--r-lg); background: var(--surface); border: 1px solid var(--line); box-shadow: var(--shadow-xs); min-width: 0; }
        .metric-card.accent { background: linear-gradient(135deg, var(--primary-soft-2), var(--surface)); border-color: color-mix(in srgb, var(--primary) 20%, var(--line)); }
        .metric-label { color: var(--muted); font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: .08em; }
        .metric-value { margin-top: 8px; font-size: 30px; line-height: 1; font-weight: 700; letter-spacing: -0.04em; font-variant-numeric: tabular-nums; }
        .metric-hint { margin-top: 8px; color: var(--muted); font-size: 13px; }

        /* ===== Table ===== */
        .table-card { background: var(--surface); border: 1px solid var(--line); border-radius: var(--r-lg); overflow: hidden; }
        .table-responsive, .table-wrap { width: 100%; overflow-x: auto; }
        table.tbl, table {
            width: 100%; border-collapse: separate; border-spacing: 0;
            font-size: 13px; min-width: 640px;
        }
        table.table-compact { min-width: 0; }
        .tbl thead th, th {
            background: var(--surface-soft); color: var(--muted);
            font-weight: 600; font-size: 11.5px; letter-spacing: 0.04em;
            text-transform: uppercase; text-align: left;
            padding: 10px 16px; border-bottom: 1px solid var(--line);
            white-space: nowrap; position: sticky; top: 0;
        }
        .tbl tbody td, td {
            padding: 12px 16px; border-bottom: 1px solid var(--line-soft);
            vertical-align: middle;
        }
        .tbl tbody tr:last-child td, tbody tr:last-child td { border-bottom: 0; }
        .tbl tbody tr, tbody tr { transition: background .1s; }
        .tbl tbody tr:hover, tbody tr:hover td { background: var(--surface-soft); }
        .table-actions { text-align: right; white-space: nowrap; }
        .table-actions form { display: inline-flex; margin: 0; }

        /* ===== Filter bar / segmented ===== */
        .filter-bar {
            display: flex; align-items: center; gap: 10px;
            flex-wrap: wrap; padding: 12px 16px;
            border-bottom: 1px solid var(--line);
            background: var(--surface-soft);
        }
        .filter-bar input, .filter-bar select { height: 32px; font-size: 12.5px; border-radius: var(--r-pill); }
        .segmented {
            display: inline-flex;
            background: var(--surface-soft); border: 1px solid var(--line);
            border-radius: var(--r-pill); padding: 3px; gap: 2px;
        }
        .segmented button {
            appearance: none; border: 0; background: transparent;
            padding: 5px 11px; border-radius: var(--r-pill);
            font-size: 12px; font-weight: 550; color: var(--muted);
            display: inline-flex; align-items: center; gap: 5px;
        }
        .segmented button:hover { color: var(--text); }
        .segmented button.active { background: var(--surface); color: var(--text); font-weight: 600; box-shadow: var(--shadow-sm); }

        /* ===== Flash messages ===== */
        .flash-success, .flash-error { margin-bottom: 16px; padding: 12px 16px; border-radius: var(--r); border: 1px solid; font-size: 13.5px; font-weight: 500; }
        .flash-success { background: var(--c-green-soft); color: var(--c-green-ink); border-color: var(--mint); }
        .flash-error { background: var(--c-red-soft); color: var(--c-red-ink); border-color: color-mix(in srgb, var(--c-red) 30%, transparent); }

        /* ===== Layout helpers ===== */
        .row { display: flex; align-items: center; gap: 8px; }
        .row.between { justify-content: space-between; }
        .row.wrap { flex-wrap: wrap; }
        .col { display: flex; flex-direction: column; gap: 6px; }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .grid-3 { display: grid; grid-template-columns: repeat(3,1fr); gap: 16px; }
        .grid-main-side, .grid-12-4 { display: grid; grid-template-columns: minmax(0,1fr) 360px; gap: 18px; align-items: start; }
        .divider { height: 1px; background: var(--line); margin: 16px 0; }
        .muted, .txt-muted { color: var(--muted); }
        .form-actions { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 16px; }
        .toolbar { display: flex; gap: 10px; align-items: flex-end; flex-wrap: wrap; }
        .toolbar .field { min-width: 180px; flex: 1; }
        .empty-state, .empty { padding: 36px 20px; color: var(--muted); text-align: center; }

        /* ===== Timeline ===== */
        .timeline { display: flex; flex-direction: column; gap: 14px; position: relative; }
        .timeline::before { content: ''; position: absolute; left: 11px; top: 6px; bottom: 6px; width: 1.5px; background: var(--line); }
        .timeline-item { display: grid; grid-template-columns: 24px 1fr; gap: 10px; }
        .timeline-date { color: var(--muted); font-size: 13px; font-weight: 600; }

        /* ===== Map ===== */
        .map-card { height: 320px; border-radius: var(--r-lg); overflow: hidden; border: 1px solid var(--line); background: var(--surface-soft); position: relative; isolation: isolate; }
        .map-card #adminTicketsMap, .map-card #ticketsMap { height: 100%; width: 100%; position: relative; z-index: 1; }
        .leaflet-container { font-family: var(--font); position: relative; z-index: 1; }
        .leaflet-popup-content-wrapper { border-radius: var(--r); box-shadow: var(--shadow-md); }
        .pin-marker { width: 30px; height: 38px; position: relative; }
        .pin-marker .pin-body { width: 30px; height: 30px; border-radius: 50% 50% 50% 8px; transform: rotate(-45deg); background: var(--primary); border: 3px solid #fff; box-shadow: 0 8px 18px rgba(0,0,0,.24); }
        .pin-marker .pin-dot { position: absolute; left: 11px; top: 10px; width: 8px; height: 8px; border-radius: 50%; background: #fff; }
        .pin-created .pin-body { background: var(--c-blue); }
        .pin-assigned .pin-body, .pin-accepted .pin-body { background: var(--c-blue); }
        .pin-in_progress .pin-body, .pin-problem .pin-body, .pin-postponed .pin-body { background: var(--c-orange); }
        .pin-completed .pin-body { background: var(--c-green); opacity: .78; }
        .pin-rejected .pin-body, .pin-duplicate .pin-body { background: var(--c-gray); opacity: .72; }

        /* ===== Info grid ===== */
        .info-grid { display: grid; grid-template-columns: repeat(2,minmax(0,1fr)); gap: 14px; }
        .info-item { padding: 14px; border-radius: var(--r); border: 1px solid var(--line); background: var(--surface-soft); }
        .info-label { color: var(--muted); font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: .08em; margin-bottom: 5px; }
        .info-value { font-weight: 600; line-height: 1.45; }

        /* ===== Photo grid ===== */
        .photo-grid { display: grid; grid-template-columns: repeat(auto-fit,minmax(260px,1fr)); gap: 16px; }
        .photo-card { padding: 12px; border-radius: var(--r-md); border: 1px solid var(--line); background: var(--surface-soft); }
        .photo-card img { width: 100%; max-height: 360px; object-fit: cover; border-radius: var(--r); display: block; }

        /* ===== Legacy page-title compat ===== */
        .page-title { margin: 0; font-size: 26px; font-weight: 700; letter-spacing: -0.025em; color: var(--text); }
        .page-description, .page-subtitle, .ticket-meta, .card-muted { color: var(--muted); font-size: 13px; line-height: 1.5; }

        /* ===== Key chip ===== */
        .key-chip { display: inline-flex; align-items: center; gap: 8px; padding: 8px 10px; border-radius: var(--r); border: 1px solid var(--line-strong); background: var(--surface-soft); font-family: var(--mono); font-size: 13px; font-weight: 600; letter-spacing: .03em; }
        .copy-link { height: 34px; padding: 0 10px; border-radius: var(--r); border: 1px solid var(--line); background: var(--surface); color: var(--text); font-weight: 600; cursor: pointer; }
        .pagination-wrap { padding: 14px 20px; }

        /* ===== Mobile drawer ===== */
        .hamburger {
            display: none; width: 36px; height: 36px; border-radius: 10px;
            background: transparent; border: 1px solid var(--line);
            flex-direction: column; align-items: center; justify-content: center;
            gap: 4px; cursor: pointer; flex-shrink: 0;
            transition: background .12s;
        }
        .hamburger:hover { background: var(--surface-soft); }
        .hamburger span {
            display: block; width: 16px; height: 2px;
            background: var(--text-soft); border-radius: 2px;
            transition: transform .2s, opacity .2s;
        }
        .hamburger.open span:nth-child(1) { transform: translateY(6px) rotate(45deg); }
        .hamburger.open span:nth-child(2) { opacity: 0; }
        .hamburger.open span:nth-child(3) { transform: translateY(-6px) rotate(-45deg); }

        .drawer-overlay {
            display: none; position: fixed; inset: 0; z-index: 200;
            background: rgba(0,0,0,.45); backdrop-filter: blur(2px);
        }
        .drawer-overlay.open { display: block; }

        .drawer {
            position: fixed; top: 0; left: 0; bottom: 0; z-index: 201;
            width: 280px; background: var(--surface);
            border-right: 1px solid var(--line); box-shadow: var(--shadow-lg);
            transform: translateX(-100%);
            transition: transform .22s cubic-bezier(.4,0,.2,1);
            display: flex; flex-direction: column;
            overflow-y: auto;
        }
        .drawer.open { transform: translateX(0); }

        .drawer-head {
            display: flex; align-items: center; justify-content: space-between;
            padding: 14px 16px; border-bottom: 1px solid var(--line); flex-shrink: 0;
        }
        .drawer-brand { display: flex; align-items: center; gap: 9px; font-weight: 700; font-size: 14px; }
        .drawer-close {
            width: 32px; height: 32px; border-radius: 8px; border: 0;
            background: var(--surface-soft); color: var(--text-soft);
            cursor: pointer; display: grid; place-items: center; font-size: 18px;
        }
        .drawer-nav { display: flex; flex-direction: column; gap: 2px; padding: 12px 8px; flex: 1; }
        .drawer-nav-item {
            display: flex; align-items: center; padding: 10px 12px;
            border-radius: var(--r); font-size: 14px; font-weight: 500;
            color: var(--text-soft); text-decoration: none;
            transition: background .1s, color .1s;
        }
        .drawer-nav-item:hover { background: var(--surface-soft); color: var(--text); }
        .drawer-nav-item.active { background: var(--primary-soft); color: var(--primary-ink); font-weight: 650; }
        .drawer-foot { padding: 12px 16px; border-top: 1px solid var(--line); flex-shrink: 0; }

        /* ===== Responsive ===== */
        @media (max-width: 1100px) { .brand-meta { display: none; } .topbar-inner { gap: 16px; padding: 0 20px; } }
        @media (max-width: 960px) { .user-chip-name, .user-chip-role { display: none; } .user-chip { padding: 0 4px; height: 36px; } }
        @media (max-width: 900px) {
            .topbar-inner { padding: 0 14px; height: 56px; gap: 10px; }
            .nav-pills { display: none; }
            .hamburger { display: flex; }
            .page { padding: 18px 14px 48px; }
            .page-head { flex-direction: column; align-items: stretch; }
            .grid-main-side, .grid-12-4 { grid-template-columns: 1fr; }
        }
        @media (max-width: 760px) { .kpi-grid, .metric-grid { grid-template-columns: 1fr 1fr !important; gap: 10px; } .kpi-value { font-size: 24px; } .grid-2, .grid-3 { grid-template-columns: 1fr; } .info-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
@php
    $currentAdmin = auth()->user();
    $isSuperAdminLayout = $currentAdmin && in_array($currentAdmin->role, ['admin', 'super_admin'], true);
    $isOrgAdminLayout = $currentAdmin && $currentAdmin->role === 'org_admin';
    $userInitial = $currentAdmin ? mb_strtoupper(mb_substr($currentAdmin->name ?? $currentAdmin->email, 0, 1)) : '?';
    $hues = ['A' => 'h1','Б'=>'h2','В'=>'h3','Г'=>'h4','Д'=>'h5','Е'=>'h6','Ж'=>'h7','З'=>'h1','И'=>'h2','К'=>'h3','Л'=>'h4','М'=>'h5','Н'=>'h6','О'=>'h7','П'=>'h1','Р'=>'h2','С'=>'h3','Т'=>'h4','У'=>'h5','Ф'=>'h6','Х'=>'h7','Ц'=>'h1','Ч'=>'h2','Ш'=>'h3','Щ'=>'h4','Э'=>'h5','Ю'=>'h6','Я'=>'h7'];
    $hueClass = $hues[$userInitial] ?? 'h4';
@endphp
<div class="app-shell">
    <header class="topbar">
        <div class="topbar-inner">
            <a class="brand" href="{{ $isSuperAdminLayout ? route('admin.dashboard') : ($isOrgAdminLayout ? route('admin.dashboard') : '#') }}">
                <div class="brand-mark">ЧГ</div>
                <div>
                    <div class="brand-name">Чистый город</div>
                    <div class="brand-meta">Управление</div>
                </div>
            </a>

            @if($isSuperAdminLayout || $isOrgAdminLayout)
            <button class="hamburger" id="hamburgerBtn" type="button" aria-label="Меню">
                <span></span><span></span><span></span>
            </button>
            @endif

            @if($isSuperAdminLayout)
            <nav class="nav-pills">
                <a class="nav-pill {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">Контроль</a>
                <a class="nav-pill {{ request()->routeIs('admin.tickets.index') || request()->routeIs('admin.tickets.show') ? 'active' : '' }}" href="{{ route('admin.tickets.index') }}">Заявки</a>
                <a class="nav-pill {{ request()->routeIs('admin.tickets.map') ? 'active' : '' }}" href="{{ route('admin.tickets.map') }}">Карта</a>
                <a class="nav-pill {{ request()->routeIs('admin.analytics.*') ? 'active' : '' }}" href="{{ route('admin.analytics.index') }}">Аналитика</a>
                <a class="nav-pill {{ request()->routeIs('admin.users.*') ? 'active' : '' }}" href="{{ route('admin.users.index') }}">Пользователи</a>
                <a class="nav-pill {{ request()->routeIs('admin.employees.*') ? 'active' : '' }}" href="{{ route('admin.employees.index') }}">Сотрудники</a>
                <a class="nav-pill {{ request()->routeIs('admin.organizations.*') ? 'active' : '' }}" href="{{ route('admin.organizations.index') }}">ЖКХ</a>
                <a class="nav-pill {{ request()->routeIs('admin.claim-requests.*') ? 'active' : '' }}" href="{{ route('admin.claim-requests.index') }}">Запросы</a>
                <a class="nav-pill {{ request()->routeIs('admin.complaints.*') ? 'active' : '' }}" href="{{ route('admin.complaints.index') }}">Жалобы</a>
                <a class="nav-pill {{ request()->routeIs('admin.news.*') ? 'active' : '' }}" href="{{ route('admin.news.index') }}">Новости</a>
                <a class="nav-pill {{ request()->routeIs('admin.rewards.*') ? 'active' : '' }}" href="{{ route('admin.rewards.index') }}">Купоны</a>
                <a class="nav-pill {{ request()->routeIs('admin.points.*') ? 'active' : '' }}" href="{{ route('admin.points.index') }}">Баллы</a>
                <a class="nav-pill {{ request()->routeIs('admin.cities.*') ? 'active' : '' }}" href="{{ route('admin.cities.index') }}">Города</a>
            </nav>
            @elseif($isOrgAdminLayout)
            <nav class="nav-pills">
                <a class="nav-pill {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">Обзор</a>
                <a class="nav-pill {{ request()->routeIs('admin.tickets.index') || request()->routeIs('admin.tickets.show') ? 'active' : '' }}" href="{{ route('admin.tickets.index') }}">Заявки</a>
                <a class="nav-pill {{ request()->routeIs('admin.tickets.map') ? 'active' : '' }}" href="{{ route('admin.tickets.map') }}">Карта</a>
                <a class="nav-pill {{ request()->routeIs('admin.claim-requests.*') ? 'active' : '' }}" href="{{ route('admin.claim-requests.index') }}">Запросы</a>
                <a class="nav-pill {{ request()->routeIs('admin.employees.*') ? 'active' : '' }}" href="{{ route('admin.employees.index') }}">Сотрудники</a>
                <a class="nav-pill {{ request()->routeIs('admin.complaints.*') ? 'active' : '' }}" href="{{ route('admin.complaints.index') }}">Жалобы</a>
                <a class="nav-pill {{ request()->routeIs('admin.news.*') ? 'active' : '' }}" href="{{ route('admin.news.index') }}">Новости</a>
                <a class="nav-pill {{ request()->routeIs('admin.rewards.*') ? 'active' : '' }}" href="{{ route('admin.rewards.index') }}">Купоны</a>
                <a class="nav-pill {{ request()->routeIs('admin.analytics.*') ? 'active' : '' }}" href="{{ route('admin.analytics.index') }}">Отчёт</a>
            </nav>
            @endif

            <div class="top-actions">
                <button class="icon-btn" type="button" id="themeToggle" title="Сменить тему">
                    <svg id="themeIcon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/>
                    </svg>
                </button>
                @auth
                    <div class="user-chip">
                        <div class="avatar {{ $hueClass }}">{{ $userInitial }}</div>
                        <div>
                            <div class="user-chip-name">{{ auth()->user()->name ?? auth()->user()->email }}</div>
                            <div class="user-chip-role">{{ auth()->user()->role }}</div>
                        </div>
                    </div>
                    <form method="POST" action="{{ route('admin.logout') }}" style="margin:0;">
                        @csrf
                        <button type="submit" class="btn ghost sm">Выйти</button>
                    </form>
                @endauth
            </div>
        </div>
    </header>

    <main>
        <div class="page">
            @if(session('success'))
                <div class="flash-success">{{ session('success') }}</div>
            @endif
            @if($errors->any())
                <div class="flash-error">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>
            @endif
            @yield('content')
        </div>
    </main>
</div>

{{-- ===== Mobile drawer ===== --}}
@if($isSuperAdminLayout || $isOrgAdminLayout)
<div class="drawer-overlay" id="drawerOverlay"></div>
<nav class="drawer" id="drawer" aria-label="Навигация">
    <div class="drawer-head">
        <div class="drawer-brand">
            <div class="brand-mark">ЧГ</div>
            Чистый город
        </div>
        <button class="drawer-close" id="drawerClose" type="button">×</button>
    </div>

    <div class="drawer-nav">
        @if($isSuperAdminLayout)
            <a class="drawer-nav-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">Контроль</a>
            <a class="drawer-nav-item {{ request()->routeIs('admin.tickets.*') ? 'active' : '' }}" href="{{ route('admin.tickets.index') }}">Заявки</a>
            <a class="drawer-nav-item {{ request()->routeIs('admin.tickets.map') ? 'active' : '' }}" href="{{ route('admin.tickets.map') }}">Карта</a>
            <a class="drawer-nav-item {{ request()->routeIs('admin.analytics.*') ? 'active' : '' }}" href="{{ route('admin.analytics.index') }}">Аналитика</a>
            <a class="drawer-nav-item {{ request()->routeIs('admin.users.*') ? 'active' : '' }}" href="{{ route('admin.users.index') }}">Пользователи</a>
            <a class="drawer-nav-item {{ request()->routeIs('admin.employees.*') ? 'active' : '' }}" href="{{ route('admin.employees.index') }}">Сотрудники</a>
            <a class="drawer-nav-item {{ request()->routeIs('admin.organizations.*') ? 'active' : '' }}" href="{{ route('admin.organizations.index') }}">ЖКХ</a>
            <a class="drawer-nav-item {{ request()->routeIs('admin.claim-requests.*') ? 'active' : '' }}" href="{{ route('admin.claim-requests.index') }}">Запросы</a>
            <a class="drawer-nav-item {{ request()->routeIs('admin.complaints.*') ? 'active' : '' }}" href="{{ route('admin.complaints.index') }}">Жалобы</a>
            <a class="drawer-nav-item {{ request()->routeIs('admin.news.*') ? 'active' : '' }}" href="{{ route('admin.news.index') }}">Новости</a>
            <a class="drawer-nav-item {{ request()->routeIs('admin.rewards.*') ? 'active' : '' }}" href="{{ route('admin.rewards.index') }}">Купоны</a>
            <a class="drawer-nav-item {{ request()->routeIs('admin.points.*') ? 'active' : '' }}" href="{{ route('admin.points.index') }}">Баллы</a>
            <a class="drawer-nav-item {{ request()->routeIs('admin.cities.*') ? 'active' : '' }}" href="{{ route('admin.cities.index') }}">Города</a>
        @elseif($isOrgAdminLayout)
            <a class="drawer-nav-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">Обзор</a>
            <a class="drawer-nav-item {{ request()->routeIs('admin.tickets.index') || request()->routeIs('admin.tickets.show') ? 'active' : '' }}" href="{{ route('admin.tickets.index') }}">Заявки</a>
            <a class="drawer-nav-item {{ request()->routeIs('admin.tickets.map') ? 'active' : '' }}" href="{{ route('admin.tickets.map') }}">Карта</a>
            <a class="drawer-nav-item {{ request()->routeIs('admin.claim-requests.*') ? 'active' : '' }}" href="{{ route('admin.claim-requests.index') }}">Запросы</a>
            <a class="drawer-nav-item {{ request()->routeIs('admin.employees.*') ? 'active' : '' }}" href="{{ route('admin.employees.index') }}">Сотрудники</a>
            <a class="drawer-nav-item {{ request()->routeIs('admin.complaints.*') ? 'active' : '' }}" href="{{ route('admin.complaints.index') }}">Жалобы</a>
            <a class="drawer-nav-item {{ request()->routeIs('admin.news.*') ? 'active' : '' }}" href="{{ route('admin.news.index') }}">Новости</a>
            <a class="drawer-nav-item {{ request()->routeIs('admin.rewards.*') ? 'active' : '' }}" href="{{ route('admin.rewards.index') }}">Купоны</a>
            <a class="drawer-nav-item {{ request()->routeIs('admin.analytics.*') ? 'active' : '' }}" href="{{ route('admin.analytics.index') }}">Отчёт</a>
        @endif
    </div>

    <div class="drawer-foot">
        @auth
        <form method="POST" action="{{ route('admin.logout') }}" style="margin:0;">
            @csrf
            <button type="submit" class="btn ghost" style="width:100%;">Выйти из системы</button>
        </form>
        @endauth
    </div>
</nav>
@endif

<script>
    const toggle = document.getElementById('themeToggle');
    const moonPath = '<path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>';
    const sunPath = '<circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/>';

    function syncTheme() {
        const t = document.documentElement.getAttribute('data-theme') || 'light';
        const icon = document.getElementById('themeIcon');
        if (icon) icon.innerHTML = t === 'dark' ? moonPath : sunPath;
    }
    const savedTheme = localStorage.getItem('cleanCityAdminTheme');
    if (savedTheme) document.documentElement.setAttribute('data-theme', savedTheme);
    syncTheme();
    toggle?.addEventListener('click', function() {
        const cur = document.documentElement.getAttribute('data-theme') || 'light';
        const next = cur === 'dark' ? 'light' : 'dark';
        document.documentElement.setAttribute('data-theme', next);
        localStorage.setItem('cleanCityAdminTheme', next);
        syncTheme();
    });

    // ── Mobile drawer ──────────────────────────────────────────────────────
    const hamburgerBtn  = document.getElementById('hamburgerBtn');
    const drawer        = document.getElementById('drawer');
    const drawerOverlay = document.getElementById('drawerOverlay');
    const drawerClose   = document.getElementById('drawerClose');

    function openDrawer() {
        drawer?.classList.add('open');
        drawerOverlay?.classList.add('open');
        hamburgerBtn?.classList.add('open');
        document.body.style.overflow = 'hidden';
    }
    function closeDrawer() {
        drawer?.classList.remove('open');
        drawerOverlay?.classList.remove('open');
        hamburgerBtn?.classList.remove('open');
        document.body.style.overflow = '';
    }

    hamburgerBtn?.addEventListener('click', () => {
        drawer?.classList.contains('open') ? closeDrawer() : openDrawer();
    });
    drawerOverlay?.addEventListener('click', closeDrawer);
    drawerClose?.addEventListener('click', closeDrawer);

    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') closeDrawer();
    });
</script>
@stack('scripts')
</body>
</html>
