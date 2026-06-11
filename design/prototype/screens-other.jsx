/* global React, L, Icon, StatusPill, Avatar, KPI, Card, CardHead, Search,
   Empty, BarChart, Donut, STATUS, CATEGORIES, ORGS, WORKERS, CODES, TICKETS,
   STATS_ORG, DAILY, CATEGORY_STATS, CLAIMS */
const { useState, useEffect, useMemo, useRef } = React;

// ============================================================
// MAP SCREEN
// ============================================================
function MapScreen({ onOpenTicket }) {
    const mapEl = useRef(null);
    const mapRef = useRef(null);
    const markersRef = useRef([]);
    const [statusFilter, setStatusFilter] = useState('all');
    const [search, setSearch] = useState('');
    const [selectedId, setSelectedId] = useState(null);

    const visible = useMemo(() => TICKETS.filter(t => {
        if (statusFilter === 'all') {
        } else if (statusFilter === 'active') {
            if (!['assigned', 'in_progress'].includes(t.status)) return false;
        } else if (t.status !== statusFilter) return false;
        if (search) {
            const q = search.toLowerCase();
            if (!`${t.id}`.includes(q) && !t.address.toLowerCase().includes(q)) return false;
        }
        return true;
    }), [statusFilter, search]);

    // init leaflet
    useEffect(() => {
        if (!mapEl.current || !window.L) return;
        const map = L.map(mapEl.current, { zoomControl: false, attributionControl: false })
            .setView([53.1327, 26.0139], 13);
        L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}.png', {
            maxZoom: 19,
        }).addTo(map);
        mapRef.current = map;
        setTimeout(() => map.invalidateSize(), 200);
        return () => map.remove();
    }, []);

    // update markers
    useEffect(() => {
        const map = mapRef.current;
        if (!map) return;
        markersRef.current.forEach(m => m.remove());
        markersRef.current = [];
        visible.forEach(t => {
            const html = `<div class="pin ${STATUS[t.status]?.pin || 's-created'}"><div class="body"></div><div class="core"></div></div>`;
            const icon = L.divIcon({ className: '', html, iconSize: [26, 32], iconAnchor: [13, 28], popupAnchor: [0, -24] });
            const m = L.marker([t.lat, t.lng], { icon }).addTo(map);
            m.on('click', () => setSelectedId(t.id));
            m.bindPopup(
                `<div style="font-weight:600;margin-bottom:2px;">№${t.id} · ${t.category.name}</div>` +
                `<div style="color:#687468;margin-bottom:4px;">${t.address}</div>`
            );
            markersRef.current.push(m);
        });
    }, [visible]);

    const selected = selectedId ? TICKETS.find(t => t.id === selectedId) : null;

    const summary = {
        all: TICKETS.length,
        new: TICKETS.filter(t => t.status === 'created').length,
        active: TICKETS.filter(t => ['assigned', 'in_progress'].includes(t.status)).length,
        completed: TICKETS.filter(t => t.status === 'completed').length,
        rejected: TICKETS.filter(t => t.status === 'rejected').length,
    };

    return (
        <div className="page" style={{ paddingBottom: 32 }}>
            <div className="page-head">
                <div className="page-head-left">
                    <div className="crumb"><Icon name="home" size={12} /><span>Главная</span><Icon name="chevR" size={11} /><span>Карта</span></div>
                    <h1 className="h-page">Карта обращений</h1>
                    <p className="page-sub">Барановичи · {visible.length} меток из {TICKETS.length}</p>
                </div>
                <div className="page-actions">
                    <div className="segmented">
                        <button className="active"><Icon name="map" size={13} />Карта</button>
                        <button><Icon name="layers" size={13} />Тепловая</button>
                        <button><Icon name="grid" size={13} />Кластеры</button>
                    </div>
                </div>
            </div>

            {/* Layout: filters strip + map + sidebar */}
            <div className="layout-main-side">
                <Card style={{ overflow: 'hidden' }}>
                    {/* compact filter bar inside map card */}
                    <div className="card-head" style={{ padding: '12px 14px', gap: 10, flexWrap: 'wrap' }}>
                        <div className="segmented">
                            <button className={statusFilter === 'all' ? 'active' : ''} onClick={() => setStatusFilter('all')}>Все <span className="cnt">{summary.all}</span></button>
                            <button className={statusFilter === 'created' ? 'active' : ''} onClick={() => setStatusFilter('created')}>Новые <span className="cnt">{summary.new}</span></button>
                            <button className={statusFilter === 'active' ? 'active' : ''} onClick={() => setStatusFilter('active')}>В работе <span className="cnt">{summary.active}</span></button>
                            <button className={statusFilter === 'completed' ? 'active' : ''} onClick={() => setStatusFilter('completed')}>Выполнено <span className="cnt">{summary.completed}</span></button>
                            <button className={statusFilter === 'rejected' ? 'active' : ''} onClick={() => setStatusFilter('rejected')}>Отклонено <span className="cnt">{summary.rejected}</span></button>
                        </div>
                        <Search value={search} onChange={setSearch} placeholder="№ заявки или адрес" />
                        <select className="select" style={{ width: 180 }}>
                            <option>Все категории</option>
                            {CATEGORIES.map(c => <option key={c.id}>{c.name}</option>)}
                        </select>
                        <div className="row" style={{ gap: 6, marginLeft: 'auto' }}>
                            <button className="btn ghost sm" title="Текущая локация"><Icon name="target" size={14} /></button>
                            <button className="btn ghost sm"><Icon name="refresh" size={14} />Обновить</button>
                        </div>
                    </div>

                    {/* Map */}
                    <div className="map-shell" style={{ height: 620, borderRadius: 0, borderLeft: 0, borderRight: 0, borderBottom: 0 }}>
                        <div ref={mapEl} style={{ position: 'absolute', inset: 0 }} />
                        <div className="map-control-cluster">
                            <button onClick={() => mapRef.current?.zoomIn()}><Icon name="plus" size={16} /></button>
                            <button onClick={() => mapRef.current?.zoomOut()}><Icon name="minus" size={16} /></button>
                            <button onClick={() => mapRef.current?.setView([53.1327, 26.0139], 13)}><Icon name="target" size={16} /></button>
                        </div>

                        {/* legend */}
                        <div style={{
                            position: 'absolute', bottom: 14, left: 14, zIndex: 500,
                            background: 'var(--surface)', border: '1px solid var(--line)', borderRadius: 12,
                            padding: '10px 12px', boxShadow: 'var(--shadow-sm)', display: 'flex', flexDirection: 'column', gap: 6, fontSize: 11.5,
                        }}>
                            <div style={{ fontWeight: 600, marginBottom: 2 }}>Статусы</div>
                            {[
                                ['created', 'Новые'],
                                ['assigned', 'Назначены'],
                                ['in_progress', 'В работе'],
                                ['completed', 'Выполнены'],
                                ['rejected', 'Отклонены'],
                            ].map(([k, label]) => (
                                <div key={k} className="row" style={{ gap: 8 }}>
                                    <span style={{ width: 9, height: 9, borderRadius: '50%', background: STATUS[k].ink, boxShadow: '0 0 0 1.5px var(--surface)' }} />
                                    <span style={{ color: 'var(--muted)' }}>{label}</span>
                                </div>
                            ))}
                        </div>
                    </div>
                </Card>

                {/* Sidebar list / selected ticket */}
                <Card style={{ position: 'sticky', top: 80 }}>
                    {selected ? (
                        <>
                            <div className="card-head">
                                <div className="row" style={{ gap: 10 }}>
                                    <button className="btn ghost sm btn-icon" onClick={() => setSelectedId(null)}><Icon name="chevL" size={14} /></button>
                                    <div>
                                        <div className="card-title">Обращение №{selected.id}</div>
                                        <div className="card-sub">{selected.category.name}</div>
                                    </div>
                                </div>
                                <button className="icon-btn" onClick={() => setSelectedId(null)}><Icon name="x" size={14} /></button>
                            </div>
                            <div className="card-pad" style={{ display: 'flex', flexDirection: 'column', gap: 12 }}>
                                <StatusPill status={selected.status} large />
                                <div>
                                    <div className="field-label">Адрес</div>
                                    <div className="txt-strong" style={{ marginTop: 4 }}>{selected.address}</div>
                                </div>
                                <div>
                                    <div className="field-label">Описание</div>
                                    <div style={{ marginTop: 4, lineHeight: 1.55, fontSize: 13 }}>{selected.description}</div>
                                </div>
                                <PhotoSlot kind="before" label="фото от жителя" />
                                <div className="grid-2" style={{ gap: 12 }}>
                                    <div>
                                        <div className="field-label">Создана</div>
                                        <div className="txt-strong" style={{ marginTop: 4 }}>{selected.created}</div>
                                    </div>
                                    <div>
                                        <div className="field-label">Исполнитель</div>
                                        <div style={{ marginTop: 4 }}>
                                            {selected.worker ? (
                                                <div className="row" style={{ gap: 6 }}>
                                                    <Avatar name={selected.worker.name} />
                                                    <span style={{ fontSize: 12.5 }}>{selected.worker.name}</span>
                                                </div>
                                            ) : <span className="txt-muted">не назначен</span>}
                                        </div>
                                    </div>
                                </div>
                                <div className="row" style={{ gap: 8, marginTop: 4 }}>
                                    <button className="btn ghost sm" style={{ flex: 1 }}><Icon name="route" size={13} />Маршрут</button>
                                    <button className="btn sm" style={{ flex: 1 }} onClick={() => onOpenTicket(selected)}>Открыть карточку</button>
                                </div>
                            </div>
                        </>
                    ) : (
                        <>
                            <div className="card-head">
                                <div>
                                    <div className="card-title">Заявки в области</div>
                                    <div className="card-sub">{visible.length} меток на карте</div>
                                </div>
                                <button className="icon-btn"><Icon name="sliders" size={14} /></button>
                            </div>
                            <div style={{ maxHeight: 600, overflowY: 'auto' }}>
                                {visible.slice(0, 14).map(t => (
                                    <div key={t.id}
                                        onClick={() => { setSelectedId(t.id); mapRef.current?.setView([t.lat, t.lng], 16); }}
                                        style={{
                                            padding: '12px 16px',
                                            borderBottom: '1px solid var(--line-soft)',
                                            cursor: 'pointer',
                                            display: 'flex', gap: 10, alignItems: 'flex-start',
                                        }}>
                                        <span style={{ width: 10, height: 10, borderRadius: '50%', background: STATUS[t.status]?.ink, marginTop: 5, flexShrink: 0 }} />
                                        <div style={{ flex: 1, minWidth: 0 }}>
                                            <div className="row between">
                                                <span className="txt-strong" style={{ fontSize: 13 }}>№{t.id}</span>
                                                <span className="txt-muted" style={{ fontSize: 11.5 }}>{t.created_short}</span>
                                            </div>
                                            <div style={{ fontSize: 12.5, marginTop: 1, overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>{t.address}</div>
                                            <div className="row" style={{ gap: 6, marginTop: 6 }}>
                                                <span className="tag" style={{ height: 18, fontSize: 10.5, padding: '0 6px' }}>{t.category.name}</span>
                                                <StatusPill status={t.status} />
                                            </div>
                                        </div>
                                    </div>
                                ))}
                                {visible.length === 0 && <Empty icon="search" title="Заявок в области не найдено" sub="Уменьшите масштаб карты или сбросьте фильтры." />}
                            </div>
                        </>
                    )}
                </Card>
            </div>
        </div>
    );
}

// ============================================================
// EMPLOYEES SCREEN
// ============================================================
function EmployeesScreen({ onToast, onConfirm }) {
    const [search, setSearch] = useState('');
    const [load, setLoad] = useState('all');
    const [codeSearch, setCodeSearch] = useState('');
    const [codeFilter, setCodeFilter] = useState('all');
    const [copied, setCopied] = useState(null);
    const [issuedTo, setIssuedTo] = useState('');

    const totalWorkers = WORKERS.length;
    const activeTasks = WORKERS.reduce((s, w) => s + w.active, 0);
    const availableCodes = CODES.filter(c => c.state === 'available').length;
    const usedCodes = CODES.filter(c => c.state === 'used').length;

    const filteredWorkers = WORKERS.filter(w => {
        if (search) {
            const q = search.toLowerCase();
            if (!w.name.toLowerCase().includes(q) && !w.email.toLowerCase().includes(q)) return false;
        }
        if (load === 'active' && w.active === 0) return false;
        if (load === 'free' && w.active !== 0) return false;
        return true;
    });

    const filteredCodes = CODES.filter(c => {
        if (codeSearch) {
            const q = codeSearch.toLowerCase();
            if (!c.code.toLowerCase().includes(q) && !(c.issued || '').toLowerCase().includes(q) && !(c.used_by || '').toLowerCase().includes(q)) return false;
        }
        if (codeFilter !== 'all' && c.state !== codeFilter) return false;
        return true;
    });

    function copyCode(code) {
        if (navigator.clipboard) navigator.clipboard.writeText(code);
        setCopied(code);
        setTimeout(() => setCopied(null), 1200);
    }

    function generateCode() {
        const random = () => Math.random().toString(36).toUpperCase().replace(/[^A-Z0-9]/g, '').slice(0, 4);
        const code = `JKH-BR1-${random()}`;
        onToast({ title: 'Ключ сгенерирован', body: `${code} — выдан: ${issuedTo || '—'}` });
        setIssuedTo('');
    }

    return (
        <div className="page">
            <div className="page-head">
                <div className="page-head-left">
                    <div className="crumb"><Icon name="home" size={12} /><span>Главная</span><Icon name="chevR" size={11} /><span>Сотрудники</span></div>
                    <h1 className="h-page">Сотрудники</h1>
                    <p className="page-sub">Исполнители ЖЭС-1 Северный · ключи доступа и нагрузка</p>
                </div>
                <div className="page-actions">
                    <button className="btn ghost"><Icon name="upload" size={14} />Экспорт</button>
                </div>
            </div>

            <div className="kpi-grid" style={{ marginBottom: 18 }}>
                <KPI icon="users" tint="green" label="Сотрудников" value={totalWorkers} hint="в вашем ЖКХ" />
                <KPI icon="activity" tint="orange" label="Активные задачи" value={activeTasks} hint="в работе у исполнителей" trend="+5" />
                <KPI icon="key" tint="blue" label="Доступные ключи" value={availableCodes} hint="можно выдать" />
                <KPI icon="check" tint="gray" label="Использованные" value={usedCodes} hint="в истории" />
            </div>

            {/* New key card — prominent, separate */}
            <Card className="card-pad-lg new-key-card" style={{ marginBottom: 18, background: 'linear-gradient(180deg, var(--primary-soft-2) 0%, var(--surface) 100%)' }}>
                <div className="new-key-grid">
                    <div style={{
                        width: 56, height: 56, borderRadius: 16,
                        background: 'var(--surface)', border: '1px solid var(--line)',
                        display: 'grid', placeItems: 'center', color: 'var(--primary)',
                        boxShadow: 'var(--shadow-sm)',
                    }}>
                        <Icon name="key" size={26} />
                    </div>
                    <div>
                        <h3 style={{ margin: 0, fontSize: 16, fontWeight: 650, letterSpacing: '-0.015em' }}>Новый ключ доступа</h3>
                        <p className="txt-muted" style={{ fontSize: 13, marginTop: 4, maxWidth: 60 + 'ch' }}>
                            Один ключ — один сотрудник. После регистрации в мобильном приложении ключ становится использованным.
                            Формат: <code style={{ background: 'var(--surface)', padding: '1px 5px', borderRadius: 4, border: '1px solid var(--line)', fontSize: 11.5 }}>JKH-XXXX-XXXX</code>
                        </p>
                    </div>
                    <div className="row new-key-actions" style={{ gap: 10 }}>
                        <input className="input" placeholder="Кому выдать (ФИО или бригада)"
                            value={issuedTo} onChange={e => setIssuedTo(e.target.value)}
                            style={{ minWidth: 200, flex: 1, background: 'var(--surface)' }} />
                        <button className="btn" onClick={generateCode}>
                            <Icon name="plus" size={14} />Сгенерировать ключ
                        </button>
                    </div>
                </div>
            </Card>

            {/* Workers */}
            <Card style={{ marginBottom: 18 }}>
                <div className="card-head" style={{ flexWrap: 'wrap', gap: 10 }}>
                    <div>
                        <h3 className="card-title">Исполнители</h3>
                        <div className="card-sub">{filteredWorkers.length} из {WORKERS.length}</div>
                    </div>
                    <div className="row" style={{ gap: 8, marginLeft: 'auto' }}>
                        <div className="segmented">
                            <button className={load === 'all' ? 'active' : ''} onClick={() => setLoad('all')}>Все</button>
                            <button className={load === 'active' ? 'active' : ''} onClick={() => setLoad('active')}>С задачами</button>
                            <button className={load === 'free' ? 'active' : ''} onClick={() => setLoad('free')}>Свободные</button>
                        </div>
                        <Search value={search} onChange={setSearch} placeholder="Имя или email" />
                    </div>
                </div>
                <div className="table-wrap">
                    <table className="tbl">
                        <thead>
                            <tr>
                                <th>Сотрудник</th>
                                <th>Статус</th>
                                <th className="num" style={{ textAlign: 'right' }}>Активные</th>
                                <th className="num" style={{ textAlign: 'right' }}>Назначено</th>
                                <th className="num" style={{ textAlign: 'right' }}>Выполнено</th>
                                <th>Создан</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            {filteredWorkers.map(w => (
                                <tr key={w.id}>
                                    <td>
                                        <div className="row" style={{ gap: 10 }}>
                                            <div style={{ position: 'relative' }}>
                                                <Avatar name={w.name} size="md" />
                                                <span style={{
                                                    position: 'absolute', right: -1, bottom: -1,
                                                    width: 9, height: 9, borderRadius: '50%',
                                                    background: w.online ? 'var(--c-green)' : 'var(--c-gray)',
                                                    boxShadow: '0 0 0 2px var(--surface)'
                                                }} />
                                            </div>
                                            <div>
                                                <div className="txt-strong">{w.name}</div>
                                                <div className="secondary">{w.email}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        {w.active > 0
                                            ? <span className="pill pill-orange">{w.active} в работе</span>
                                            : <span className="pill pill-gray no-dot">свободен</span>}
                                    </td>
                                    <td className="num" style={{ textAlign: 'right' }}>{w.active}</td>
                                    <td className="num" style={{ textAlign: 'right' }}>{w.assigned}</td>
                                    <td className="num" style={{ textAlign: 'right', fontWeight: 600 }}>{w.completed}</td>
                                    <td className="txt-muted">{w.created}</td>
                                    <td className="row-actions">
                                        <button className="btn ghost sm">Профиль</button>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </Card>

            {/* Keys */}
            <Card>
                <div className="card-head" style={{ flexWrap: 'wrap', gap: 10 }}>
                    <div>
                        <h3 className="card-title">Ключи доступа</h3>
                        <div className="card-sub">{filteredCodes.length} из {CODES.length}</div>
                    </div>
                    <div className="row" style={{ gap: 8, marginLeft: 'auto' }}>
                        <div className="segmented">
                            <button className={codeFilter === 'all' ? 'active' : ''} onClick={() => setCodeFilter('all')}>Все</button>
                            <button className={codeFilter === 'available' ? 'active' : ''} onClick={() => setCodeFilter('available')}>Доступные</button>
                            <button className={codeFilter === 'used' ? 'active' : ''} onClick={() => setCodeFilter('used')}>Использованные</button>
                            <button className={codeFilter === 'revoked' ? 'active' : ''} onClick={() => setCodeFilter('revoked')}>Отключённые</button>
                        </div>
                        <Search value={codeSearch} onChange={setCodeSearch} placeholder="Ключ или ФИО" />
                    </div>
                </div>
                <div className="table-wrap">
                    <table className="tbl">
                        <thead>
                            <tr>
                                <th>Ключ</th>
                                <th>Выдан</th>
                                <th>Статус</th>
                                <th>Использовал</th>
                                <th>Дата</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            {filteredCodes.map(c => (
                                <tr key={c.code}>
                                    <td>
                                        <span style={{
                                            display: 'inline-flex', alignItems: 'center', gap: 8,
                                            fontFamily: 'var(--mono)', fontSize: 12,
                                            background: 'var(--surface-soft)', border: '1px solid var(--line)',
                                            padding: '3px 8px', borderRadius: 8, fontWeight: 600, letterSpacing: '0.02em',
                                        }}>
                                            {c.code}
                                        </span>
                                    </td>
                                    <td>{c.issued || '—'}</td>
                                    <td>
                                        {c.state === 'available' && <span className="pill pill-green">Доступен</span>}
                                        {c.state === 'used' && <span className="pill pill-gray no-dot">Использован</span>}
                                        {c.state === 'revoked' && <span className="pill pill-red">Отключён</span>}
                                    </td>
                                    <td>{c.used_by ? (
                                        <div className="row" style={{ gap: 6 }}>
                                            <Avatar name={c.used_by} />
                                            <span style={{ fontSize: 12.5 }}>{c.used_by}</span>
                                        </div>
                                    ) : <span className="txt-muted">—</span>}</td>
                                    <td className="txt-muted">{c.date}</td>
                                    <td className="row-actions">
                                        <button className="btn ghost sm" onClick={() => copyCode(c.code)}>
                                            <Icon name={copied === c.code ? 'check' : 'copy'} size={13} />
                                            {copied === c.code ? 'Скопирован' : 'Копия'}
                                        </button>
                                        {c.state === 'available' && (
                                            <button className="btn ghost sm">Отключить</button>
                                        )}
                                        {c.state !== 'used' && (
                                            <button className="btn danger-ghost sm btn-icon" onClick={() => onConfirm({ type: 'delete_code', code: c.code })}>
                                                <Icon name="trash" size={13} />
                                            </button>
                                        )}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </Card>
        </div>
    );
}

// ============================================================
// REPORT SCREEN
// ============================================================
function ReportScreen({ onToast }) {
    const [period, setPeriod] = useState('week');
    const [dateFrom, setDateFrom] = useState('2026-05-20');
    const [dateTo, setDateTo] = useState('2026-05-27');
    const [cat, setCat] = useState('');
    const [worker, setWorker] = useState('');
    const [generated, setGenerated] = useState(true);

    const summary = {
        total: 109, created: 23, active: 22, completed: 58, rejected: 6,
        avg_time: '4ч 18м', sla: 92,
    };
    const periodLabel = period === 'today' ? 'Сегодня' : period === 'week' ? 'Неделя' : period === 'month' ? 'Месяц' : period === 'quarter' ? 'Квартал' : 'Произвольный';

    return (
        <div className="page">
            <div className="page-head">
                <div className="page-head-left">
                    <div className="crumb"><Icon name="home" size={12} /><span>Главная</span><Icon name="chevR" size={11} /><span>Отчёт</span></div>
                    <h1 className="h-page">Отчёт</h1>
                    <p className="page-sub">Период · {dateFrom.split('-').reverse().join('.')} — {dateTo.split('-').reverse().join('.')} · ЖЭС-1 Северный</p>
                </div>
                <div className="page-actions">
                    <button className="btn ghost" onClick={() => onToast({ title: 'PDF готовится', body: 'Файл будет в загрузках' })}>
                        <Icon name="pdf" size={14} />PDF
                    </button>
                    <button className="btn ghost"><Icon name="download" size={14} />CSV</button>
                </div>
            </div>

            <div className="layout-main-side-340">
                {/* Report sheet */}
                <div className="col-16">
                    {/* hero summary */}
                    <Card style={{ overflow: 'hidden' }}>
                        <div style={{
                            padding: 22,
                            background: 'linear-gradient(135deg, var(--primary-soft) 0%, var(--surface) 70%)',
                            borderBottom: '1px solid var(--line)',
                        }}>
                            <div className="row between" style={{ alignItems: 'flex-start' }}>
                                <div>
                                    <span className="pill pill-green">Отчёт сформирован за {periodLabel.toLowerCase()}</span>
                                    <h2 style={{ margin: '10px 0 4px', fontSize: 22, fontWeight: 700, letterSpacing: '-0.025em' }}>
                                        Отчёт по обращениям
                                    </h2>
                                    <div className="txt-muted" style={{ fontSize: 13 }}>ЖЭС-1 Северный, Барановичи · {dateFrom.split('-').reverse().join('.')} — {dateTo.split('-').reverse().join('.')}</div>
                                </div>
                                <div className="row" style={{ gap: 16, fontSize: 12.5 }}>
                                    <div>
                                        <div className="txt-muted">Сформирован</div>
                                        <div className="txt-strong">27.05.2026 10:24</div>
                                    </div>
                                    <div>
                                        <div className="txt-muted">SLA</div>
                                        <div className="txt-strong">{summary.sla}%</div>
                                    </div>
                                    <div>
                                        <div className="txt-muted">Среднее время</div>
                                        <div className="txt-strong">{summary.avg_time}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        {/* summary tiles */}
                        <div className="report-metrics-grid" style={{ display: 'grid', gridTemplateColumns: 'repeat(5, 1fr)' }}>
                            {[
                                { label: 'Всего',     value: summary.total,     hint: 'обращений за период', color: 'var(--text)' },
                                { label: 'Новые',     value: summary.created,   hint: 'поступило',           color: 'var(--c-blue)' },
                                { label: 'В работе',  value: summary.active,    hint: 'обрабатываются',      color: 'var(--c-orange)' },
                                { label: 'Выполнено', value: summary.completed, hint: 'закрыто',             color: 'var(--c-green)' },
                                { label: 'Отклонено', value: summary.rejected,  hint: 'не приняты',          color: 'var(--c-red)' },
                            ].map((m, i) => (
                                <div key={i} style={{
                                    padding: 18,
                                    borderRight: i < 4 ? '1px solid var(--line)' : 'none',
                                }}>
                                    <div className="row" style={{ gap: 8 }}>
                                        <span style={{ width: 8, height: 8, borderRadius: 2, background: m.color }} />
                                        <span className="field-label" style={{ letterSpacing: '0.03em' }}>{m.label}</span>
                                    </div>
                                    <div style={{ fontSize: 28, fontWeight: 700, letterSpacing: '-0.04em', marginTop: 6, fontVariantNumeric: 'tabular-nums' }}>{m.value}</div>
                                    <div className="txt-muted" style={{ fontSize: 12 }}>{m.hint}</div>
                                </div>
                            ))}
                        </div>
                    </Card>

                    {/* dynamics chart */}
                    <Card className="card-pad">
                        <div className="row between" style={{ marginBottom: 14 }}>
                            <div>
                                <h3 className="card-title">Динамика по дням</h3>
                                <div className="card-sub">сколько обращений поступало в выбранный период</div>
                            </div>
                            <div className="segmented">
                                <button className="active">Поступление</button>
                                <button>Закрытие</button>
                            </div>
                        </div>
                        <BarChart data={DAILY} height={200} />
                    </Card>

                    {/* Categories table */}
                    <Card>
                        <CardHead title="Категории" sub="распределение обращений по типам" />
                        <div className="table-wrap">
                            <table className="tbl">
                                <thead>
                                    <tr>
                                        <th>Категория</th>
                                        <th className="num" style={{ textAlign: 'right' }}>Всего</th>
                                        <th className="num" style={{ textAlign: 'right' }}>В работе</th>
                                        <th className="num" style={{ textAlign: 'right' }}>Выполнено</th>
                                        <th className="num" style={{ textAlign: 'right' }}>Отклонено</th>
                                        <th style={{ width: '24%' }}>Доля</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {CATEGORY_STATS.map(c => {
                                        const max = Math.max(...CATEGORY_STATS.map(x => x.total));
                                        return (
                                            <tr key={c.name} style={{ cursor: 'default' }}>
                                                <td><span className="txt-strong">{c.name}</span></td>
                                                <td className="num" style={{ textAlign: 'right' }}>{c.total}</td>
                                                <td className="num" style={{ textAlign: 'right' }}>{c.in_work}</td>
                                                <td className="num" style={{ textAlign: 'right', color: 'var(--primary)', fontWeight: 600 }}>{c.completed}</td>
                                                <td className="num" style={{ textAlign: 'right' }}>{c.rejected}</td>
                                                <td>
                                                    <div className="bar-track"><div className="bar-fill" style={{ width: c.total / max * 100 + '%' }} /></div>
                                                </td>
                                            </tr>
                                        );
                                    })}
                                </tbody>
                            </table>
                        </div>
                    </Card>

                    {/* Workers table */}
                    <Card>
                        <CardHead title="Сотрудники" sub="нагрузка и результативность" />
                        <div className="table-wrap">
                            <table className="tbl">
                                <thead>
                                    <tr>
                                        <th>Сотрудник</th>
                                        <th className="num" style={{ textAlign: 'right' }}>Назначено</th>
                                        <th className="num" style={{ textAlign: 'right' }}>В работе</th>
                                        <th className="num" style={{ textAlign: 'right' }}>Выполнено</th>
                                        <th style={{ width: '24%' }}>Доля выполнения</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {WORKERS.slice(0, 6).map(w => {
                                        const pct = Math.round(w.completed / (w.completed + w.active + 1) * 100);
                                        return (
                                            <tr key={w.id} style={{ cursor: 'default' }}>
                                                <td>
                                                    <div className="row" style={{ gap: 10 }}>
                                                        <Avatar name={w.name} size="md" />
                                                        <div>
                                                            <div className="txt-strong">{w.name}</div>
                                                            <div className="secondary">{w.email}</div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td className="num" style={{ textAlign: 'right' }}>{w.assigned}</td>
                                                <td className="num" style={{ textAlign: 'right' }}>{w.active}</td>
                                                <td className="num" style={{ textAlign: 'right', fontWeight: 600 }}>{w.completed}</td>
                                                <td>
                                                    <div className="row" style={{ gap: 10 }}>
                                                        <div className="bar-track" style={{ flex: 1 }}><div className="bar-fill" style={{ width: pct + '%' }} /></div>
                                                        <span className="num" style={{ fontSize: 11.5, color: 'var(--muted)', minWidth: 30, textAlign: 'right' }}>{pct}%</span>
                                                    </div>
                                                </td>
                                            </tr>
                                        );
                                    })}
                                </tbody>
                            </table>
                        </div>
                    </Card>
                </div>

                {/* Right: parameters */}
                <div className="col-16" style={{ position: 'sticky', top: 80 }}>
                    <Card className="card-pad-lg">
                        <div className="row between" style={{ marginBottom: 14 }}>
                            <h3 className="card-title">Параметры отчёта</h3>
                            <button className="icon-btn"><Icon name="refresh" size={14} /></button>
                        </div>

                        <div className="col-12">
                            <div className="field">
                                <label className="field-label">Период</label>
                                <div className="segmented" style={{ width: '100%', display: 'grid', gridTemplateColumns: 'repeat(4, 1fr)' }}>
                                    {[['today','Сегодня'], ['week','Неделя'], ['month','Месяц'], ['quarter','Квартал']].map(([k, label]) => (
                                        <button key={k} className={period === k ? 'active' : ''} onClick={() => setPeriod(k)}>{label}</button>
                                    ))}
                                </div>
                            </div>

                            <div className="grid-2" style={{ gap: 10 }}>
                                <div className="field">
                                    <label className="field-label">Дата от</label>
                                    <input className="input" type="date" value={dateFrom} onChange={e => { setDateFrom(e.target.value); setPeriod('custom'); }} />
                                </div>
                                <div className="field">
                                    <label className="field-label">Дата до</label>
                                    <input className="input" type="date" value={dateTo} onChange={e => { setDateTo(e.target.value); setPeriod('custom'); }} />
                                </div>
                            </div>

                            <div className="field">
                                <label className="field-label">Категория</label>
                                <select className="select" value={cat} onChange={e => setCat(e.target.value)}>
                                    <option value="">Все категории</option>
                                    {CATEGORIES.map(c => <option key={c.id} value={c.id}>{c.name}</option>)}
                                </select>
                            </div>

                            <div className="field">
                                <label className="field-label">Исполнитель</label>
                                <select className="select" value={worker} onChange={e => setWorker(e.target.value)}>
                                    <option value="">Все</option>
                                    {WORKERS.map(w => <option key={w.id} value={w.id}>{w.name}</option>)}
                                </select>
                            </div>

                            <div className="field">
                                <label className="field-label">Включить</label>
                                <div className="col" style={{ gap: 8, fontSize: 13 }}>
                                    <label className="row" style={{ gap: 8 }}><input type="checkbox" defaultChecked /> Графики динамики</label>
                                    <label className="row" style={{ gap: 8 }}><input type="checkbox" defaultChecked /> Таблица категорий</label>
                                    <label className="row" style={{ gap: 8 }}><input type="checkbox" defaultChecked /> Сотрудники</label>
                                    <label className="row" style={{ gap: 8 }}><input type="checkbox" /> Скрытые заявки</label>
                                </div>
                            </div>

                            <div className="divider-soft"></div>

                            <button className="btn lg" onClick={() => { setGenerated(true); onToast({ title: 'Отчёт сформирован', body: `${summary.total} заявок за ${periodLabel.toLowerCase()}` }); }}>
                                <Icon name="chart" size={14} />Сформировать отчёт
                            </button>
                            <div className="row" style={{ gap: 8 }}>
                                <button className="btn ghost" style={{ flex: 1 }} onClick={() => onToast({ title: 'PDF в загрузках' })}><Icon name="pdf" size={14} />PDF</button>
                                <button className="btn ghost" style={{ flex: 1 }}><Icon name="download" size={14} />CSV</button>
                            </div>

                            <div className="txt-muted" style={{ fontSize: 11.5, lineHeight: 1.5, marginTop: 4 }}>
                                Отчёт строится только по вашему ЖКХ. Скрытые заявки и удалённые записи не учитываются.
                            </div>
                        </div>
                    </Card>
                </div>
            </div>
        </div>
    );
}

// ============================================================
// CLAIMS (запросы исполнителей на заявку)
// ============================================================
function ClaimsScreen({ onToast, onOpenTicket }) {
    const [tab, setTab] = useState('pending');
    return (
        <div className="page">
            <div className="page-head">
                <div className="page-head-left">
                    <div className="crumb"><Icon name="home" size={12} /><span>Главная</span><Icon name="chevR" size={11} /><span>Запросы</span></div>
                    <h1 className="h-page">Запросы на заявки</h1>
                    <p className="page-sub">Исполнители просят принять заявку в работу — подтвердите или отклоните</p>
                </div>
            </div>

            <Card>
                <div className="card-head" style={{ flexWrap: 'wrap' }}>
                    <div className="segmented">
                        <button className={tab === 'pending' ? 'active' : ''} onClick={() => setTab('pending')}>Ожидают <span className="cnt">{CLAIMS.length}</span></button>
                        <button className={tab === 'approved' ? 'active' : ''} onClick={() => setTab('approved')}>Подтверждённые</button>
                        <button className={tab === 'rejected' ? 'active' : ''} onClick={() => setTab('rejected')}>Отклонённые</button>
                    </div>
                </div>
                <div>
                    {CLAIMS.map(c => (
                        <div key={c.id} className="claim-item">
                            <div style={{ minWidth: 0 }}>
                                <div className="row" style={{ gap: 10, marginBottom: 6 }}>
                                    <Avatar name={c.worker.name} size="md" />
                                    <div>
                                        <div className="txt-strong">{c.worker.name}</div>
                                        <div className="txt-muted" style={{ fontSize: 12 }}>{c.worker.email} · {c.created}</div>
                                    </div>
                                </div>
                                <div style={{ fontSize: 13.5, lineHeight: 1.55, marginTop: 6 }}>
                                    Хочет принять <a style={{ color: 'var(--primary)', fontWeight: 600 }} onClick={() => onOpenTicket(c.ticket)}>заявку №{c.ticket.id}</a> — {c.ticket.address}
                                </div>
                                {c.note && (
                                    <div style={{
                                        marginTop: 8, padding: '10px 12px',
                                        background: 'var(--surface-soft)', borderRadius: 10,
                                        fontSize: 12.5, color: 'var(--text-soft)', borderLeft: '3px solid var(--primary)',
                                    }}>«{c.note}»</div>
                                )}
                                <div className="row" style={{ gap: 6, marginTop: 10 }}>
                                    <span className="tag">{c.ticket.category.name}</span>
                                    <StatusPill status={c.ticket.status} />
                                </div>
                            </div>
                            <div className="claim-actions">
                                <button className="btn ghost" onClick={() => onToast({ title: 'Запрос отклонён', body: `${c.worker.name} · №${c.ticket.id}` })}>
                                    <Icon name="x" size={14} />Отклонить
                                </button>
                                <button className="btn" onClick={() => onToast({ title: 'Заявка передана', body: `№${c.ticket.id} → ${c.worker.name}` })}>
                                    <Icon name="check" size={14} />Подтвердить
                                </button>
                            </div>
                        </div>
                    ))}
                </div>
            </Card>
        </div>
    );
}

// ============================================================
// SUPER ADMIN dashboard (compact)
// ============================================================
function SuperAdminScreen({ onNav }) {
    const banned = [
        { name: 'Михаил Г.', reason: 'Спам — 47 фальшивых заявок' },
        { name: 'Антон Р.',  reason: 'Угрозы в комментариях' },
    ];
    return (
        <div className="page">
            <div className="page-head">
                <div className="page-head-left">
                    <div className="crumb"><Icon name="home" size={12} /><span>Главная</span><Icon name="chevR" size={11} /><span>Контроль</span></div>
                    <h1 className="h-page">Контроль платформы</h1>
                    <p className="page-sub">Пользователи, блокировки и фиксированный список ЖКХ Барановичей</p>
                </div>
                <div className="page-actions">
                    <button className="btn" onClick={() => onNav('users')}><Icon name="users" size={14} />Пользователи</button>
                </div>
            </div>

            <div className="kpi-grid" style={{ marginBottom: 18 }}>
                <KPI icon="users" tint="green" label="Пользователи" value="2 847" hint="все аккаунты" trend="+38" />
                <KPI icon="alert" tint="red" label="Забанены" value="12" hint="глобальная блокировка" />
                <KPI icon="building" tint="blue" label="ЖКХ" value="5" hint="фиксированный справочник" />
                <KPI icon="user" tint="amber" label="Админы ЖКХ" value="7" hint="ответственные организации" />
            </div>

            <div className="grid-12-4">
                <Card>
                    <CardHead title="Фиксированные ЖКХ Барановичей" sub="Справочник не создаётся вручную. Активные организации уже занесены в систему." actions={<button className="btn ghost sm">Открыть</button>} />
                    <div className="table-wrap">
                        <table className="tbl">
                            <thead>
                                <tr>
                                    <th>Организация</th>
                                    <th className="num" style={{ textAlign: 'right' }}>Админов</th>
                                    <th className="num" style={{ textAlign: 'right' }}>Сотрудников</th>
                                    <th className="num" style={{ textAlign: 'right' }}>Заявок</th>
                                </tr>
                            </thead>
                            <tbody>
                                {ORGS.map(o => (
                                    <tr key={o.id} style={{ cursor: 'default' }}>
                                        <td>
                                            <div className="txt-strong">{o.name}</div>
                                            <div className="secondary">{o.address}</div>
                                        </td>
                                        <td className="num" style={{ textAlign: 'right' }}>{o.admins}</td>
                                        <td className="num" style={{ textAlign: 'right' }}>{o.workers}</td>
                                        <td className="num" style={{ textAlign: 'right', fontWeight: 600 }}>{o.tickets}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </Card>

                <Card>
                    <CardHead icon="alert" title="Активные блокировки" sub={`${banned.length} аккаунта заблокированы`} />
                    <div>
                        {banned.map((b, i) => (
                            <div key={i} style={{ padding: '14px 20px', borderBottom: '1px solid var(--line-soft)', display: 'grid', gridTemplateColumns: '1fr auto', gap: 12, alignItems: 'center' }}>
                                <div className="row" style={{ gap: 10, minWidth: 0 }}>
                                    <Avatar name={b.name} size="md" hue={5} />
                                    <div style={{ minWidth: 0 }}>
                                        <div className="txt-strong">{b.name}</div>
                                        <div className="secondary" style={{ overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>{b.reason}</div>
                                    </div>
                                </div>
                                <button className="btn ghost sm">Карточка</button>
                            </div>
                        ))}
                    </div>
                </Card>
            </div>
        </div>
    );
}

Object.assign(window, { MapScreen, EmployeesScreen, ReportScreen, ClaimsScreen, SuperAdminScreen });
