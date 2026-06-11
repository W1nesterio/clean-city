/* global React */
const { useState, useEffect, useMemo, useRef, useCallback } = React;

// ============== Status config ==============
const STATUS = {
    created:     { label: 'Новая',       cls: 'pill-blue',   pin: 's-created',     ink: 'var(--c-blue)' },
    moderation:  { label: 'На проверке', cls: 'pill-blue',   pin: 's-created',     ink: 'var(--c-blue)' },
    assigned:    { label: 'Назначена',   cls: 'pill-amber',  pin: 's-assigned',    ink: 'var(--c-amber)' },
    accepted:    { label: 'Принята',     cls: 'pill-amber',  pin: 's-assigned',    ink: 'var(--c-amber)' },
    in_progress: { label: 'В работе',    cls: 'pill-orange', pin: 's-in_progress', ink: 'var(--c-orange)' },
    problem:     { label: 'Проблема',    cls: 'pill-orange', pin: 's-in_progress', ink: 'var(--c-orange)' },
    postponed:   { label: 'Отложена',    cls: 'pill-orange', pin: 's-in_progress', ink: 'var(--c-orange)' },
    completed:   { label: 'Выполнена',   cls: 'pill-green',  pin: 's-completed',   ink: 'var(--c-green)' },
    rejected:    { label: 'Отклонена',   cls: 'pill-red',    pin: 's-rejected',    ink: 'var(--c-red)' },
    duplicate:   { label: 'Дубликат',    cls: 'pill-gray',   pin: 's-duplicate',   ink: 'var(--c-gray)' },
    hidden:      { label: 'Скрыта',      cls: 'pill-gray',   pin: 's-duplicate',   ink: 'var(--c-gray)' },
};

