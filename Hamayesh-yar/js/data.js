// =========================================================
// DATA.JS - داده‌های مرکزی پروژه
// =========================================================

// ===== داده‌های همایش‌ها =====
window.eventsData = [
    {
        id: 1,
        title: 'همایش فناوری اطلاعات و ارتباطات',
        category: 'فناوری',
        status: 'upcoming',
        date: '۲۰ شهریور ۱۴۰۵',
        time: '۰۹:۰۰ - ۱۷:۰۰',
        duration: '۸ ساعت',
        location: 'سالن همایش دانشکده',
        address: 'دانشکده فنی و مهندسی، سالن همایش',
        organizer: 'دانشکده مهندسی کامپیوتر',
        image: 'images/event-1.jpg',
        description: 'همایش یک روزه با حضور متخصصان برتر حوزه فناوری اطلاعات',
        longDescription: 'همایش فناوری اطلاعات و ارتباطات با هدف ایجاد بستری برای تبادل نظر...',
        participants: 250,
        papers: 85,
        presentations: 12,
        price: 'رایگان',
        deadline: '۱۰ شهریور ۱۴۰۵',
        capacity: 300,
        statusText: 'فعال',
        speakers: [
            { name: 'دکتر رضا محمدی', title: 'استاد دانشگاه صنعتی', image: 'images/speaker-1.jpg' },
            { name: 'دکتر سارا حسینی', title: 'پژوهشگر ارشد هوش مصنوعی', image: 'images/speaker-2.jpg' }
        ],
        schedule: [
            { time: '۰۸:۰۰ - ۰۸:۳۰', title: 'ثبت‌نام و پذیرش', speaker: '-' },
            { time: '۰۸:۳۰ - ۰۹:۰۰', title: 'مراسم افتتاحیه', speaker: 'دکتر رئیس دانشکده' },
            { time: '۰۹:۰۰ - ۱۰:۰۰', title: 'سخنرانی کلیدی: آینده هوش مصنوعی', speaker: 'دکتر رضا محمدی' }
        ],
        papersList: [
            { title: 'بررسی الگوریتم‌های یادگیری عمیق', author: 'دکتر رضا محمدی' },
            { title: 'کاربرد بلاکچین در امنیت اطلاعات', author: 'دکتر سارا حسینی' }
        ],
        relatedEvents: [2, 3, 4]
    },
    {
        id: 2,
        title: 'کنفرانس ملی مهندسی و نوآوری',
        category: 'مهندسی',
        status: 'upcoming',
        date: '۵ مهر ۱۴۰۵',
        time: '۰۸:۳۰ - ۱۸:۰۰',
        location: 'سالن اجتماعات دانشکده',
        image: 'images/event-2.jpg',
        description: 'کنفرانس دو روزه با محوریت نوآوری در مهندسی و فناوری',
        price: '۱۵۰,۰۰۰ تومان',
        deadline: '۲۵ شهریور ۱۴۰۵'
    },
    {
        id: 3,
        title: 'نشست علمی پژوهش و توسعه',
        category: 'علمی',
        status: 'ongoing',
        date: '۱۸ مهر ۱۴۰۵',
        time: '۱۰:۰۰ - ۱۶:۰۰',
        location: 'مرکز همایش‌های دانشگاه',
        image: 'images/event-3.jpg',
        description: 'نشست تخصصی با محوریت پژوهش و توسعه در علوم پایه',
        price: 'رایگان',
        deadline: '۱۰ مهر ۱۴۰۵'
    },
    {
        id: 4,
        title: 'کنگره بین‌المللی پزشکی نوین',
        category: 'پزشکی',
        status: 'upcoming',
        date: '۱۵ آبان ۱۴۰۵',
        time: '۰۸:۰۰ - ۲۰:۰۰',
        location: 'سالن همایش‌های بین‌المللی',
        image: 'images/event-4.jpg',
        description: 'کنگره سه روزه با حضور پزشکان و پژوهشگران بین‌المللی',
        price: '۲۵۰,۰۰۰ تومان',
        deadline: '۵ آبان ۱۴۰۵'
    }
];

