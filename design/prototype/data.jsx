/* global window */

// Categories (from real seeder)
const CATEGORIES = [
    { id: 1, name: 'Мусор', icon: 'trash' },
    { id: 2, name: 'Переполненная урна', icon: 'inbox' },
    { id: 3, name: 'Несанкционированная свалка', icon: 'layers' },
    { id: 4, name: 'Граффити', icon: 'image' },
    { id: 5, name: 'Стекло / опасный мусор', icon: 'alert' },
];

// Organizations (ЖКХ) — fixed Baranavichy list
const ORGS = [
    { id: 1, name: 'ЖЭС-1 Северный', address: 'ул. Брестская, 137', admins: 2, workers: 18, tickets: 142 },
    { id: 2, name: 'ЖЭС-2 Тексер',   address: 'ул. Тельмана, 18',   admins: 2, workers: 14, tickets: 98 },
    { id: 3, name: 'ЖЭС-3 Боровики', address: 'ул. Куйбышева, 45',  admins: 1, workers: 11, tickets: 76 },
    { id: 4, name: 'ЖЭС-4 Полонка',  address: 'ул. Парковая, 9А',   admins: 1, workers: 9,  tickets: 54 },
    { id: 5, name: 'ГорКомЭкоПарк',  address: 'ул. Советская, 79',  admins: 1, workers: 7,  tickets: 39 },
];

// Workers
const WORKERS = [
    { id: 11, name: 'Сергей Дробыш',    email: 's.drobysh@gkx-bar.by',   org: 1, active: 4, assigned: 12, completed: 47, created: '12.02.2026', online: true },
    { id: 12, name: 'Анна Левченко',    email: 'a.levchenko@gkx-bar.by', org: 1, active: 2, assigned: 9,  completed: 38, created: '03.03.2026', online: true },
    { id: 13, name: 'Виктор Романчук',  email: 'v.romanchuk@gkx-bar.by', org: 1, active: 5, assigned: 14, completed: 52, created: '21.01.2026', online: false },
    { id: 14, name: 'Мария Бондарь',    email: 'm.bondar@gkx-bar.by',    org: 1, active: 0, assigned: 6,  completed: 41, created: '08.02.2026', online: false },
    { id: 15, name: 'Олег Сухой',       email: 'o.sukhoy@gkx-bar.by',    org: 1, active: 3, assigned: 11, completed: 35, created: '17.03.2026', online: true },
    { id: 16, name: 'Юлия Грицкевич',   email: 'y.gritskev@gkx-bar.by',  org: 1, active: 1, assigned: 7,  completed: 28, created: '02.04.2026', online: true },
    { id: 17, name: 'Дмитрий Карпович', email: 'd.karpovich@gkx-bar.by', org: 1, active: 0, assigned: 3,  completed: 22, created: '11.04.2026', online: false },
    { id: 18, name: 'Татьяна Жук',      email: 't.zhuk@gkx-bar.by',      org: 1, active: 2, assigned: 8,  completed: 19, created: '24.04.2026', online: false },
];

// Worker codes
const CODES = [
    { code: 'JKH-BR1-A7K2', issued: 'Бригада №2 / Иванов А.', state: 'available', used_by: null, date: '18.05.2026 09:14' },
    { code: 'JKH-BR1-W3F8', issued: 'Карпович Д.',            state: 'used',      used_by: 'Дмитрий Карпович', date: '11.04.2026 14:22' },
    { code: 'JKH-BR1-Q1M5', issued: 'Грицкевич Ю.',           state: 'used',      used_by: 'Юлия Грицкевич',   date: '02.04.2026 11:05' },
    { code: 'JKH-BR1-Z9X4', issued: 'Жук Т.',                 state: 'used',      used_by: 'Татьяна Жук',     date: '24.04.2026 16:48' },
    { code: 'JKH-BR1-H4N7', issued: 'Резерв — субподряд',     state: 'available', used_by: null, date: '22.05.2026 10:00' },
    { code: 'JKH-BR1-P2L9', issued: 'Стажёр май',             state: 'revoked',   used_by: null, date: '06.05.2026 12:30' },
];

// Tickets (real coords near Baranavichy ≈ 53.13, 26.01)
const ADDRESSES = [
    'ул. Советская, 84',
    'ул. Тельмана, 24',
    'ул. Брестская, 137',
    'ул. Куйбышева, 32',
    'ул. Парковая, 9А',
    'ул. Ленина, 156',
    'ул. Гагарина, 41',
    'пр-т Машерова, 12',
    'ул. Чкалова, 8',
    'ул. Войкова, 23',
    'ул. Притыцкого, 5',
    'ул. Полевая, 17',
];

const DESCRIPTIONS = [
    'Около подъезда вторую неделю не вывозят строительный мусор. Лежит у мусорных контейнеров.',
    'Контейнер переполнен, мешки стоят рядом, разносит ветром.',
    'На детской площадке появилась надпись краской на стене дома.',
    'Между гаражами скопилось битое стекло, дети бегают рядом.',
    'Стихийная свалка веток и листьев у дороги.',
    'Бак переполнен уже третий день, рядом разбросаны коробки.',
    'Граффити на трансформаторной будке.',
    'Битые бутылки на тротуаре после праздничных выходных.',
    'Возле подъезда выброшен старый диван и матрас.',
    'Под лестницей мусор и листья, никто не убирает.',
    'Аэрозольные баллончики в зелёной зоне у школы.',
    'Под скамейкой — пакеты с мусором, неприятный запах.',
];

const RESIDENTS = [
    'Алла К.', 'Игорь М.', 'Светлана Р.', 'Николай П.',
    'Ольга Т.', 'Андрей Б.', 'Елена Г.', 'Павел Ш.',
    'Татьяна О.', 'Сергей В.', 'Юлия Ц.', 'Денис Х.',
];

