/* global React, ReactDOM, Icon, Modal, Toast, Drawer,
   TopBar, DashboardScreen, TicketsScreen, TicketDrawer,
   MapScreen, EmployeesScreen, ReportScreen, ClaimsScreen, SuperAdminScreen,
   TweaksPanel, useTweaks, TweakSection, TweakRadio, TweakSelect, TweakColor,
   TweakToggle, TweakButton, CLAIMS */
const { useState, useEffect, useMemo, useCallback } = React;

const TWEAK_DEFAULTS = /*EDITMODE-BEGIN*/{
    "role": "org_admin",
    "theme": "light",
    "primary": "#0E7A42",
    "screen": "dashboard",
    "density": "comfortable",
    "headerStyle": "pills"
}/*EDITMODE-END*/;

function App() {
    const [t, setTweak] = useTweaks(TWEAK_DEFAULTS);

    // theme
    useEffect(() => {
        document.documentElement.setAttribute('data-theme', t.theme || 'light');
    }, [t.theme]);

    // primary color override
    useEffect(() => {
        const style = document.getElementById('__primary-override') || (() => {
            const s = document.createElement('style');
            s.id = '__primary-override';
            document.head.appendChild(s);
            return s;
        })();
        const c = t.primary || '#0E7A42';
        // derive deep
        style.textContent = `
            :root, [data-theme="light"] {
                --primary: ${c};
                --primary-deep: color-mix(in srgb, ${c} 85%, #000 15%);
                --primary-soft: color-mix(in srgb, ${c} 14%, #ffffff 86%);
                --primary-soft-2: color-mix(in srgb, ${c} 7%, #ffffff 93%);
                --primary-ink: color-mix(in srgb, ${c} 75%, #000 25%);
                --c-green: ${c};
                --c-green-soft: color-mix(in srgb, ${c} 14%, #ffffff 86%);
                --c-green-ink: color-mix(in srgb, ${c} 75%, #000 25%);
            }
            [data-theme="dark"] {
                --primary: ${c};
                --primary-deep: color-mix(in srgb, ${c} 85%, #000 15%);
                --primary-soft: color-mix(in srgb, ${c} 22%, #0E1411 78%);
                --primary-soft-2: color-mix(in srgb, ${c} 12%, #0E1411 88%);
                --primary-ink: color-mix(in srgb, ${c} 40%, #ffffff 60%);
            }
        `;
    }, [t.primary]);

    // density
    useEffect(() => {
        const root = document.documentElement;
        if (t.density === 'compact') {
            root.style.setProperty('--r-lg', '12px');
            root.style.setProperty('--r', '10px');
        } else {
            root.style.removeProperty('--r-lg');
            root.style.removeProperty('--r');
        }
    }, [t.density]);

    // navigation state
    const screen = t.screen || 'dashboard';
    const setScreen = (s) => setTweak('screen', s);
    const role = t.role || 'org_admin';

    // global toasts
    const [toasts, setToasts] = useState([]);
    const pushToast = useCallback((toast) => {
        const id = Date.now() + Math.random();
        setToasts(ts => [...ts, { ...toast, id }]);
        setTimeout(() => setToasts(ts => ts.filter(x => x.id !== id)), 4500);
    }, []);
    const removeToast = (id) => setToasts(ts => ts.filter(x => x.id !== id));

    // confirmation modal
    const [confirm, setConfirm] = useState(null);

    // ticket drawer
    const [ticketDrawer, setTicketDrawer] = useState(null);

    return (
        <div className="app-shell">
            <TopBar
                current={screen}
                onNav={setScreen}
                role={role}
                theme={t.theme || 'light'}
                onTheme={() => setTweak('theme', t.theme === 'dark' ? 'light' : 'dark')}
                counts={{ tickets: 11, claims: CLAIMS.length }}
            />

            {role === 'super_admin' ? (
                screen === 'dashboard' ? <SuperAdminScreen onNav={setScreen} />
                : screen === 'users' ? <SuperAdminScreen onNav={setScreen} />
                : <SuperAdminScreen onNav={setScreen} />
            ) : (
                <>
                    {screen === 'dashboard' && <DashboardScreen onNav={setScreen} onOpenTicket={setTicketDrawer} onToast={pushToast} />}
                    {screen === 'tickets' && <TicketsScreen onOpenTicket={setTicketDrawer} onToast={pushToast} />}
                    {screen === 'map' && <MapScreen onOpenTicket={setTicketDrawer} />}
                    {screen === 'claims' && <ClaimsScreen onToast={pushToast} onOpenTicket={setTicketDrawer} />}
                    {screen === 'employees' && <EmployeesScreen onToast={pushToast} onConfirm={setConfirm} />}
                    {screen === 'report' && <ReportScreen onToast={pushToast} />}
                </>
            )}

            {/* Drawer */}
            <TicketDrawer
                ticket={ticketDrawer}
                onClose={() => setTicketDrawer(null)}
                onToast={pushToast}
                onConfirm={setConfirm}
            />

            {/* Confirmation modal */}
            <Modal open={!!confirm} onClose={() => setConfirm(null)}>
                {confirm && (
                    <>
                        <div className="modal-head">
                            <div className="modal-icon">
                                <Icon name="alert" size={20} />
                            </div>
                            <h3 className="modal-title">
                                {confirm.type === 'hide' && `Скрыть заявку №${confirm.ticket?.id}?`}
                                {confirm.type === 'ban' && `Заблокировать пользователя?`}
                                {confirm.type === 'delete_code' && `Удалить ключ доступа?`}
                            </h3>
                        </div>
                        <div className="modal-body">
                            {confirm.type === 'hide' && 'Заявка будет скрыта из рабочих показателей и отчётов вашего ЖКХ. Восстановить можно из реестра «Скрытые».'}
                            {confirm.type === 'ban' && 'Аккаунт получит глобальную блокировку. Пользователь не сможет подавать новые заявки.'}
                            {confirm.type === 'delete_code' && `Ключ ${confirm.code} будет безвозвратно удалён. Если ключ ещё не использовался — отменить нельзя.`}
                        </div>
                        <div className="modal-foot">
                            <button className="btn ghost" onClick={() => setConfirm(null)}>Отмена</button>
                            <button className="btn danger" onClick={() => {
                                pushToast({ tone: 'warn', icon: 'check', title: 'Готово', body: confirm.type === 'hide' ? `Заявка №${confirm.ticket?.id} скрыта` : confirm.type === 'delete_code' ? `Ключ ${confirm.code} удалён` : 'Пользователь заблокирован' });
                                setConfirm(null);
                            }}>
                                {confirm.type === 'hide' && 'Скрыть'}
                                {confirm.type === 'ban' && 'Заблокировать'}
                                {confirm.type === 'delete_code' && 'Удалить'}
                            </button>
                        </div>
                    </>
                )}
            </Modal>

            {/* Toasts */}
            <div className="toast-stack">
                {toasts.map(toast => (
                    <Toast key={toast.id} {...toast} onClose={() => removeToast(toast.id)} />
                ))}
            </div>

            {/* Tweaks panel */}
            <TweaksPanel title="Tweaks">
                <TweakSection label="Роль и навигация">
                    <TweakRadio
                        label="Роль"
                        value={t.role}
                        onChange={(v) => { setTweak({ role: v, screen: 'dashboard' }); }}
                        options={[
                            { value: 'org_admin', label: 'Админ ЖКХ' },
                            { value: 'super_admin', label: 'Главный' },
                        ]}
                    />
                    <TweakSelect
                        label="Экран"
                        value={t.screen}
                        onChange={(v) => setTweak('screen', v)}
                        options={
                            t.role === 'super_admin'
                                ? [
                                    { value: 'dashboard', label: 'Контроль' },
                                    { value: 'users', label: 'Пользователи' },
                                    { value: 'orgs', label: 'ЖКХ' },
                                ]
                                : [
                                    { value: 'dashboard', label: 'Обзор' },
                                    { value: 'tickets', label: 'Заявки' },
                                    { value: 'map', label: 'Карта' },
                                    { value: 'claims', label: 'Запросы' },
                                    { value: 'employees', label: 'Сотрудники' },
                                    { value: 'report', label: 'Отчёт' },
                                ]
                        }
                    />
                </TweakSection>
                <TweakSection label="Внешний вид">
                    <TweakRadio
                        label="Тема"
                        value={t.theme}
                        onChange={(v) => setTweak('theme', v)}
                        options={[
                            { value: 'light', label: 'Светлая' },
                            { value: 'dark', label: 'Тёмная' },
                        ]}
                    />
                    <TweakColor
                        label="Основной цвет"
                        value={t.primary}
                        onChange={(v) => setTweak('primary', v)}
                        options={['#0E7A42', '#0A6E5C', '#1B5E91', '#7A4A0E']}
                    />
                    <TweakRadio
                        label="Плотность"
                        value={t.density}
                        onChange={(v) => setTweak('density', v)}
                        options={[
                            { value: 'comfortable', label: 'Обычная' },
                            { value: 'compact', label: 'Компактно' },
                        ]}
                    />
                </TweakSection>
                <TweakSection label="Демо-действия">
                    <TweakButton label="Показать toast" onClick={() => pushToast({ title: 'Заявка №24812 назначена', body: 'Исполнитель: Сергей Дробыш' })} />
                    <TweakButton label="Открыть подтверждение" secondary onClick={() => setConfirm({ type: 'ban' })} />
                </TweakSection>
            </TweaksPanel>
        </div>
    );
}

ReactDOM.createRoot(document.getElementById('root')).render(<App />);
