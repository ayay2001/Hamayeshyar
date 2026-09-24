(function () {
    'use strict';

    function faDate(value) {
        if (!value) return 'تاریخ نامشخص';
        var d = new Date(value + 'T00:00:00');
        if (Number.isNaN(d.getTime())) return value;
        return new Intl.DateTimeFormat('fa-IR-u-ca-persian', { day: 'numeric', month: 'long', year: 'numeric' }).format(d);
    }

    function faMoney(value) {
        var n = Number(value || 0);
        return n <= 0 ? 'رایگان' : new Intl.NumberFormat('fa-IR').format(n) + ' تومان';
    }

    function normalizeEvent(e) {
        e = e || {};
        var date = e.start_date ? faDate(e.start_date) : (e.date || 'تاریخ نامشخص');
        if (e.end_date && e.end_date !== e.start_date) date += ' تا ' + faDate(e.end_date);
        var startTime = e.start_time || '';
        var endTime = e.end_time || '';
        var time = e.time || [startTime, endTime].filter(Boolean).join(' - ') || '—';
        return Object.assign({}, e, {
            date: date,
            time: time,
            price: e.price !== undefined ? faMoney(e.price) : (e.price || 'رایگان'),
            deadline: e.submission_deadline ? faDate(e.submission_deadline) : (e.deadline || '—'),
            statusText: ({ upcoming: 'فعال', ongoing: 'در حال برگزاری', past: 'پایان یافته', cancelled: 'لغو شده' })[e.status] || e.status || 'همایش'
        });
    }

    async function loadEvents(options) {
        options = options || {};
        var url = 'backend/api/events.php';
        if (options.id) url += '?id=' + encodeURIComponent(options.id);
        var result = await window.apiRequest(url.replace('backend/api/', ''), { method: 'GET' });
        if (!result.ok) throw new Error(result.message || 'دریافت همایش‌ها انجام نشد.');
        if (options.id) return normalizeEvent(result.event);
        return (result.events || []).map(normalizeEvent);
    }

    window.loadEventsFromApi = loadEvents;
    window.normalizeEventForUi = normalizeEvent;
})();
