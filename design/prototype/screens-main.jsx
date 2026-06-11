/* global React, Icon, StatusPill, Avatar, KPI, Card, CardHead, Drawer, Modal,
   Toast, Empty, PhotoSlot, Search, InlineField, BarChart, Spark, Donut,
   STATUS, CATEGORIES, ORGS, WORKERS, CODES, TICKETS, STATS_ORG, DAILY,
   CATEGORY_STATS, CLAIMS */
const { useState, useEffect, useMemo, useRef, useCallback } = React;

// ============================================================
// HEADER
// ============================================================
function TopBar({ current, onNav, role, theme, onTheme, counts }) {
    const navItems = role === 'super_admin'
        ? [
            { id: 'dashboard', label: 'Контроль', icon: 'home' },
            { id: 'users',     label: 'Пользователи', icon: 'users' },
            { id: 'orgs',      label: 'ЖКХ', icon: 'building' },
        ]
        : [
            { id: 'dashboard', label: 'Обзор',      icon: 'home',  count: null },
            { id: 'tickets',   label: 'Заявки',     icon: 'list',  count: counts?.tickets },
            { id: 'map',       label: 'Карта',      icon: 'map',   count: null },
            { id: 'claims',    label: 'Запросы',    icon: 'inbox', count: counts?.claims },
            { id: 'employees', label: 'Сотрудники', icon: 'users', count: null },
            { id: 'report',    label: 'Отчёт',      icon: 'chart', count: null },
        ];
    return (
        <header className="topbar">
            <div className="topbar-inner">
                <div className="brand">
                    <div className="brand-mark">ЧГ</div>
                    <div>
                        <div className="brand-name">Чистый город</div>
                        <div className="brand-meta">{role === 'super_admin' ? 'Платформа' : 'ЖЭС-1 Северный · Барановичи'}</div>
                    </div>
                </div>

                <nav className="nav-pills" aria-label="Основная навигация">
                    {navItems.map(it => (
                        <button
                            key={it.id}
                            className={`nav-pill ${current === it.id ? 'active' : ''}`}
                            onClick={() => onNav(it.id)}>
                            <Icon name={it.icon} size={14} />
                            {it.label}
                            {it.count > 0 && <span className="nav-count">{it.count}</span>}
                        </button>
                    ))}
                </nav>

                <div className="top-actions">
                    <button className="icon-btn" title="Поиск"><Icon name="search" size={15} /></button>
                    <button className="icon-btn" title="Уведомления">
                        <Icon name="bell" size={15} />
                        <span className="dot" />
                    </button>
                    <button className="icon-btn" title={theme === 'dark' ? 'Тёмная' : 'Светлая'} onClick={onTheme}>
                        <Icon name={theme === 'dark' ? 'sun' : 'moon'} size={15} />
                    </button>
                    <div className="user-chip">
                        <Avatar name="Виктор Лесюк" size="sm" />
                        <div>
                            <div className="user-chip-name">Виктор Лесюк</div>
                            <div className="user-chip-role">{role === 'super_admin' ? 'Главный админ' : 'Админ ЖЭС-1'}</div>
                        </div>
                        <Icon name="chev" size={13} className="chev" />
                    </div>
                </div>
            </div>
        </header>
    );
}