// ===== داده‌های مقالات =====
window.articlesData = [
    {
        id: 1,
        title: 'بررسی تأثیر هوش مصنوعی بر یادگیری',
        authorId: 1001,
        authorName: 'دکتر رضا محمدی',
        submitDate: '۱۴۰۳/۱۰/۲۵',
        status: 'accepted',
        finalDecision: 'accept',
        abstract: 'این مقاله به بررسی تأثیر هوش مصنوعی بر فرآیند یادگیری می‌پردازد...',
        keywords: 'هوش مصنوعی، یادگیری، آموزش',
        file: 'ai-learning.pdf',
        // === این بخش اضافه شده تا داور آن را ببیند ===
        reviewers: [201], 
        reviews: [
            { reviewerId: 201, score: 85, comment: 'مقاله بسیار خوبی است.', suggestion: 'accept' },
            { reviewerId: 202, score: 90, comment: 'بسیار خوب', suggestion: 'accept' }
        ],
        presentation: { date: '۱۴۰۴/۰۱/۱۵', time: '۱۰:۰۰', hall: 'سالن اصلی', type: 'سخنرانی' }
    },
    {
        id: 2,
        title: 'روش‌های نوین در آموزش مجازی',
        authorId: 1001,
        authorName: 'دکتر رضا محمدی',
        submitDate: '۱۴۰۳/۱۱/۰۵',
        status: 'revision',
        finalDecision: 'revision',
        abstract: 'در این مقاله روش‌های نوین آموزش مجازی بررسی شده است...',
        keywords: 'آموزش مجازی، یادگیری آنلاین',
        file: 'virtual-education.pdf',
        // === این بخش اضافه شده تا داور آن را ببیند ===
        reviewers: [201],
        reviews: [
            { reviewerId: 201, score: 75, comment: 'خوب اما نیاز به اصلاح دارد', suggestion: 'revision' }
        ],
        presentation: null
    },
    {
        id: 3,
        title: 'کاربرد بلاکچین در سیستم‌های مالی',
        authorId: 1001,
        authorName: 'دکتر رضا محمدی',
        submitDate: '۱۴۰۳/۱۲/۱۲',
        status: 'pending',
        finalDecision: null,
        abstract: 'این مقاله کاربردهای بلاکچین در سیستم‌های مالی را بررسی می‌کند...',
        keywords: 'بلاکچین، مالی، امنیت',
        file: 'blockchain-finance.pdf',
        // === این بخش اضافه شده تا داور آن را ببیند ===
        reviewers: [201],
        reviews: [],
        presentation: null
    },
    {
        id: 4,
        title: 'تحلیل داده‌های بزرگ در علوم پزشکی',
        authorId: 1002,
        authorName: 'دکتر علی کریمی',
        submitDate: '۱۴۰۳/۱۲/۲۰',
        status: 'reviewing',
        finalDecision: null,
        abstract: 'تحلیل داده‌های بزرگ در علوم پزشکی و کاربردهای آن...',
        keywords: 'داده‌کاوی، پزشکی، تحلیل',
        file: 'big-data-medical.pdf',
        // === این بخش اضافه شده تا داور آن را ببیند ===
        reviewers: [2],
        reviews: [
            { reviewerId: 202, score: 78, comment: 'خوب', suggestion: 'accept' }
        ],
        presentation: null
    }
];

// ===== داده‌های نویسندگان =====
window.authorsData = [
    {
        id: 1,
        name: 'دکتر رضا محمدی',
        title: 'استاد دانشگاه صنعتی',
        organization: 'دانشگاه صنعتی',
        field: 'هوش مصنوعی',
        avatar: 'images/avatar-1.jpg',
        papers: 12,
        citations: 345,
        email: 'reza.mohammadi@example.com',
        bio: 'استاد تمام با بیش از ۲۰ سال سابقه در حوزه هوش مصنوعی و یادگیری ماشین.',
        social: { google: '#', linkedin: '#', researchgate: '#' },
        isTop: true
    },
    {
        id: 2,
        name: 'دکتر سارا حسینی',
        title: 'دانشیار دانشگاه صنعتی',
        organization: 'دانشگاه صنعتی',
        field: 'داده‌کاوی',
        avatar: 'images/avatar-2.jpg',
        papers: 9,
        citations: 210,
        email: 'sara.hosseini@example.com',
        bio: 'پژوهشگر برتر در حوزه داده‌کاوی و تحلیل داده‌های بزرگ.',
        social: { google: '#', linkedin: '#', researchgate: '#' },
        isTop: true
    },
    {
        id: 3,
        name: 'دکتر علی کریمی',
        title: 'استاد دانشگاه علوم پزشکی',
        organization: 'دانشگاه علوم پزشکی',
        field: 'بیوانفورماتیک',
        avatar: 'images/avatar-3.jpg',
        papers: 7,
        citations: 180,
        email: 'ali.karimi@example.com',
        bio: 'پژوهشگر حوزه بیوانفورماتیک و کاربرد هوش مصنوعی در پزشکی.',
        social: { google: '#', linkedin: '#', researchgate: '#' },
        isTop: false
    }
];

// ===== بارگذاری مقالات ذخیره‌شده =====
(function () {
    try {
        var stored = localStorage.getItem('hy_articles');
        if (stored) {
            var parsed = JSON.parse(stored);
            if (Array.isArray(parsed)) window.articlesData = parsed;
        }
    } catch (e) {
        // در صورت خرابی داده ذخیره‌شده، از داده‌های اولیه استفاده می‌شود.
    }
})();