// ============== Icons (stroke, 1.6) ==============
const Icon = ({ name, size = 16, className = '', style }) => {
    const s = { width: size, height: size, ...(style || {}) };
    const c = `icon icon-${name} ${className}`;
    const path = ICONS[name];
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.7" strokeLinecap="round" strokeLinejoin="round" style={s} className={c} aria-hidden="true">
            {path}
        </svg>
    );
};
const ICONS = {
    home: <><path d="M3 11l9-7 9 7"/><path d="M5 10v9a1 1 0 0 0 1 1h4v-6h4v6h4a1 1 0 0 0 1-1v-9"/></>,
    list: <><line x1="8" y1="6" x2="20" y2="6"/><line x1="8" y1="12" x2="20" y2="12"/><line x1="8" y1="18" x2="20" y2="18"/><circle cx="4" cy="6" r="1"/><circle cx="4" cy="12" r="1"/><circle cx="4" cy="18" r="1"/></>,
    map: <><polygon points="3 6 9 4 15 6 21 4 21 18 15 20 9 18 3 20 3 6"/><line x1="9" y1="4" x2="9" y2="18"/><line x1="15" y1="6" x2="15" y2="20"/></>,
    inbox: <><path d="M22 12h-6l-2 3h-4l-2-3H2"/><path d="M5.45 5.11L2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z"/></>,
    users: <><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></>,
    chart: <><path d="M3 3v18h18"/><path d="M7 15l4-4 3 3 5-6"/></>,
    building: <><rect x="4" y="3" width="16" height="18" rx="2"/><line x1="9" y1="9" x2="9" y2="9"/><line x1="9" y1="13" x2="9" y2="13"/><line x1="9" y1="17" x2="9" y2="17"/><line x1="15" y1="9" x2="15" y2="9"/><line x1="15" y1="13" x2="15" y2="13"/><line x1="15" y1="17" x2="15" y2="17"/></>,
    search: <><circle cx="11" cy="11" r="7"/><line x1="21" y1="21" x2="16.5" y2="16.5"/></>,
    bell: <><path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.7 21a2 2 0 0 1-3.4 0"/></>,
    moon: <path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"/>,
    sun: <><circle cx="12" cy="12" r="4"/><line x1="12" y1="2" x2="12" y2="4"/><line x1="12" y1="20" x2="12" y2="22"/><line x1="4.93" y1="4.93" x2="6.34" y2="6.34"/><line x1="17.66" y1="17.66" x2="19.07" y2="19.07"/><line x1="2" y1="12" x2="4" y2="12"/><line x1="20" y1="12" x2="22" y2="12"/><line x1="4.93" y1="19.07" x2="6.34" y2="17.66"/><line x1="17.66" y1="6.34" x2="19.07" y2="4.93"/></>,
    chev: <polyline points="6 9 12 15 18 9"/>,
    chevR: <polyline points="9 18 15 12 9 6"/>,
    chevL: <polyline points="15 18 9 12 15 6"/>,
    x: <><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></>,
    plus: <><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></>,
    minus: <line x1="5" y1="12" x2="19" y2="12"/>,
    check: <polyline points="20 6 9 17 4 12"/>,
    filter: <polygon points="22 3 2 3 10 12.5 10 19 14 21 14 12.5 22 3"/>,
    download: <><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></>,
    pdf: <><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></>,
    image: <><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></>,
    pin: <><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 1 1 18 0z"/><circle cx="12" cy="10" r="3"/></>,
    clock: <><circle cx="12" cy="12" r="9"/><polyline points="12 7 12 12 15 14"/></>,
    trash: <><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2"/></>,
    user: <><circle cx="12" cy="8" r="4"/><path d="M4 21v-1a6 6 0 0 1 6-6h4a6 6 0 0 1 6 6v1"/></>,
    key: <><circle cx="8" cy="15" r="4"/><path d="M10.85 12.15L19 4"/><path d="M18 5l3 3"/><path d="M15 8l3 3"/></>,
    copy: <><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></>,
    edit: <><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 1 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></>,
    more: <><circle cx="12" cy="5" r="1.4"/><circle cx="12" cy="12" r="1.4"/><circle cx="12" cy="19" r="1.4"/></>,
    refresh: <><polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/><path d="M3.5 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.65 4.36A9 9 0 0 0 20.5 15"/></>,
    arrowUp: <><line x1="12" y1="19" x2="12" y2="5"/><polyline points="5 12 12 5 19 12"/></>,
    arrowDown: <><line x1="12" y1="5" x2="12" y2="19"/><polyline points="19 12 12 19 5 12"/></>,
    flag: <><path d="M4 22V4a1 1 0 0 1 1-1h12l-2 4 2 4H5"/><line x1="4" y1="14" x2="4" y2="22"/></>,
    alert: <><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></>,
    info: <><circle cx="12" cy="12" r="9"/><line x1="12" y1="8" x2="12" y2="13"/><line x1="12" y1="16" x2="12.01" y2="16"/></>,
    layers: <><polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/></>,
    target: <><circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="5"/><circle cx="12" cy="12" r="1.5"/></>,
    cal: <><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></>,
    settings: <><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.6 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></>,
    chat: <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/>,
    eye: <><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></>,
    eyeOff: <><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></>,
    arrow_right: <><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></>,
    arrow_left: <><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 19"/></>,
    file: <><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></>,
    activity: <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>,
    route: <><circle cx="6" cy="19" r="3"/><path d="M9 19h8.5a3.5 3.5 0 0 0 0-7h-11a3.5 3.5 0 0 1 0-7H15"/><circle cx="18" cy="5" r="3"/></>,
    book: <><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></>,
    trend_up: <><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></>,
    grid: <><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></>,
    sliders: <><line x1="4" y1="21" x2="4" y2="14"/><line x1="4" y1="10" x2="4" y2="3"/><line x1="12" y1="21" x2="12" y2="12"/><line x1="12" y1="8" x2="12" y2="3"/><line x1="20" y1="21" x2="20" y2="16"/><line x1="20" y1="12" x2="20" y2="3"/><line x1="1" y1="14" x2="7" y2="14"/><line x1="9" y1="8" x2="15" y2="8"/><line x1="17" y1="16" x2="23" y2="16"/></>,
    upload: <><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></>,
};

// ============== Status pill ==============
const StatusPill = ({ status, large, noDot }) => {
    const s = STATUS[status] || { label: status, cls: 'pill-gray' };
    return <span className={`pill ${s.cls} ${large ? 'pill-lg' : ''} ${noDot ? 'no-dot' : ''}`}>{s.label}</span>;
};

// ============== Avatar ==============
const Avatar = ({ name = '', size = 'sm', hue }) => {
    const initials = name.split(' ').slice(0, 2).map(w => w[0] || '').join('').toUpperCase();
    const h = hue ?? ((name.charCodeAt(0) + (name.charCodeAt(1) || 0)) % 7 + 1);
    const cls = size === 'lg' ? 'lg' : (size === 'md' ? '' : 'sm');
    return <div className={`avatar ${cls} h${h}`}>{initials || '·'}</div>;
};