// ============================================================
// DASHBOARD (org_admin)
// ============================================================
function DashboardScreen({ onNav, onOpenTicket, onToast }) {
    const queueTickets = TICKETS.filter(t => ['created', 'assigned', 'in_progress'].includes(t.status)).slice(0, 6);
    const completed = TICKETS.filter(t => t.status === 'completed').slice(0, 5);

    const donutSegs = [
        { value: 11, color: 'var(--c-blue)',   label: 'Новые'      },
        { value: 8,  color: 'var(--c-amber)',  label: 'Назначены'  },
        { value: 7,  color: 'var(--c-orange)', label: 'В работе'   },
        { value: 21, color: 'var(--c-green)',  label: 'Выполнены'  },
        { value: 3,  color: 'var(--c-red)',    label: 'Отклонены'  },
    ];
    const donutTotal = donutSegs.reduce((s, x) => s + x.value, 0);

    return (
        <div className="page">
            <div className="page-head">
                <div className="page-head-left">
                    <div className="crumb">
                        <Icon name="home" size={12} />
                        <span>Главная</span>
                        <Icon name="chevR" size={11} />
                        <span>Обзор</span>
                    </div>
                    <h1 className="h-page">Доброе утро, Виктор</h1>
                    <p className="page-sub">Текущая нагрузка ЖЭС-1 Северный · сегодня среда, 27 мая 2026 · обновлено 2 мин назад</p>
                </div>
                <div className="page-actions">
                    <button className="btn ghost"><Icon name="route" size={14} />Маршрутный лист</button>
                    <button className="btn ghost" onClick={() => onNav('report')}><Icon name="download" size={14} />Отчёт</button>
                    <button className="btn" onClick={() => onNav('tickets')}><Icon name="list" size={14} />Открыть заявки</button>
                </div>
            </div>

            {/* KPI row */}
            <div className="kpi-grid" style={{ marginBottom: 18 }}>
                <KPI icon="inbox" tint="blue" label="Новые" value="11" hint="ожидают обработки" trend="+3" trendDir="up" />
                <KPI icon="activity" tint="orange" label="В работе" value="15" hint="назначены и выполняются" trend="+4" trendDir="up" />
                <KPI icon="check" tint="green" label="Выполнено за неделю" value="42" hint="ср. время 4ч 18м" trend="+18%" trendDir="up" />
                <KPI icon="alert" tint="red" label="SLA-риск" value="3" hint="старше 48 часов" trend="−1" trendDir="down" />
            </div>

            <div className="grid-12-4">
                {/* Left column */}
                <div className="col-16">
                    {/* Queue */}
                    <Card>
                        <CardHead
                            icon="list"
                            title="Очередь обработки"
                            sub="Назначить исполнителя или передвинуть в работу"
                            actions={
                                <>
                                    <button className="btn subtle sm">Авто-назначить</button>
                                    <button className="btn ghost sm" onClick={() => onNav('tickets')}>Все заявки →</button>
                                </>
                            }
                        />
                        <div className="table-wrap">
                            <table className="tbl">
                                <thead>
                                    <tr>
                                        <th>Заявка</th>
                                        <th>Категория</th>
                                        <th>Статус</th>
                                        <th>Исполнитель</th>
                                        <th>Возраст</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {queueTickets.map((t, i) => (
                                        <tr key={t.id} onClick={() => onOpenTicket(t)}>
                                            <td>
                                                <div className="id-cell">№{t.id}</div>
                                                <div className="secondary" style={{ maxWidth: 260, overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>{t.address}</div>
                                            </td>
                                            <td><span className="tag">{t.category.name}</span></td>
                                            <td><StatusPill status={t.status} /></td>
                                            <td>
                                                {t.worker ? (
                                                    <div className="row" style={{ gap: 8 }}>
                                                        <Avatar name={t.worker.name} />
                                                        <span style={{ fontSize: 12.5 }}>{t.worker.name.split(' ')[0]} {t.worker.name.split(' ')[1]?.[0]}.</span>
                                                    </div>
                                                ) : <span className="txt-muted">—</span>}
                                            </td>
                                            <td className="num"><span className="txt-muted">{(i * 3 + 2)} ч</span></td>
                                            <td className="row-actions">
                                                <button className="btn sm ghost" onClick={(e) => { e.stopPropagation(); onOpenTicket(t); }}>Открыть</button>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </Card>

                    {/* Recently closed */}
                    <Card>
                        <CardHead
                            icon="check"
                            title="Недавно закрыто"
                            sub="Последние выполненные обращения"
                            actions={<button className="btn ghost sm" onClick={() => onNav('tickets')}>Все →</button>}
                        />
                        <div className="table-wrap">
                            <table className="tbl">
                                <thead>
                                    <tr>
                                        <th>Заявка</th>
                                        <th>Адрес</th>
                                        <th>Исполнитель</th>
                                        <th>Время выполнения</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {completed.map(t => (
                                        <tr key={t.id} onClick={() => onOpenTicket(t)}>
                                            <td>
                                                <div className="id-cell">№{t.id}</div>
                                                <div className="secondary">{t.category.name}</div>
                                            </td>
                                            <td style={{ maxWidth: 240 }}>{t.address}</td>
                                            <td>
                                                {t.worker && (
                                                    <div className="row" style={{ gap: 8 }}>
                                                        <Avatar name={t.worker.name} />
                                                        <span style={{ fontSize: 12.5 }}>{t.worker.name}</span>
                                                    </div>
                                                )}
                                            </td>
                                            <td className="num txt-muted">{t.duration || '—'}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </Card>
                </div>

                {/* Right column */}
                <div className="col-16">
                    {/* Distribution donut */}
                    <Card className="card-pad">
                        <div className="row between" style={{ marginBottom: 14 }}>
                            <div>
                                <h3 className="card-title">Распределение</h3>
                                <div className="card-sub">за последние 14 дней</div>
                            </div>
                            <button className="btn ghost sm"><Icon name="more" size={14} /></button>
                        </div>
                        <div style={{ display: 'flex', gap: 22, alignItems: 'center', paddingBottom: 4 }}>
                            <Donut segments={donutSegs} label={donutTotal} sub="заявок" />
                            <div style={{ flex: 1, display: 'flex', flexDirection: 'column', gap: 8 }}>
                                {donutSegs.map((s, i) => (
                                    <div key={i} className="row" style={{ justifyContent: 'space-between', fontSize: 12.5 }}>
                                        <span className="row" style={{ gap: 8 }}>
                                            <span style={{ width: 8, height: 8, borderRadius: 2, background: s.color }} />
                                            <span>{s.label}</span>
                                        </span>
                                        <span className="num txt-strong">{s.value}</span>
                                    </div>
                                ))}
                            </div>
                        </div>
                    </Card>

                    {/* Daily dynamics */}
                    <Card className="card-pad">
                        <div className="row between" style={{ marginBottom: 8 }}>
                            <div>
                                <h3 className="card-title">Динамика поступления</h3>
                                <div className="card-sub">10 дней · всего 116 заявок</div>
                            </div>
                            <div className="segmented">
                                <button>День</button>
                                <button className="active">Неделя</button>
                                <button>Месяц</button>
                            </div>
                        </div>
                        <BarChart data={DAILY} />
                    </Card>

                    {/* Problem categories */}
                    <Card className="card-pad">
                        <div className="row between" style={{ marginBottom: 14 }}>
                            <div>
                                <h3 className="card-title">Проблемные категории</h3>
                                <div className="card-sub">рост за неделю</div>
                            </div>
                            <button className="btn ghost sm" onClick={() => onNav('report')}>Подробнее</button>
                        </div>
                        <div className="bars">
                            {CATEGORY_STATS.map((c, i) => {
                                const max = Math.max(...CATEGORY_STATS.map(x => x.total));
                                return (
                                    <div key={i} className="bars-row" style={{ gridTemplateColumns: '1fr 1fr 50px' }}>
                                        <span style={{ fontSize: 12.5, fontWeight: 550, overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>{c.name}</span>
                                        <div className="bar-track"><div className="bar-fill" style={{ width: (c.total / max * 100) + '%' }} /></div>
                                        <span className="bar-num">{c.total}</span>
                                    </div>
                                );
                            })}
                        </div>
                    </Card>
                </div>
            </div>
        </div>
    );
}

// ============================================================
// TICKETS LIST
// ============================================================
function TicketsScreen({ onOpenTicket, onToast }) {
    const [statusFilter, setStatusFilter] = useState('all');
    const [search, setSearch] = useState('');
    const [category, setCategory] = useState('');
    const [worker, setWorker] = useState('');
    const [showFilters, setShowFilters] = useState(true);

    const counts = useMemo(() => {
        const c = { all: TICKETS.length, created: 0, assigned: 0, in_progress: 0, completed: 0, rejected: 0 };
        TICKETS.forEach(t => { if (c[t.status] !== undefined) c[t.status]++; });
        return c;
    }, []);

    const filtered = useMemo(() => TICKETS.filter(t => {
        if (statusFilter === 'all') {
            // all but hidden/duplicate
        } else if (statusFilter === 'active') {
            if (!['assigned', 'in_progress', 'accepted'].includes(t.status)) return false;
        } else if (t.status !== statusFilter) return false;
        if (category && String(t.category.id) !== String(category)) return false;
        if (worker && (!t.worker || String(t.worker.id) !== String(worker))) return false;
        if (search) {
            const q = search.toLowerCase();
            if (!(`№${t.id}`).includes(q) && !t.address.toLowerCase().includes(q) && !t.description.toLowerCase().includes(q)) return false;
        }
        return true;
    }), [statusFilter, category, worker, search]);

    return (
        <div className="page">
            <div className="page-head">
                <div className="page-head-left">
                    <div className="crumb">
                        <Icon name="home" size={12} />
                        <span>Главная</span>
                        <Icon name="chevR" size={11} />
                        <span>Заявки</span>
                    </div>
                    <h1 className="h-page">Заявки</h1>
                    <p className="page-sub">Реестр обращений жителей · {filtered.length} из {TICKETS.length} записей</p>
                </div>
                <div className="page-actions">
                    <button className="btn ghost"><Icon name="upload" size={14} />Экспорт</button>
                    <button className="btn"><Icon name="plus" size={14} />Новая заявка</button>
                </div>
            </div>

            {/* Mini-KPI */}
            <div className="kpi-grid kpi-grid-5" style={{ marginBottom: 18 }}>
                <KPI icon="list" tint="gray" label="Всего" value={STATS_ORG.total} hint="в реестре" />
                <KPI icon="inbox" tint="blue" label="Новые" value={STATS_ORG.new} hint="ожидают обработки" />
                <KPI icon="activity" tint="orange" label="В работе" value={STATS_ORG.active} hint="назначены/выполняются" />
                <KPI icon="check" tint="green" label="Выполнено" value={STATS_ORG.completed} hint="закрытые" />
                <KPI icon="eyeOff" tint="gray" label="Скрыто" value={STATS_ORG.hidden} hint="не входят в отчёт" />
            </div>

            <Card>
                {/* status quick filters */}
                <div className="card-head" style={{ flexWrap: 'wrap', gap: 12 }}>
                    <div className="segmented">
                        <button className={statusFilter === 'all' ? 'active' : ''} onClick={() => setStatusFilter('all')}>Все <span className="cnt">{counts.all}</span></button>
                        <button className={statusFilter === 'created' ? 'active' : ''} onClick={() => setStatusFilter('created')}>Новые <span className="cnt">{counts.created}</span></button>
                        <button className={statusFilter === 'active' ? 'active' : ''} onClick={() => setStatusFilter('active')}>В работе <span className="cnt">{counts.assigned + counts.in_progress}</span></button>
                        <button className={statusFilter === 'completed' ? 'active' : ''} onClick={() => setStatusFilter('completed')}>Выполнено <span className="cnt">{counts.completed}</span></button>
                        <button className={statusFilter === 'rejected' ? 'active' : ''} onClick={() => setStatusFilter('rejected')}>Отклонено <span className="cnt">{counts.rejected}</span></button>
                    </div>
                    <div className="row" style={{ gap: 8, marginLeft: 'auto' }}>
                        <Search value={search} onChange={setSearch} placeholder="№ заявки, адрес или описание" kbd="⌘K" />
                        <button className={`btn ${showFilters ? 'subtle' : 'ghost'} sm`} onClick={() => setShowFilters(v => !v)}>
                            <Icon name="sliders" size={14} />Фильтры
                        </button>
                    </div>
                </div>

                {showFilters && (
                    <div className="filter-bar">
                        <select className="select" value={category} onChange={e => setCategory(e.target.value)} style={{ width: 200 }}>
                            <option value="">Все категории</option>
                            {CATEGORIES.map(c => <option key={c.id} value={c.id}>{c.name}</option>)}
                        </select>
                        <select className="select" value={worker} onChange={e => setWorker(e.target.value)} style={{ width: 200 }}>
                            <option value="">Все исполнители</option>
                            {WORKERS.map(w => <option key={w.id} value={w.id}>{w.name}</option>)}
                        </select>
                        <select className="select" style={{ width: 160 }}>
                            <option>Период: 7 дней</option>
                            <option>30 дней</option>
                            <option>Весь период</option>
                        </select>
                        <select className="select" style={{ width: 160 }}>
                            <option>Приоритет: любой</option>
                            <option>Высокий</option>
                            <option>Обычный</option>
                            <option>Низкий</option>
                        </select>
                        <div style={{ marginLeft: 'auto', display: 'flex', gap: 8 }}>
                            <button className="btn ghost sm" onClick={() => { setCategory(''); setWorker(''); setSearch(''); setStatusFilter('all'); }}>Сбросить</button>
                            <button className="btn sm">Применить</button>
                        </div>
                    </div>
                )}

                <div className="table-wrap">
                    <table className="tbl">
                        <thead>
                            <tr>
                                <th>№</th>
                                <th>Адрес и описание</th>
                                <th>Категория</th>
                                <th>Статус</th>
                                <th>Исполнитель</th>
                                <th>Создана</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            {filtered.length === 0 ? (
                                <tr><td colSpan={7}><Empty icon="search" title="Заявок не найдено" sub="Попробуйте сбросить фильтры или изменить запрос." /></td></tr>
                            ) : filtered.map(t => (
                                <tr key={t.id} onClick={() => onOpenTicket(t)}>
                                    <td><span className="id-cell">№{t.id}</span></td>
                                    <td style={{ maxWidth: 360 }}>
                                        <div className="txt-strong" style={{ overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>{t.address}</div>
                                        <div className="secondary" style={{ maxWidth: 360, overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>{t.description}</div>
                                    </td>
                                    <td><span className="tag">{t.category.name}</span></td>
                                    <td><StatusPill status={t.status} /></td>
                                    <td>
                                        {t.worker ? (
                                            <div className="row" style={{ gap: 8 }}>
                                                <Avatar name={t.worker.name} />
                                                <span style={{ fontSize: 12.5 }}>{t.worker.name}</span>
                                            </div>
                                        ) : <span className="txt-muted">не назначен</span>}
                                    </td>
                                    <td className="txt-muted num">{t.created}</td>
                                    <td className="row-actions">
                                        <button className="btn sm ghost" onClick={(e) => { e.stopPropagation(); onOpenTicket(t); }}>Открыть</button>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
                <div className="card-foot">
                    <div className="txt-muted" style={{ fontSize: 12.5 }}>Показано {filtered.length} из {TICKETS.length}</div>
                    <div className="row" style={{ gap: 6 }}>
                        <button className="btn ghost sm btn-icon"><Icon name="chevL" size={14} /></button>
                        <button className="btn subtle sm">1</button>
                        <button className="btn ghost sm">2</button>
                        <button className="btn ghost sm">3</button>
                        <button className="btn ghost sm btn-icon"><Icon name="chevR" size={14} /></button>
                    </div>
                </div>
            </Card>
        </div>
    );
}

// ============================================================
// TICKET DRAWER (detail)
// ============================================================
function TicketDrawer({ ticket, onClose, onToast, onConfirm }) {
    if (!ticket) return null;
    const [tab, setTab] = useState('overview');
    const [assignWorker, setAssignWorker] = useState(ticket.worker?.id || '');
    const [statusVal, setStatusVal] = useState(ticket.status);

    return (
        <Drawer open={!!ticket} onClose={onClose}>
            <div className="drawer-head">
                <div style={{ minWidth: 0 }}>
                    <div className="row" style={{ gap: 8, marginBottom: 6 }}>
                        <StatusPill status={ticket.status} />
                        <span className="tag">{ticket.category.name}</span>
                        {ticket.priority === 'high' && <span className="tag" style={{ background: 'var(--c-red-soft)', color: 'var(--c-red-ink)', border: 'none' }}><Icon name="flag" size={11} />Высокий</span>}
                    </div>
                    <div className="row" style={{ gap: 8, alignItems: 'baseline' }}>
                        <h2 style={{ margin: 0, fontSize: 18, letterSpacing: '-0.02em', fontWeight: 700 }}>Обращение №{ticket.id}</h2>
                        <span className="txt-muted" style={{ fontSize: 12 }}>· {ticket.created}</span>
                    </div>
                    <div className="txt-muted" style={{ fontSize: 12.5, marginTop: 4 }}>{ticket.address}</div>
                </div>
                <div className="row" style={{ gap: 4 }}>
                    <button className="icon-btn"><Icon name="more" size={15} /></button>
                    <button className="icon-btn" onClick={onClose}><Icon name="x" size={15} /></button>
                </div>
            </div>

            <div style={{ padding: '0 20px', borderBottom: '1px solid var(--line)', background: 'var(--surface)' }}>
                <div className="row" style={{ gap: 4 }}>
                    {[
                        { id: 'overview', label: 'Обзор' },
                        { id: 'history', label: `История · ${ticket.history.length}` },
                        { id: 'photos', label: 'Фото' },
                        { id: 'comments', label: `Комментарии · ${ticket.comments}` },
                    ].map(t => (
                        <button
                            key={t.id}
                            onClick={() => setTab(t.id)}
                            style={{
                                background: 'transparent', border: 0, padding: '12px 4px', marginRight: 14,
                                fontSize: 13, fontWeight: tab === t.id ? 650 : 500,
                                color: tab === t.id ? 'var(--text)' : 'var(--muted)',
                                borderBottom: '2px solid ' + (tab === t.id ? 'var(--primary)' : 'transparent'),
                                cursor: 'pointer',
                            }}>{t.label}</button>
                    ))}
                </div>
            </div>

            <div className="drawer-body">
                {tab === 'overview' && (
                    <>
                        <Card className="card-pad">
                            <div className="grid-2" style={{ gap: 14 }}>
                                <div>
                                    <div className="field-label">Житель</div>
                                    <div className="row" style={{ gap: 8, marginTop: 6 }}>
                                        <Avatar name={ticket.resident} />
                                        <div>
                                            <div style={{ fontWeight: 600 }}>{ticket.resident}</div>
                                            <div className="txt-muted" style={{ fontSize: 11.5 }}>через мобильное приложение</div>
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <div className="field-label">Приоритет</div>
                                    <div style={{ marginTop: 6, fontWeight: 600 }}>
                                        {ticket.priority === 'high' ? 'Высокий' : ticket.priority === 'low' ? 'Низкий' : 'Обычный'}
                                    </div>
                                </div>
                                <div style={{ gridColumn: '1 / -1' }}>
                                    <div className="field-label">Описание</div>
                                    <div style={{ marginTop: 6, lineHeight: 1.55 }}>{ticket.description}</div>
                                </div>
                                <div style={{ gridColumn: '1 / -1' }}>
                                    <div className="field-label">Координаты</div>
                                    <div className="row" style={{ gap: 8, marginTop: 6, fontSize: 12.5, fontVariantNumeric: 'tabular-nums' }}>
                                        <Icon name="pin" size={14} />
                                        <span>{ticket.lat.toFixed(5)}, {ticket.lng.toFixed(5)}</span>
                                        <span className="txt-muted">·</span>
                                        <a style={{ color: 'var(--primary)', fontWeight: 600 }}>построить маршрут</a>
                                    </div>
                                </div>
                            </div>
                        </Card>

                        <Card className="card-pad">
                            <div className="row between" style={{ marginBottom: 12 }}>
                                <h3 className="h-section">Фото до / после</h3>
                                <button className="btn ghost sm"><Icon name="upload" size={13} />Загрузить</button>
                            </div>
                            <div className="grid-2" style={{ gap: 10 }}>
                                <PhotoSlot kind="before" label="фото от жителя" />
                                {ticket.status === 'completed'
                                    ? <PhotoSlot kind="after" label="отчёт исполнителя" />
                                    : <div className="photo-slot after" style={{ background: 'var(--surface-soft)', border: '1px dashed var(--line-strong)', backgroundImage: 'none' }}>
                                          <div className="ps-label" style={{ background: 'transparent', border: 0, boxShadow: 'none', color: 'var(--muted-soft)' }}>будет добавлено<br/>после работ</div>
                                      </div>
                                }
                            </div>
                        </Card>

                        <Card className="card-pad">
                            <h3 className="h-section" style={{ marginBottom: 12 }}>Назначение</h3>
                            <div className="col-12">
                                <div className="field">
                                    <label className="field-label">Исполнитель</label>
                                    <select className="select" value={assignWorker} onChange={e => setAssignWorker(e.target.value)}>
                                        <option value="">Без конкретного сотрудника</option>
                                        {WORKERS.map(w => (
                                            <option key={w.id} value={w.id}>{w.name} · активных {w.active}</option>
                                        ))}
                                    </select>
                                </div>
                                <div className="field">
                                    <label className="field-label">Изменить статус</label>
                                    <select className="select" value={statusVal} onChange={e => setStatusVal(e.target.value)}>
                                        {Object.entries(STATUS).filter(([k]) => k !== 'hidden').map(([k, v]) => (
                                            <option key={k} value={k}>{v.label}</option>
                                        ))}
                                    </select>
                                </div>
                                <div className="field">
                                    <label className="field-label">Комментарий</label>
                                    <textarea className="textarea" placeholder="Необязательно — причина или примечание" />
                                </div>
                            </div>
                        </Card>
                    </>
                )}

                {tab === 'history' && (
                    <Card className="card-pad">
                        <div className="timeline">
                            {ticket.history.map((h, i) => (
                                <div key={i} className="timeline-item">
                                    <div className={`timeline-dot b-${(STATUS[h.to]?.cls || 'pill-gray').replace('pill-', '')}`} />
                                    <div className="timeline-body">
                                        <div className="tl-meta">{h.ts} · {h.who}</div>
                                        <div className="tl-title">
                                            {h.from && <StatusPill status={h.from} />}
                                            {h.from && <span className="txt-muted" style={{ margin: '0 6px' }}>→</span>}
                                            <StatusPill status={h.to} />
                                        </div>
                                        {h.note && <div className="tl-note">{h.note}</div>}
                                    </div>
                                </div>
                            ))}
                        </div>
                    </Card>
                )}

                {tab === 'photos' && (
                    <Card className="card-pad">
                        <div className="grid-2" style={{ gap: 10 }}>
                            <PhotoSlot kind="before" label="общий план" />
                            <PhotoSlot kind="before" label="крупный план" />
                            {ticket.status === 'completed' && <PhotoSlot kind="after" label="после уборки" />}
                            {ticket.status === 'completed' && <PhotoSlot kind="after" label="контейнер" />}
                        </div>
                    </Card>
                )}

                {tab === 'comments' && (
                    <Card className="card-pad">
                        <div className="col-16">
                            <div className="row" style={{ alignItems: 'flex-start', gap: 10 }}>
                                <Avatar name={ticket.resident} />
                                <div style={{ flex: 1 }}>
                                    <div className="row between"><div className="txt-strong" style={{ fontSize: 13 }}>{ticket.resident}</div><div className="txt-muted" style={{ fontSize: 11.5 }}>{ticket.created}</div></div>
                                    <div style={{ fontSize: 13, marginTop: 4, lineHeight: 1.5 }}>Уже неделю не убирают, дети рядом гуляют. Сделайте, пожалуйста.</div>
                                </div>
                            </div>
                            <div className="row" style={{ alignItems: 'flex-start', gap: 10 }}>
                                <Avatar name="Админ ЖКХ" hue={4} />
                                <div style={{ flex: 1 }}>
                                    <div className="row between"><div className="txt-strong" style={{ fontSize: 13 }}>Виктор Лесюк · админ</div><div className="txt-muted" style={{ fontSize: 11.5 }}>сегодня 09:14</div></div>
                                    <div style={{ fontSize: 13, marginTop: 4, lineHeight: 1.5 }}>Принято, отправляем бригаду в течение дня.</div>
                                </div>
                            </div>
                            <div className="divider-soft"></div>
                            <textarea className="textarea" placeholder="Написать комментарий жителю или внутренний для команды…" />
                            <div className="row" style={{ justifyContent: 'flex-end', gap: 8 }}>
                                <button className="btn ghost sm">Внутренняя заметка</button>
                                <button className="btn sm">Ответить жителю</button>
                            </div>
                        </div>
                    </Card>
                )}
            </div>

            <div className="drawer-foot">
                <button className="btn danger-ghost" onClick={() => onConfirm({ ticket, type: 'hide' })}>
                    <Icon name="eyeOff" size={14} />Скрыть
                </button>
                <div style={{ marginLeft: 'auto', display: 'flex', gap: 8 }}>
                    <button className="btn ghost" onClick={onClose}>Закрыть</button>
                    <button className="btn" onClick={() => { onToast({ title: 'Сохранено', body: `Заявка №${ticket.id} обновлена` }); onClose(); }}>
                        <Icon name="check" size={14} />Сохранить
                    </button>
                </div>
            </div>
        </Drawer>
    );
}

Object.assign(window, { TopBar, DashboardScreen, TicketsScreen, TicketDrawer });
