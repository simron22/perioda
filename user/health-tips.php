<?php
require_once __DIR__ . '/../includes/user_auth.php';

$page_title = 'Health Advisor';
$active = 'tips'; // keep active as tips to highlight the correct nav item
require_once __DIR__ . '/../includes/user_head.php';
?>

<div class="page-title">
    <div>
        <h1>Perioda Health Advisor</h1>
        <p>Search your symptoms to learn about possible period-related conditions and what you can do.</p>
    </div>
</div>

<div class="card" style="padding: 2rem;">
    <h2 style="margin-bottom: 1rem;">🔍 Search your symptoms...</h2>
    <div style="display: flex; gap: 10px;">
        <input type="text" id="symptomSearchInput" class="form-control" placeholder="e.g. I have severe stomach cramps and nausea" style="flex: 1; padding: 12px; font-size: 16px;">
        <button id="symptomSearchBtn" class="btn btn-primary" style="padding: 12px 24px; font-size: 16px;">Search</button>
    </div>
    <div style="margin-top: 10px; color: var(--muted); font-size: 0.9em;">
        Try typing natural-language symptoms, like "I feel dizzy during my period" or "I have heavy bleeding and feel weak".
    </div>
</div>

<div id="advisorResultContainer" style="margin-top: 2rem; display: none;">
    <!-- Results will be injected here via JS -->
</div>

<div class="disclaimer" style="margin-top: 3rem;">
    ⓘ This information is not a diagnosis. These tips are general educational information, not medical advice. Do not use this as a substitute for professional medical help.
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const searchInput = document.getElementById("symptomSearchInput");
    const searchBtn = document.getElementById("symptomSearchBtn");
    const resultContainer = document.getElementById("advisorResultContainer");

    searchBtn.addEventListener("click", performSearch);
    searchInput.addEventListener("keypress", function(e) {
        if (e.key === "Enter") {
            performSearch();
        }
    });

    function performSearch() {
        const query = searchInput.value.trim();
        if (!query) {
            alert("Please enter your symptoms to search.");
            return;
        }

        searchBtn.textContent = "Searching...";
        searchBtn.disabled = true;
        resultContainer.style.display = "block";
        resultContainer.innerHTML = '<div class="card"><p class="empty-state">Analyzing symptoms...</p></div>';

        fetch("../api/symptom_advisor.php?q=" + encodeURIComponent(query))
            .then(response => response.json())
            .then(data => {
                searchBtn.textContent = "Search";
                searchBtn.disabled = false;

                if (data.success && data.condition) {
                    renderResult(data.condition);
                } else {
                    resultContainer.innerHTML = `
                        <div class="card empty-state" style="padding: 2rem; text-align: center;">
                            <div style="font-size: 3rem; margin-bottom: 1rem;">🤔</div>
                            <h3>No specific match found</h3>
                            <p>${data.error || "We couldn't find a specific condition matching those terms."}</p>
                        </div>
                    `;
                }
            })
            .catch(error => {
                searchBtn.textContent = "Search";
                searchBtn.disabled = false;
                resultContainer.innerHTML = `
                    <div class="card empty-state">
                        <p style="color: red;">An error occurred while connecting to the server. Please try again.</p>
                    </div>
                `;
            });
    }

    function renderResult(cond) {
        let commonSymptomsHtml = cond.common_symptoms.map(s => `<li>${s}</li>`).join('');
        let selfCareHtml = cond.self_care.map(s => `<li>${s}</li>`).join('');

        const html = `
            <div class="card" style="border-top: 4px solid var(--primary-color);">
                <span class="tag" style="background-color: #f0e6ff; color: var(--primary-color);">Possible condition</span>
                <h2 style="margin-top: 10px; color: var(--text-dark);">${cond.condition_name}</h2>
                
                <p style="font-size: 1.1em; line-height: 1.6; margin-bottom: 1.5rem; color: #444;">
                    <strong>Why this may be related:</strong><br>
                    ${cond.description}
                </p>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div style="background: var(--bg-color); padding: 15px; border-radius: 8px;">
                        <h4 style="color: var(--text-dark); margin-bottom: 10px;">📋 Common associated symptoms</h4>
                        <ul style="padding-left: 20px; color: var(--text-muted); line-height: 1.5;">
                            ${commonSymptomsHtml}
                        </ul>
                    </div>
                    
                    <div style="background: var(--bg-color); padding: 15px; border-radius: 8px;">
                        <h4 style="color: var(--text-dark); margin-bottom: 10px;">💆‍♀️ General self-care suggestions</h4>
                        <ul style="padding-left: 20px; color: var(--text-muted); line-height: 1.5;">
                            ${selfCareHtml}
                        </ul>
                    </div>
                </div>

                <div style="margin-top: 20px; background: #fff0f0; padding: 15px; border-radius: 8px; border-left: 4px solid #ff4d4d;">
                    <h4 style="color: #d32f2f; margin-bottom: 5px;">⚠️ When to seek medical attention</h4>
                    <p style="color: #b71c1c; margin: 0; line-height: 1.5;">
                        ${cond.warning_signs}
                    </p>
                </div>
            </div>
        `;
        resultContainer.innerHTML = html;
        resultContainer.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
});
</script>

<?php require_once __DIR__ . '/../includes/user_foot.php'; ?>