// ============== Pill input field ==============
const InlineField = ({ label, value, onClear }) => (
    <span className="field-inline">
        <span className="txt-muted">{label}</span>
        <strong>{value}</strong>
        {onClear && <button className="x" onClick={onClear} aria-label="убрать"><Icon name="x" size={12} /></button>}
    </span>
);

// ============== Search input ==============
const Search = ({ value, onChange, placeholder = 'Поиск', kbd, full }) => (
    <div className="search" style={full ? { flex: 1 } : { width: 280 }}>
        <Icon name="search" size={15} />
        <input className="input" value={value} onChange={e => onChange(e.target.value)} placeholder={placeholder} />
        {kbd && <span className="search-kbd">{kbd}</span>}
    </div>
);

// ============== KPI card ==============
const KPI = ({ icon, label, value, hint, trend, trendDir = 'up', tint }) => (
    <div className={`kpi tint ${tint ? 'tint-' + tint : ''}`}>
        <div className="kpi-head">
            <div className="kpi-icon"><Icon name={icon} size={14} /></div>
            <div className="kpi-label">{label}</div>
        </div>
        <div className="kpi-value">{value}</div>
        <div className="kpi-foot">
            <span className="kpi-hint">{hint}</span>
            {trend && (
                <span className={`kpi-trend ${trendDir}`}>
                    <Icon name={trendDir === 'down' ? 'arrowDown' : 'arrowUp'} size={11} />
                    {trend}
                </span>
            )}
        </div>
    </div>
);

// ============== Card primitives ==============
const Card = ({ children, className = '', style }) => (
    <div className={`card ${className}`} style={style}>{children}</div>
);

const CardHead = ({ title, sub, actions, icon }) => (
    <div className="card-head">
        <div className="row" style={{ gap: 12, minWidth: 0 }}>
            {icon && <div className="kpi-icon" style={{ background: 'var(--surface-soft)' }}><Icon name={icon} size={14} /></div>}
            <div style={{ minWidth: 0 }}>
                <h3 className="card-title">{title}</h3>
                {sub && <div className="card-sub">{sub}</div>}
            </div>
        </div>
        {actions && <div className="row" style={{ gap: 6 }}>{actions}</div>}
    </div>
);

// ============== Drawer ==============
const Drawer = ({ open, onClose, children }) => {
    useEffect(() => {
        if (!open) return;
        const onKey = e => { if (e.key === 'Escape') onClose(); };
        document.addEventListener('keydown', onKey);
        return () => document.removeEventListener('keydown', onKey);
    }, [open, onClose]);
    if (!open) return null;
    return (
        <>
            <div className="drawer-overlay" onClick={onClose} />
            <div className="drawer" role="dialog" aria-modal="true">{children}</div>
        </>
    );
};

// ============== Modal ==============
const Modal = ({ open, onClose, children }) => {
    useEffect(() => {
        if (!open) return;
        const onKey = e => { if (e.key === 'Escape') onClose(); };
        document.addEventListener('keydown', onKey);
        return () => document.removeEventListener('keydown', onKey);
    }, [open, onClose]);
    if (!open) return null;
    return (
        <div className="modal-overlay" onClick={onClose}>
            <div className="modal" role="dialog" aria-modal="true" onClick={e => e.stopPropagation()}>{children}</div>
        </div>
    );
};

// ============== Toast ==============
const Toast = ({ tone, icon, title, body, onClose }) => (
    <div className={`toast ${tone || ''}`}>
        <div className="t-icon"><Icon name={icon || 'check'} size={13} /></div>
        <div style={{ minWidth: 0, flex: 1 }}>
            <div className="t-title">{title}</div>
            {body && <div className="t-body">{body}</div>}
        </div>
        <button className="t-close" onClick={onClose}><Icon name="x" size={14} /></button>
    </div>
);

// ============== Empty state ==============
const Empty = ({ icon = 'inbox', title, sub, action }) => (
    <div className="empty">
        <div className="empty-mark"><Icon name={icon} size={22} /></div>
        <div className="empty-title">{title}</div>
        {sub && <div className="empty-sub">{sub}</div>}
        {action}
    </div>
);

// ============== Photo slot placeholder ==============
const PhotoSlot = ({ kind = 'before', label }) => (
    <div className={`photo-slot ${kind}`}>
        <div className="ps-label">{label || 'фото обращения'}</div>
    </div>
);

