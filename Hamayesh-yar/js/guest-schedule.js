// =========================================================
// GUEST-SCHEDULE.JS - برنامه ارائه مقالات
// =========================================================

document.addEventListener("DOMContentLoaded", function() {
    var cardsContainer = document.getElementById("presentationCards");
    if (!cardsContainer) return;
    var searchInput = document.getElementById("searchPresentation");
    if (!searchInput) return;
    var hallFilter = document.getElementById("hallFilter");
    if (!hallFilter) return;

    // فقط مقالات پذیرفته شده
    var acceptedArticles = window.articlesData.filter(function(article) {
        return article.status === "accepted" || article.finalDecision === "accept";
    });

    // آمار بالا
    document.getElementById("presentationCount").textContent = acceptedArticles.length;

    var halls = [];
    for (var i = 0; i < acceptedArticles.length; i++) {
        if (acceptedArticles[i].presentation && acceptedArticles[i].presentation.hall) {
            if (halls.indexOf(acceptedArticles[i].presentation.hall) === -1) {
                halls.push(acceptedArticles[i].presentation.hall);
            }
        }
    }
    document.getElementById("hallCount").textContent = halls.length;

    var authors = [];
    for (var j = 0; j < acceptedArticles.length; j++) {
        if (authors.indexOf(acceptedArticles[j].authorName) === -1) {
            authors.push(acceptedArticles[j].authorName);
        }
    }
    document.getElementById("authorCount").textContent = authors.length;

    // پر کردن Select سالن
    for (var k = 0; k < halls.length; k++) {
        hallFilter.innerHTML += '<option value="' + halls[k] + '">' + halls[k] + '</option>';
    }

    // ساخت کارت‌ها
    function renderCards(list) {
        cardsContainer.innerHTML = "";

        if (list.length === 0) {
            cardsContainer.innerHTML = `
                <div class="card">
                    <h3>موردی یافت نشد 😕</h3>
                    <p>مقاله‌ای با این مشخصات وجود ندارد.</p>
                </div>
            `;
            return;
        }

        for (var i = 0; i < list.length; i++) {
            var article = list[i];
            cardsContainer.innerHTML += `
                <div class="presentation-card">
                    <div class="presentation-top">
                        <span class="presentation-status">آماده ارائه</span>
                        <span class="presentation-type">${article.presentation ? article.presentation.type : 'سخنرانی'}</span>
                    </div>
                    <h2 class="presentation-title">${article.title}</h2>
                    <div class="presentation-info">
                        <div>
                            <i class="fa-solid fa-user"></i>
                            <span>${article.authorName}</span>
                        </div>
                        <div>
                            <i class="fa-solid fa-calendar"></i>
                            <span>${article.presentation ? article.presentation.date : 'نامشخص'}</span>
                        </div>
                        <div>
                            <i class="fa-solid fa-clock"></i>
                            <span>${article.presentation ? article.presentation.time : 'نامشخص'}</span>
                        </div>
                        <div>
                            <i class="fa-solid fa-building"></i>
                            <span>${article.presentation ? article.presentation.hall : 'نامشخص'}</span>
                        </div>
                    </div>
                    <div class="presentation-footer">
                        <button class="btn" onclick="showDetails(${article.id})">
                            <i class="fa-solid fa-eye"></i>
                            مشاهده جزئیات
                        </button>
                    </div>
                </div>
            `;
        }
    }

    renderCards(acceptedArticles);

    // جستجو
    function filterArticles() {
        var text = searchInput.value.trim().toLowerCase();
        var hall = hallFilter.value;

        var filtered = acceptedArticles.filter(function(article) {
            var titleMatch = (article.title || "").toLowerCase().includes(text);
            var hallMatch = hall === "" || (article.presentation && article.presentation.hall === hall);
            return titleMatch && hallMatch;
        });

        renderCards(filtered);
    }

    searchInput.addEventListener("input", filterArticles);
    hallFilter.addEventListener("change", filterArticles);
});

// جزئیات مقاله
function showDetails(id) {
    var article = null;
    for (var i = 0; i < window.articlesData.length; i++) {
        if (window.articlesData[i].id === id) {
            article = window.articlesData[i];
            break;
        }
    }
    if (!article) return;

    var message = '📄 جزئیات مقاله\n\n';
    message += '📌 عنوان: ' + article.title + '\n';
    message += '👤 ارائه دهنده: ' + article.authorName + '\n';
    message += '📅 تاریخ: ' + (article.presentation ? article.presentation.date : 'نامشخص') + '\n';
    message += '🕐 ساعت: ' + (article.presentation ? article.presentation.time : 'نامشخص') + '\n';
    message += '🏛️ سالن: ' + (article.presentation ? article.presentation.hall : 'نامشخص') + '\n';
    message += '🎤 نوع ارائه: ' + (article.presentation ? article.presentation.type : 'نامشخص');

    alert(message);
}