const STATUSES = ['created', 'created', 'assigned', 'in_progress', 'in_progress', 'completed', 'completed', 'rejected', 'duplicate'];

const TICKETS = (() => {
    const arr = [];
    // distribute around Baranavichy ≈ 53.1327, 26.0139
    const seedRand = (i) => {
        const s = Math.sin(i * 9301 + 49297) * 233280;
        return s - Math.floor(s);
    };
    for (let i = 0; i < 32; i++) {
        const status = STATUSES[i % STATUSES.length];
        const cat = CATEGORIES[i % CATEGORIES.length];
        const addr = ADDRESSES[i % ADDRESSES.length];
        const desc = DESCRIPTIONS[i % DESCRIPTIONS.length];
        const resident = RESIDENTS[i % RESIDENTS.length];
        const assigned = (status === 'created' || status === 'rejected') ? null : WORKERS[i % WORKERS.length];
        const priority = i % 7 === 0 ? 'high' : (i % 11 === 0 ? 'low' : 'normal');
        const lat = 53.1327 + (seedRand(i + 1) - 0.5) * 0.045;
        const lng = 26.0139 + (seedRand(i + 2) - 0.5) * 0.075;
        const day = 27 - Math.floor(i / 2);
        const hour = (8 + (i * 3) % 12).toString().padStart(2, '0');
        const minute = ((i * 17) % 60).toString().padStart(2, '0');
        const created = `${day.toString().padStart(2,'0')}.05.2026 ${hour}:${minute}`;
        arr.push({
            id: 24800 + i,
            category: cat,
            address: addr,
            description: desc,
            resident,
            priority,
            status,
            worker: assigned,
            lat, lng,
            created,
            created_short: `${day}.05`,
            org: 'ЖЭС-1 Северный',
            duration: status === 'completed' ? `${(i % 5) + 2} ч 14 мин` : null,
            comments: 2 + (i % 4),
            history: [
                { ts: created, from: null, to: 'created', who: resident, note: 'Заявка создана из мобильного приложения' },
                ...(status !== 'created' ? [{ ts: created.replace(hour, (+hour + 1).toString().padStart(2,'0')), from: 'created', to: 'assigned', who: 'Админ ЖКХ', note: assigned ? `Назначен исполнитель ${assigned.name}` : 'Назначено на бригаду' }] : []),
                ...(['in_progress','completed'].includes(status) ? [{ ts: created.replace(hour, (+hour + 3).toString().padStart(2,'0')), from: 'assigned', to: 'in_progress', who: assigned?.name || '—', note: 'Принято в работу, выехали на адрес' }] : []),
                ...(status === 'completed' ? [{ ts: created.replace(hour, (+hour + 5).toString().padStart(2,'0')), from: 'in_progress', to: 'completed', who: assigned?.name || '—', note: 'Мусор вывезен. Фото после работ приложено.' }] : []),
                ...(status === 'rejected' ? [{ ts: created.replace(hour, (+hour + 2).toString().padStart(2,'0')), from: 'created', to: 'rejected', who: 'Админ ЖКХ', note: 'Не относится к зоне обслуживания ЖЭС-1.' }] : []),
                ...(status === 'duplicate' ? [{ ts: created.replace(hour, (+hour + 1).toString().padStart(2,'0')), from: 'created', to: 'duplicate', who: 'Админ ЖКХ', note: 'Дубликат №24802.' }] : []),
            ],
        });
    }
    return arr;
})();

// Stats
const STATS_ORG = {
    total: TICKETS.length,
    new: TICKETS.filter(t => t.status === 'created').length,
    active: TICKETS.filter(t => ['assigned','accepted','in_progress','problem'].includes(t.status)).length,
    completed: TICKETS.filter(t => t.status === 'completed').length,
    rejected: TICKETS.filter(t => t.status === 'rejected').length,
    hidden: 2,
};

const DAILY = [
    { label: 'пн 18.05', total: 7 },
    { label: 'вт 19.05', total: 12 },
    { label: 'ср 20.05', total: 9 },
    { label: 'чт 21.05', total: 18 },
    { label: 'пт 22.05', total: 14 },
    { label: 'сб 23.05', total: 6 },
    { label: 'вс 24.05', total: 4 },
    { label: 'пн 25.05', total: 16 },
    { label: 'вт 26.05', total: 19 },
    { label: 'ср 27.05', total: 11 },
];

const CATEGORY_STATS = [
    { name: 'Мусор',                       total: 48, in_work: 11, completed: 31, rejected: 6 },
    { name: 'Переполненная урна',          total: 27, in_work: 6,  completed: 19, rejected: 2 },
    { name: 'Несанкционированная свалка',  total: 14, in_work: 5,  completed: 7,  rejected: 2 },
    { name: 'Граффити',                    total: 11, in_work: 3,  completed: 6,  rejected: 2 },
    { name: 'Стекло / опасный мусор',      total: 9,  in_work: 2,  completed: 6,  rejected: 1 },
];

// Claim requests (Запросы — workers asking to take a ticket)
const CLAIMS = [
    { id: 901, worker: WORKERS[0], ticket: TICKETS[2], created: '27.05.2026 09:14', note: 'Уже на адресе, могу принять и закрыть сегодня.' },
    { id: 902, worker: WORKERS[2], ticket: TICKETS[5], created: '27.05.2026 08:42', note: 'Свободен, маршрут через эту улицу.' },
    { id: 903, worker: WORKERS[4], ticket: TICKETS[7], created: '27.05.2026 08:10', note: '' },
];

Object.assign(window, {
    CATEGORIES, ORGS, WORKERS, CODES, TICKETS,
    STATS_ORG, DAILY, CATEGORY_STATS, CLAIMS,
});