// ============== Mini sparkline chart ==============
const Spark = ({ data, width = 320, height = 80, fill = 'var(--primary)', area = true, baseline = 0 }) => {
    if (!data || !data.length) return null;
    const max = Math.max(...data, 1);
    const min = Math.min(0, baseline);
    const w = width, h = height;
    const step = w / (data.length - 1 || 1);
    const points = data.map((v, i) => {
        const x = i * step;
        const y = h - 6 - ((v - min) / (max - min || 1)) * (h - 12);
        return [x, y];
    });
    const path = points.map((p, i) => `${i === 0 ? 'M' : 'L'} ${p[0].toFixed(1)} ${p[1].toFixed(1)}`).join(' ');
    const areaPath = `${path} L ${w} ${h} L 0 ${h} Z`;
    return (
        <svg viewBox={`0 0 ${w} ${h}`} width="100%" height={h} preserveAspectRatio="none" style={{ display: 'block' }}>
            {area && (
                <>
                    <defs>
                        <linearGradient id="spgrad" x1="0" x2="0" y1="0" y2="1">
                            <stop offset="0%" stopColor={fill} stopOpacity="0.22" />
                            <stop offset="100%" stopColor={fill} stopOpacity="0" />
                        </linearGradient>
                    </defs>
                    <path d={areaPath} fill="url(#spgrad)" />
                </>
            )}
            <path d={path} stroke={fill} strokeWidth="2" fill="none" strokeLinecap="round" strokeLinejoin="round" />
        </svg>
    );
};

// ============== Bar chart (vertical bars per day) ==============
const BarChart = ({ data, height = 180, labels = [] }) => {
    const max = Math.max(...data.map(d => d.total || 0), 1);
    return (
        <div style={{ display: 'flex', alignItems: 'flex-end', gap: 6, height, padding: '8px 4px 0' }}>
            {data.map((d, i) => {
                const h = ((d.total || 0) / max) * (height - 30);
                return (
                    <div key={i} style={{ flex: 1, display: 'flex', flexDirection: 'column', alignItems: 'center', minWidth: 0 }}>
                        <div style={{
                            height: Math.max(2, h),
                            width: '100%', maxWidth: 22,
                            borderRadius: '4px 4px 2px 2px',
                            background: 'linear-gradient(180deg, var(--primary) 0%, var(--primary-deep) 100%)',
                            position: 'relative',
                        }} title={`${d.label}: ${d.total}`} />
                        <div style={{ fontSize: 10.5, color: 'var(--muted)', marginTop: 6, fontVariantNumeric: 'tabular-nums' }}>{d.label}</div>
                    </div>
                );
            })}
        </div>
    );
};

// ============== Donut ==============
const Donut = ({ segments, size = 130, thick = 16, label, sub }) => {
    const total = segments.reduce((s, x) => s + x.value, 0) || 1;
    const r = size / 2 - thick / 2;
    const c = 2 * Math.PI * r;
    let acc = 0;
    return (
        <div style={{ position: 'relative', width: size, height: size }}>
            <svg viewBox={`0 0 ${size} ${size}`} width={size} height={size}>
                <circle cx={size/2} cy={size/2} r={r} stroke="var(--surface-strong)" strokeWidth={thick} fill="none" />
                {segments.map((s, i) => {
                    const len = (s.value / total) * c;
                    const dash = `${len} ${c - len}`;
                    const off = -acc;
                    acc += len;
                    return (
                        <circle
                            key={i}
                            cx={size/2} cy={size/2} r={r}
                            stroke={s.color} strokeWidth={thick} fill="none"
                            strokeDasharray={dash}
                            strokeDashoffset={off}
                            transform={`rotate(-90 ${size/2} ${size/2})`}
                            strokeLinecap="butt"
                        />
                    );
                })}
            </svg>
            <div style={{ position: 'absolute', inset: 0, display: 'grid', placeItems: 'center', textAlign: 'center' }}>
                <div>
                    <div style={{ fontSize: 22, fontWeight: 700, letterSpacing: '-0.04em', fontVariantNumeric: 'tabular-nums' }}>{label}</div>
                    {sub && <div style={{ fontSize: 11.5, color: 'var(--muted)', marginTop: 2 }}>{sub}</div>}
                </div>
            </div>
        </div>
    );
};

// expose
Object.assign(window, {
    STATUS, Icon, ICONS, StatusPill, Avatar, InlineField, Search,
    KPI, Card, CardHead, Drawer, Modal, Toast, Empty, PhotoSlot,
    Spark, BarChart, Donut,
});
