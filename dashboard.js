/*   ========================================= */

const MONTH_NAMES = [
    "January", "February", "March", "April",
    "May", "June", "July", "August",
    "September", "October", "November", "December"
];

document.addEventListener("DOMContentLoaded", function () {


    /* =========================
       ELEMENTS
    ========================= */

    const userName = document.getElementById("userName");
    const logoutBtn = document.getElementById("logoutBtn");
    const sidebarToggle = document.getElementById("sidebarToggle");
    const sidebar = document.getElementById("sidebar");
    const navItems = document.querySelectorAll(".nav-item");
    const sectionPanels = document.querySelectorAll(".section-panel");

    /* Calendar */
    const calendarGrid = document.getElementById("calendarGrid");
    const calendarMonth = document.getElementById("calendarMonth");
    const prevMonthBtn = document.getElementById("prevMonth");
    const nextMonthBtn = document.getElementById("nextMonth");
    const periodStartInput = document.getElementById("periodStart");
    const periodEndInput = document.getElementById("periodEnd");
    const savePeriodBtn = document.getElementById("savePeriod");
    const cycleHistoryEl = document.getElementById("cycleHistory");

    /* Mood */
    const moodPicker = document.getElementById("moodPicker");
    const moodIntensityRow = document.getElementById("moodIntensityRow");
    const saveMoodBtn = document.getElementById("saveMood");
    const moodHistoryEl = document.getElementById("moodHistory");

    /* Symptoms */
    const symptomPicker = document.getElementById("symptomPicker");
    const severityRow = document.getElementById("severityRow");
    const saveSymptomBtn = document.getElementById("saveSymptom");
    const symptomHistoryEl = document.getElementById("symptomHistory");

    /* Notes */
    const noteTitleInput = document.getElementById("noteTitle");
    const noteContentInput = document.getElementById("noteContent");
    const saveNoteBtn = document.getElementById("saveNote");
    const notesListEl = document.getElementById("notesList");

    /* Settings */
    const cycleLengthInput = document.getElementById("cycleLength");
    const periodDurationInput = document.getElementById("periodDuration");
    const saveSettingsBtn = document.getElementById("saveSettings");
    const settingsStatus = document.getElementById("settingsStatus");


    /* =========================
       STATE
    ========================= */

    let currentMonth = new Date().getMonth();
    let currentYear = new Date().getFullYear();
    let cycleData = [];
    let selectedMood = "";
    let moodIntensity = 3;
    let selectedSymptom = "";
    let selectedSeverity = "mild";
    let userData = null;
    let userCycleLength = 28;
    let userPeriodDuration = 5;
    let editingCycleId = null;
    let editingMoodId = null;
    let editingSymptomId = null;
    let editingNoteId = null;

    let fertilityLogs = JSON.parse(localStorage.getItem('perioda_fertility_logs') || '{}');
    let selectedFertilityDate = null;


    /* =========================
       CHECK LOGIN VIA LOCALSTORAGE
    ========================= */

    let isLoggedIn = localStorage.getItem("periodaLoggedIn") === "true";
    let currentUser = localStorage.getItem("periodaCurrentUser");

    if (!isLoggedIn || !currentUser) {
        window.location.href = "login.html";
        return;
    }

    try {
        userData = JSON.parse(currentUser);
        if (userData && userData.full_name) {
            userName.textContent = userData.full_name;
            var dashNameEl = document.getElementById("dashName");
            if (dashNameEl) dashNameEl.textContent = userData.full_name;
        }
        var topbarAvatar = document.getElementById("topbarAvatar");
        var profilePreview = document.getElementById("profilePreview");
        
        // Always ensure avatar is visible in case HTML is cached with display:none
        if (topbarAvatar) {
            topbarAvatar.style.display = "inline-block";
        }

        if (userData && userData.profile_photo) {
            if (topbarAvatar) {
                topbarAvatar.src = "api/" + userData.profile_photo;
            }
            if (profilePreview) profilePreview.src = "api/" + userData.profile_photo;
        }
        populateAccountInfo();
        // Validate PHP session with the server before loading data
        fetch("api/user.php", { credentials: "same-origin" })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (!data.loggedIn) {
                    localStorage.removeItem("periodaLoggedIn");
                    localStorage.removeItem("periodaCurrentUser");
                    window.location.href = "login.html";
                } else {
                    userData = data.user;
                    localStorage.setItem("periodaCurrentUser", JSON.stringify(userData));
                    populateAccountInfo();
                    loadAllData();
                }
            })
            .catch(function(e) {
                console.error("Failed to check session:", e);
                loadAllData();
            });
    } catch (e) {
        console.error("Failed to parse user data:", e);
        window.location.href = "login.html";
    }


    function populateAccountInfo() {
        if (!userData) return;
        var nameEl = document.getElementById("settingsName");
        var emailEl = document.getElementById("settingsEmail");
        var phoneEl = document.getElementById("settingsPhone");
        var joinedEl = document.getElementById("settingsJoined");
        
        if (nameEl) nameEl.value = userData.full_name || "";
        if (emailEl) emailEl.value = userData.email || "";
        if (phoneEl) phoneEl.value = userData.phone || "";
        
        if (joinedEl && userData.created_at) {
            var d = new Date(userData.created_at);
            joinedEl.textContent = d.toLocaleDateString("en-US", { year: "numeric", month: "long", day: "numeric" });
        }
    }

    var profilePhotoInput = document.getElementById("profilePhotoInput");
    if (profilePhotoInput) {
        profilePhotoInput.addEventListener("change", function(e) {
            if (e.target.files && e.target.files[0]) {
                var reader = new FileReader();
                reader.onload = function(evt) {
                    document.getElementById("profilePreview").src = evt.target.result;
                };
                reader.readAsDataURL(e.target.files[0]);
            }
        });
    }

    var updateProfileBtn = document.getElementById("updateProfileBtn");
    if (updateProfileBtn) {
        updateProfileBtn.addEventListener("click", function() {
            var formData = new FormData();
            formData.append("full_name", document.getElementById("settingsName").value);
            formData.append("email", document.getElementById("settingsEmail").value);
            formData.append("phone", document.getElementById("settingsPhone").value);
            
            if (profilePhotoInput.files && profilePhotoInput.files[0]) {
                formData.append("profile_photo", profilePhotoInput.files[0]);
            }

            updateProfileBtn.textContent = "Updating...";
            
            fetch("api/user.php", { credentials: "same-origin",
                method: "POST",
                body: formData
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                updateProfileBtn.textContent = "Update Profile";
                if (data.success) {
                    userData = data.user;
                    localStorage.setItem("periodaCurrentUser", JSON.stringify(userData));
                    populateAccountInfo();
                    
                    if (userData.full_name) {
                        userName.textContent = userData.full_name;
                        var dashNameEl = document.getElementById("dashName");
                        if (dashNameEl) dashNameEl.textContent = userData.full_name;
                    }
                    if (userData.profile_photo) {
                        var topbarAvatar = document.getElementById("topbarAvatar");
                        if (topbarAvatar) {
                            topbarAvatar.src = "api/" + userData.profile_photo;
                            topbarAvatar.style.display = "inline-block";
                        }
                        document.getElementById("profilePreview").src = "api/" + userData.profile_photo;
                    }
                    alert("Profile updated successfully!");
                } else {
                    alert(data.error || "Failed to update profile.");
                }
            })
            .catch(function(e) {
                updateProfileBtn.textContent = "Update Profile";
                alert("Could not connect to server.");
            });
        });
    }


    function loadAllData() {
        loadCycles();
        loadMoodHistory();
        loadSymptomHistory();
        loadNotes();
        loadSettings();
        loadFertilityPageData();
        loadDashboardStats();
        renderCalendar();
        loadHealthTips();
        loadHealthTipHistory();
    }


    /* =========================
       SIDEBAR NAVIGATION
    ========================= */

    navItems.forEach(function (item) {
        item.addEventListener("click", function () {

            navItems.forEach(function (n) { n.classList.remove("active"); });
            item.classList.add("active");

            var section = item.dataset.section;
            localStorage.setItem('periodaActiveSection', section);

            sectionPanels.forEach(function (panel) {
                panel.classList.remove("active");
            });

            var target = document.getElementById("section-" + section);
            if (target) {
                target.classList.add("active");
            }

            /* Close sidebar on mobile */
            if (window.innerWidth <= 900) {
                sidebar.classList.remove("open");
            }
        });
    });

    // Restore active section on page load
    var savedSection = localStorage.getItem('periodaActiveSection');
    if (savedSection) {
        var savedNavBtn = document.querySelector('.nav-item[data-section="' + savedSection + '"]');
        if (savedNavBtn) {
            savedNavBtn.click();
        }
    }

    /* Quick action buttons navigate to sections */
    document.querySelectorAll(".quick-action-btn").forEach(function (btn) {
        btn.addEventListener("click", function () {
            var target = btn.dataset.goto;
            var navBtn = document.querySelector('.nav-item[data-section="' + target + '"]');
            if (navBtn) navBtn.click();
        });
    });

    /* Mobile sidebar toggle */
    sidebarToggle.addEventListener("click", function () {
        sidebar.classList.toggle("open");
    });


    /* =========================
       LOGOUT
    ========================= */

    logoutBtn.addEventListener("click", function () {
        fetch("api/logout.php", { credentials: "same-origin" })
            .then(function () {
                window.location.href = "login.html";
            })
            .catch(function () {
                window.location.href = "login.html";
            });
    });


    /* =========================================
       CALENDAR
       ========================================= */

    function renderCalendar() {
        calendarMonth.textContent = MONTH_NAMES[currentMonth] + " " + currentYear;

        calendarGrid.innerHTML = "";

        var firstDay = new Date(currentYear, currentMonth, 1).getDay();
        var daysInMonth = new Date(currentYear, currentMonth + 1, 0).getDate();

        var today = new Date();
        var todayDate = today.getDate();
        var todayMonth = today.getMonth();
        var todayYear = today.getFullYear();

        /* Empty cells for alignment */
        for (var i = 0; i < firstDay; i++) {
            var emptyEl = document.createElement("div");
            emptyEl.className = "cal-day empty";
            calendarGrid.appendChild(emptyEl);
        }

        /* Day cells */
        for (var d = 1; d <= daysInMonth; d++) {
            var dayEl = document.createElement("div");
            dayEl.className = "cal-day";
            dayEl.textContent = d;

            /* Check if today */
            if (d === todayDate && currentMonth === todayMonth && currentYear === todayYear) {
                dayEl.classList.add("today");
            }

            /* Check if period day */
            var dateStr = currentYear + "-" +
                String(currentMonth + 1).padStart(2, "0") + "-" +
                String(d).padStart(2, "0");

            if (isPeriodDay(dateStr)) {
                dayEl.classList.add("period");
            } else if (isPredictedPeriodDay(dateStr)) {
                dayEl.classList.add("predicted");
            } 
            
            var isFertilityEnabled = localStorage.getItem("fertilityEnabled") !== "false";
            
            if (isFertilityEnabled) {
                if (isOvulationDay(dateStr)) {
                    dayEl.classList.add("ovulation");
                } else if (isFertileDay(dateStr)) {
                    dayEl.classList.add("fertile");
                } else if (isPredictedOvulationDay(dateStr)) {
                    dayEl.classList.add("predicted-ovulation");
                } else if (isPredictedFertileDay(dateStr)) {
                    dayEl.classList.add("predicted-fertile");
                }
                
                // Add custom fertility logs
                if (fertilityLogs[dateStr]) {
                    const log = fertilityLogs[dateStr];
                    let markersHtml = '<div style="display: flex; gap: 2px; justify-content: center; margin-top: 2px;">';
                    
                    if (log.ovulationTest) {
                        if (log.ovulationTest === "positive") markersHtml += '<span style="font-size: 10px;" title="Positive Ovulation Test">➕</span>';
                        else if (log.ovulationTest === "negative") markersHtml += '<span style="font-size: 10px;" title="Negative Ovulation Test">➖</span>';
                    }
                    
                    if (log.sexualActivity) {
                        markersHtml += '<span style="font-size: 10px;" title="Sexual Activity">❤️</span>';
                    }
                    
                    markersHtml += '</div>';
                    if (markersHtml !== '<div style="display: flex; gap: 2px; justify-content: center; margin-top: 2px;"></div>') {
                        dayEl.innerHTML += markersHtml;
                    }
                }
            }

            dayEl.dataset.date = dateStr;

            calendarGrid.appendChild(dayEl);
        }
    }


    function isPeriodDay(dateStr) {
        var check = new Date(dateStr + "T00:00:00");
        for (var i = 0; i < cycleData.length; i++) {
            var cycle = cycleData[i];
            var start = new Date(cycle.start_date + "T00:00:00");
            var end = cycle.end_date ? new Date(cycle.end_date + "T00:00:00") : start;
            if (check >= start && check <= end) {
                return true;
            }
        }
        return false;
    }


    function isPredictedPeriodDay(dateStr) {
        if (!cycleData || cycleData.length === 0) return false;
        
        var mostRecentCycle = cycleData[0];
        for (var i = 1; i < cycleData.length; i++) {
            if (new Date(cycleData[i].start_date) > new Date(mostRecentCycle.start_date)) {
                mostRecentCycle = cycleData[i];
            }
        }
        
        var lastStart = new Date(mostRecentCycle.start_date + "T00:00:00");
        var predictedStart = new Date(lastStart);
        predictedStart.setDate(predictedStart.getDate() + userCycleLength);
        
        var predictedEnd = new Date(predictedStart);
        predictedEnd.setDate(predictedEnd.getDate() + userPeriodDuration - 1);
        
        var check = new Date(dateStr + "T00:00:00");
        return check >= predictedStart && check <= predictedEnd;
    }


    function isOvulationDay(dateStr) {
        if (!cycleData || cycleData.length === 0) return false;
        var check = new Date(dateStr + "T00:00:00");
        
        for (var i = 0; i < cycleData.length; i++) {
            if (cycleData[i].estimated_ovulation_date) {
                var ovDate = new Date(cycleData[i].estimated_ovulation_date + "T00:00:00");
                if (check.getTime() === ovDate.getTime()) {
                    return true;
                }
            }
        }
        return false;
    }


    function isFertileDay(dateStr) {
        if (!cycleData || cycleData.length === 0) return false;
        var check = new Date(dateStr + "T00:00:00");
        
        for (var i = 0; i < cycleData.length; i++) {
            if (cycleData[i].estimated_ovulation_date) {
                var ovDate = new Date(cycleData[i].estimated_ovulation_date + "T00:00:00");
                var startFertile = new Date(ovDate);
                startFertile.setDate(startFertile.getDate() - 5);
                
                if (check >= startFertile && check < ovDate) {
                    return true;
                }
            }
        }
        return false;
    }


    function isPredictedOvulationDay(dateStr) {
        if (!cycleData || cycleData.length === 0) return false;
        
        var mostRecentCycle = cycleData[0];
        for (var i = 1; i < cycleData.length; i++) {
            if (new Date(cycleData[i].start_date) > new Date(mostRecentCycle.start_date)) {
                mostRecentCycle = cycleData[i];
            }
        }
        
        var lastStart = new Date(mostRecentCycle.start_date + "T00:00:00");
        var predictedStart = new Date(lastStart);
        predictedStart.setDate(predictedStart.getDate() + userCycleLength);
        
        var predictedOvulation = new Date(predictedStart);
        predictedOvulation.setDate(predictedOvulation.getDate() + (userCycleLength - 14));
        
        var check = new Date(dateStr + "T00:00:00");
        return check.getTime() === predictedOvulation.getTime();
    }


    function isPredictedFertileDay(dateStr) {
        if (!cycleData || cycleData.length === 0) return false;
        
        var mostRecentCycle = cycleData[0];
        for (var i = 1; i < cycleData.length; i++) {
            if (new Date(cycleData[i].start_date) > new Date(mostRecentCycle.start_date)) {
                mostRecentCycle = cycleData[i];
            }
        }
        
        var lastStart = new Date(mostRecentCycle.start_date + "T00:00:00");
        var predictedStart = new Date(lastStart);
        predictedStart.setDate(predictedStart.getDate() + userCycleLength);
        
        var predictedOvulation = new Date(predictedStart);
        predictedOvulation.setDate(predictedOvulation.getDate() + (userCycleLength - 14));
        
        var predictedFertileStart = new Date(predictedOvulation);
        predictedFertileStart.setDate(predictedFertileStart.getDate() - 5);
        
        var check = new Date(dateStr + "T00:00:00");
        return check >= predictedFertileStart && check < predictedOvulation;
    }


    prevMonthBtn.addEventListener("click", function () {
        currentMonth--;
        if (currentMonth < 0) {
            currentMonth = 11;
            currentYear--;
        }
        renderCalendar();
    });

    nextMonthBtn.addEventListener("click", function () {
        currentMonth++;
        if (currentMonth > 11) {
            currentMonth = 0;
            currentYear++;
        }
        renderCalendar();
    });


    /* =========================
       LOG PERIOD
    ========================= */

    periodStartInput.addEventListener("change", function () {
        if (this.value) {
            var startParts = this.value.split("-");
            var start = new Date(startParts[0], startParts[1] - 1, startParts[2]);
            start.setDate(start.getDate() + 1);
            var yyyy = start.getFullYear();
            var mm = String(start.getMonth() + 1).padStart(2, '0');
            var dd = String(start.getDate()).padStart(2, '0');
            var nextDay = yyyy + "-" + mm + "-" + dd;
            periodEndInput.min = nextDay;
            if (periodEndInput.value && periodEndInput.value <= this.value) {
                periodEndInput.value = nextDay;
            }
        }
    });

    savePeriodBtn.addEventListener("click", function () {
        var startDate = periodStartInput.value;
        var endDate = periodEndInput.value;

        if (!startDate) {
            alert("Please select a start date.");
            return;
        }
        
        var method = editingCycleId ? "PUT" : "POST";
        var payload = {
            start_date: startDate,
            end_date: endDate || null
        };
        if (editingCycleId) payload.id = editingCycleId;

        fetch("api/cycles.php", { credentials: "same-origin",
            method: method,
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(payload)
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data.success) {
                resetPeriodForm();
                loadCycles();
            } else {
                alert(data.error || "Failed to save period.");
            }
        })
        .catch(function () {
            alert("Could not connect to server.");
        });
    });

    function resetPeriodForm() {
        periodStartInput.value = "";
        periodEndInput.value = "";
        editingCycleId = null;
        savePeriodBtn.textContent = "Save Period";
    }


    /* =========================
       LOAD CYCLES
    ========================= */

    function loadCycles() {
        fetch("api/cycles.php?_t=" + Date.now(), { credentials: "same-origin" })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.success && data.cycles) {
                    cycleData = data.cycles;
                    
                    // Populate sexual activity from cycles
                    var serverSexualActivities = [];
                    cycleData.forEach(function(c) {
                        if (c.sexual_activity_dates) {
                            var dates = c.sexual_activity_dates.split(',');
                            dates.forEach(function(d) {
                                d = d.trim();
                                if (d) {
                                    serverSexualActivities.push(d);
                                    if (!fertilityLogs[d]) fertilityLogs[d] = {};
                                    fertilityLogs[d].sexualActivity = true;
                                }
                            });
                        }
                    });

                    // Auto-sync any local-only sexual activity logs to the server
                    for (var d in fertilityLogs) {
                        if (fertilityLogs[d].sexualActivity && !serverSexualActivities.includes(d)) {
                            fetch("api/log_sexual_activity.php", { credentials: "same-origin",
                                method: "POST",
                                headers: { "Content-Type": "application/json" },
                                body: JSON.stringify({ date: d, action: "add" })
                            });
                        }
                    }

                    localStorage.setItem("perioda_fertility_logs", JSON.stringify(fertilityLogs));

                    var dashFertilityContent = document.getElementById("dashFertilityContent");

                    if (cycleData.length > 0) {
                        // Calculate next predicted ovulation if no active ovulation exists
                        var mostRecentCycle = cycleData[0];
                        for (var i = 1; i < cycleData.length; i++) {
                            if (new Date(cycleData[i].start_date) > new Date(mostRecentCycle.start_date)) {
                                mostRecentCycle = cycleData[i];
                            }
                        }
                        
                        var lastStart = new Date(mostRecentCycle.start_date + "T00:00:00");
                        var predictedStart = new Date(lastStart);
                        predictedStart.setDate(predictedStart.getDate() + userCycleLength);
                        
                        var predictedOvulation = new Date(predictedStart);
                        predictedOvulation.setDate(predictedOvulation.getDate() + (userCycleLength - 14));
                        
                        var fertileStart = new Date(predictedOvulation);
                        fertileStart.setDate(fertileStart.getDate() - 5);
                        
                        var ovDateToUse = mostRecentCycle.estimated_ovulation_date ? new Date(mostRecentCycle.estimated_ovulation_date + "T00:00:00") : predictedOvulation;
                        var fertileStartToUse = mostRecentCycle.estimated_ovulation_date ? new Date(mostRecentCycle.estimated_ovulation_date + "T00:00:00") : fertileStart;
                        if(mostRecentCycle.estimated_ovulation_date) fertileStartToUse.setDate(fertileStartToUse.getDate() - 5);
                        
                        // Check if the most recent ovulation is in the past, if so use predicted
                        var today = new Date();
                        today.setHours(0, 0, 0, 0);
                        if (ovDateToUse < today && predictedOvulation >= today) {
                            ovDateToUse = predictedOvulation;
                            fertileStartToUse = fertileStart;
                        }
                        
                        // Update Main Dashboard Insights Card
                        if (dashFertilityContent) {
                            var options = { month: 'short', day: 'numeric' };
                            var fStartStr = fertileStartToUse.toLocaleDateString(undefined, options);
                            var ovStr = ovDateToUse.toLocaleDateString(undefined, options);
                            
                            dashFertilityContent.innerHTML = `
                                <div style="display: flex; flex-direction: column; gap: 12px;">
                                    <div style="background: white; border-radius: 12px; padding: 12px 16px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                                        <div>
                                            <span style="display: block; font-size: 13px; color: #831843; font-weight: 500; margin-bottom: 4px;">Fertile Window</span>
                                            <span style="font-size: 16px; color: #5a4b52; font-weight: 600;">${fStartStr} - ${ovStr}</span>
                                        </div>
                                        <div style="font-size: 24px;">✨</div>
                                    </div>
                                    <div style="background: white; border-radius: 12px; padding: 12px 16px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                                        <div>
                                            <span style="display: block; font-size: 13px; color: #831843; font-weight: 500; margin-bottom: 4px;">Est. Ovulation Day</span>
                                            <span style="font-size: 16px; color: #5a4b52; font-weight: 600;">${ovStr}</span>
                                        </div>
                                        <div style="font-size: 24px;">🥚</div>
                                    </div>
                                    <p style="font-size: 12px; color: #be185d; margin: 0; font-style: italic;">*These are estimates based on your cycle length.</p>
                                </div>
                            `;
                        }
                        
                    } else {
                        if (dashFertilityContent) dashFertilityContent.innerHTML = '<p style="color: #9d174d; font-size: 15px;">Log a period to calculate your fertile window.</p>';
                    }
                    loadFertilityPageData(); // Refresh the fertility page as well
                }
                renderCalendar();
                renderCycleHistory();
            })
            .catch(function () {
                renderCalendar();
                renderCycleHistory();
            });
    }


    function renderCycleHistory() {
        if (cycleData.length === 0) {
            cycleHistoryEl.innerHTML = '<p class="empty-state">No cycles logged yet.</p>';
            return;
        }

        var html = "";
        cycleData.forEach(function (cycle) {
            var startDate = new Date(cycle.start_date + "T00:00:00");
            var startStr = startDate.toLocaleDateString("en-US", { month: "short", day: "numeric", year: "numeric" });

            var endStr = "Ongoing";
            var days = "—";
            if (cycle.end_date) {
                var endDate = new Date(cycle.end_date + "T00:00:00");
                endStr = endDate.toLocaleDateString("en-US", { month: "short", day: "numeric", year: "numeric" });
                var diff = Math.round((endDate - startDate) / (1000 * 60 * 60 * 24)) + 1;
                days = diff + " day" + (diff !== 1 ? "s" : "");
            }

            html += '<div class="cycle-history-item">' +
                '<div class="cycle-dates">' +
                    '<strong>' + startStr + ' — ' + endStr + '</strong>' +
                    '<span>Period duration: ' + days + '</span>' +
                '</div>' +
                '<span class="cycle-length-badge">' + days + '</span>' +
                '<div class="cycle-actions">' +
                    '<button class="edit-btn" data-cycle-id="' + cycle.id + '" title="Edit">✎</button>' +
                    '<button class="delete-btn" data-cycle-id="' + cycle.id + '" title="Delete">✕</button>' +
                '</div>' +
            '</div>';
        });

        cycleHistoryEl.innerHTML = html;

        /* Edit buttons */
        cycleHistoryEl.querySelectorAll(".edit-btn").forEach(function (btn) {
            btn.addEventListener("click", function () {
                var id = btn.dataset.cycleId;
                var cycle = cycleData.find(c => String(c.id) === String(id));
                if (cycle) {
                    periodStartInput.value = cycle.start_date;
                    periodEndInput.value = cycle.end_date || "";
                    
                    var startParts = cycle.start_date.split("-");
                    var start = new Date(startParts[0], startParts[1] - 1, startParts[2]);
                    start.setDate(start.getDate() + 1);
                    var yyyy = start.getFullYear();
                    var mm = String(start.getMonth() + 1).padStart(2, '0');
                    var dd = String(start.getDate()).padStart(2, '0');
                    periodEndInput.min = yyyy + "-" + mm + "-" + dd;
                    
                    editingCycleId = cycle.id;
                    savePeriodBtn.textContent = "Update Period";
                    periodStartInput.focus();
                }
            });
        });

        /* Delete buttons */
        cycleHistoryEl.querySelectorAll(".delete-btn").forEach(function (btn) {
            btn.addEventListener("click", function () {
                var id = btn.dataset.cycleId;
                if (confirm("Delete this cycle entry?")) {
                    deleteCycle(id, btn);
                }
            });
        });
    }


    function deleteCycle(id, btnElement) {
        fetch("api/cycles.php", { credentials: "same-origin",
            method: "DELETE",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ id: id })
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data.success) {
                if (btnElement) {
                    var card = btnElement.closest(".cycle-history-item");
                    if (card) card.remove();
                }
                alert("Cycle deleted successfully.");
                loadCycles(); // Re-fetch to update predictions
            } else {
                alert(data.error || "Failed to delete cycle.");
            }
        })
        .catch(function () {
            /* Offline fallback */
            cycleData = cycleData.filter(function (c) { return String(c.id) !== String(id); });
            renderCalendar();
            renderCycleHistory();
        });
    }


    /* =========================================
       MOOD BOARD
       ========================================= */

    var MOOD_EMOJIS = {
        happy: "😊",
        calm: "😌",
        energetic: "⚡",
        sad: "😢",
        anxious: "😰",
        irritable: "😤",
        tired: "😴",
        loved: "🥰"
    };

    /* Select mood */
    moodPicker.querySelectorAll(".mood-btn").forEach(function (btn) {
        btn.addEventListener("click", function () {
            moodPicker.querySelectorAll(".mood-btn").forEach(function (b) { b.classList.remove("selected"); });
            btn.classList.add("selected");
            selectedMood = btn.dataset.mood;
            moodIntensityRow.style.display = "flex";
        });
    });

    /* Intensity selection */
    document.querySelectorAll(".intensity-dot").forEach(function (dot) {
        dot.addEventListener("click", function () {
            document.querySelectorAll(".intensity-dot").forEach(function (d) { d.classList.remove("active"); });
            dot.classList.add("active");
            moodIntensity = parseInt(dot.dataset.val);
        });
    });

    /* Save mood */
    saveMoodBtn.addEventListener("click", function () {
        if (!selectedMood) {
            alert("Please select a mood first.");
            return;
        }

        var method = editingMoodId ? "PUT" : "POST";
        var payload = {
            mood: selectedMood,
            intensity: moodIntensity,
            log_date: new Date().toISOString().slice(0, 10)
        };
        if (editingMoodId) payload.id = editingMoodId;

        fetch("api/moods.php", { credentials: "same-origin",
            method: method,
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(payload)
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data.success) {
                selectedMood = "";
                moodIntensity = 3;
                editingMoodId = null;
                saveMoodBtn.textContent = "Log Mood";
                moodIntensityRow.style.display = "none";
                moodPicker.querySelectorAll(".mood-btn").forEach(function (b) { b.classList.remove("selected"); });
                document.querySelectorAll(".intensity-dot").forEach(function (d) { 
                    d.classList.remove("active");
                    if (d.dataset.val === "3") d.classList.add("active");
                });
                loadMoodHistory();
            } else {
                alert(data.error || "Failed to save mood.");
            }
        })
        .catch(function () {
            alert("Could not connect to server.");
        });
    });


    function loadMoodHistory() {
        fetch("api/moods.php?_t=" + Date.now(), { credentials: "same-origin" })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.success && data.moods && data.moods.length > 0) {
                    renderMoodHistory(data.moods);
                } else {
                    moodHistoryEl.innerHTML = '<p class="empty-state">No moods logged yet.</p>';
                }
            })
            .catch(function () {
                moodHistoryEl.innerHTML = '<p class="empty-state">Connect to server to view mood history.</p>';
            });
    }


    function renderMoodHistory(moods) {
        var html = "";
        moods.forEach(function (entry) {
            var emoji = MOOD_EMOJIS[entry.mood] || "🙂";
            var dateObj = new Date(entry.log_date + "T00:00:00");
            var dateStr = dateObj.toLocaleDateString("en-US", { credentials: "same-origin", month: "short", day: "numeric", year: "numeric" });

            html += '<div class="history-item">' +
                '<div class="history-emoji">' + emoji + '</div>' +
                '<div class="history-details" style="flex:1;">' +
                    '<strong>' + entry.mood + '</strong>' +
                    '<span>' + dateStr + '</span>' +
                '</div>' +
                '<span class="history-badge badge-intensity">Intensity: ' + entry.intensity + '/5</span>' +
                '<div class="cycle-actions" style="margin-left: 10px;">' +
                    '<button class="edit-btn edit-mood-btn" data-id="' + entry.id + '" data-mood="' + entry.mood + '" data-intensity="' + entry.intensity + '" title="Edit">✎</button>' +
                    '<button class="delete-btn delete-mood-btn" data-id="' + entry.id + '" title="Delete">✕</button>' +
                '</div>' +
            '</div>';
        });
        moodHistoryEl.innerHTML = html;

        moodHistoryEl.querySelectorAll(".edit-mood-btn").forEach(function (btn) {
            btn.addEventListener("click", function () {
                var id = btn.dataset.id;
                var mood = btn.dataset.mood;
                var intensity = btn.dataset.intensity;
                
                editingMoodId = id;
                saveMoodBtn.textContent = "Update Mood";
                selectedMood = mood;
                moodIntensityRow.style.display = "flex";
                
                moodPicker.querySelectorAll(".mood-btn").forEach(function (b) { 
                    b.classList.toggle("selected", b.dataset.mood === mood);
                });
                
                document.querySelectorAll(".intensity-dot").forEach(function (d) { 
                    d.classList.toggle("active", d.dataset.val === String(intensity));
                });
                moodIntensity = parseInt(intensity);
                saveMoodBtn.scrollIntoView({ behavior: 'smooth', block: 'center' });
            });
        });

        moodHistoryEl.querySelectorAll(".delete-mood-btn").forEach(function (btn) {
            btn.addEventListener("click", function () {
                var id = btn.dataset.id;
                if (confirm("Delete this mood log?")) {
                    fetch("api/moods.php", { credentials: "same-origin",
                        method: "DELETE",
                        headers: { "Content-Type": "application/json" },
                        body: JSON.stringify({ id: id })
                    })
                    .then(function (r) { return r.json(); })
                    .then(function (data) {
                        if (data.success) {
                            var card = btn.closest(".history-item");
                            if (card) card.remove();
                            alert("Mood deleted.");
                            loadMoodHistory();
                        } else {
                            alert(data.error || "Failed to delete.");
                        }
                    })
                    .catch(function () {
                        alert("Network error occurred.");
                    });
                }
            });
        });
    }


    /* =========================================
       SYMPTOM BOARD
       ========================================= */

    var SYMPTOM_EMOJIS = {
        cramps: "🤕",
        headache: "🤯",
        bloating: "🫧",
        fatigue: "😩",
        acne: "😣",
        "back pain": "🔙",
        nausea: "🤢",
        "breast tenderness": "💗"
    };

    /* Select symptom */
    symptomPicker.querySelectorAll(".symptom-btn").forEach(function (btn) {
        btn.addEventListener("click", function () {
            symptomPicker.querySelectorAll(".symptom-btn").forEach(function (b) { b.classList.remove("selected"); });
            btn.classList.add("selected");
            selectedSymptom = btn.dataset.symptom;
            severityRow.style.display = "flex";
        });
    });

    /* Severity selection */
    document.querySelectorAll(".severity-btn").forEach(function (btn) {
        btn.addEventListener("click", function () {
            document.querySelectorAll(".severity-btn").forEach(function (b) { b.classList.remove("active"); });
            btn.classList.add("active");
            selectedSeverity = btn.dataset.severity;
        });
    });

    /* Save symptom */
    saveSymptomBtn.addEventListener("click", function () {
        if (!selectedSymptom) {
            alert("Please select a symptom first.");
            return;
        }

        var method = editingSymptomId ? "PUT" : "POST";
        var payload = {
            symptom: selectedSymptom,
            severity: selectedSeverity,
            log_date: new Date().toISOString().slice(0, 10)
        };
        if (editingSymptomId) payload.id = editingSymptomId;

        fetch("api/symptoms.php", { credentials: "same-origin",
            method: method,
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(payload)
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data.success) {
                selectedSymptom = "";
                selectedSeverity = "mild";
                editingSymptomId = null;
                saveSymptomBtn.textContent = "Log Symptom";
                severityRow.style.display = "none";
                symptomPicker.querySelectorAll(".symptom-btn").forEach(function (b) { b.classList.remove("selected"); });
                document.querySelectorAll(".severity-btn").forEach(function (b) {
                    b.classList.remove("active");
                    if (b.dataset.severity === "mild") b.classList.add("active");
                });
                loadSymptomHistory();
            } else {
                alert(data.error || "Failed to save symptom.");
            }
        })
        .catch(function () {
            alert("Could not connect to server.");
        });
    });


    function loadSymptomHistory() {
        fetch("api/symptoms.php?_t=" + Date.now(), { credentials: "same-origin" })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.success && data.symptoms && data.symptoms.length > 0) {
                    renderSymptomHistory(data.symptoms);
                } else {
                    symptomHistoryEl.innerHTML = '<p class="empty-state">No symptoms logged yet.</p>';
                }
            })
            .catch(function () {
                symptomHistoryEl.innerHTML = '<p class="empty-state">Connect to server to view symptom history.</p>';
            });
    }


    function renderSymptomHistory(symptoms) {
        var html = "";
        symptoms.forEach(function (entry) {
            var emoji = SYMPTOM_EMOJIS[entry.symptom] || "🩺";
            var dateObj = new Date(entry.log_date + "T00:00:00");
            var dateStr = dateObj.toLocaleDateString("en-US", { credentials: "same-origin", month: "short", day: "numeric", year: "numeric" });

            var badgeClass = "badge-mild";
            if (entry.severity === "moderate") badgeClass = "badge-moderate";
            if (entry.severity === "severe") badgeClass = "badge-severe";

            html += '<div class="history-item">' +
                '<div class="history-emoji">' + emoji + '</div>' +
                '<div class="history-details" style="flex:1;">' +
                    '<strong>' + entry.symptom + '</strong>' +
                    '<span>' + dateStr + '</span>' +
                '</div>' +
                '<span class="history-badge ' + badgeClass + '">' + entry.severity + '</span>' +
                '<div class="cycle-actions" style="margin-left: 10px;">' +
                    '<button class="edit-btn edit-symptom-btn" data-id="' + entry.id + '" data-symptom="' + entry.symptom + '" data-severity="' + entry.severity + '" title="Edit">✎</button>' +
                    '<button class="delete-btn delete-symptom-btn" data-id="' + entry.id + '" title="Delete">✕</button>' +
                '</div>' +
            '</div>';
        });
        symptomHistoryEl.innerHTML = html;

        symptomHistoryEl.querySelectorAll(".edit-symptom-btn").forEach(function (btn) {
            btn.addEventListener("click", function () {
                var id = btn.dataset.id;
                var symptom = btn.dataset.symptom;
                var severity = btn.dataset.severity;
                
                editingSymptomId = id;
                saveSymptomBtn.textContent = "Update Symptom";
                selectedSymptom = symptom;
                severityRow.style.display = "flex";
                
                symptomPicker.querySelectorAll(".symptom-btn").forEach(function (b) { 
                    b.classList.toggle("selected", b.dataset.symptom === symptom);
                });
                
                document.querySelectorAll(".severity-btn").forEach(function (b) { 
                    b.classList.toggle("active", b.dataset.severity === severity);
                });
                selectedSeverity = severity;
                saveSymptomBtn.scrollIntoView({ behavior: 'smooth', block: 'center' });
            });
        });

        symptomHistoryEl.querySelectorAll(".delete-symptom-btn").forEach(function (btn) {
            btn.addEventListener("click", function () {
                var id = btn.dataset.id;
                if (confirm("Delete this symptom log?")) {
                    fetch("api/symptoms.php", { credentials: "same-origin",
                        method: "DELETE",
                        headers: { "Content-Type": "application/json" },
                        body: JSON.stringify({ id: id })
                    })
                    .then(function (r) { return r.json(); })
                    .then(function (data) {
                        if (data.success) {
                            var card = btn.closest(".history-item");
                            if (card) card.remove();
                            alert("Symptom deleted successfully.");
                            loadSymptomHistory();
                        } else {
                            alert(data.error || "Failed to delete symptom.");
                        }
                    })
                    .catch(function () {
                        alert("Network error occurred.");
                    });
                }
            });
        });
    }


    /* =========================================
       NOTES
       ========================================= */

    saveNoteBtn.addEventListener("click", function () {
        var title = noteTitleInput.value.trim();
        var content = noteContentInput.value.trim();

        if (!title || !content) {
            alert("Please enter both a title and content.");
            return;
        }

        var method = editingNoteId ? "PUT" : "POST";
        var payload = {
            title: title,
            content: content
        };
        if (editingNoteId) payload.id = editingNoteId;

        fetch("api/notes.php", { credentials: "same-origin",
            method: method,
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(payload)
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data.success) {
                noteTitleInput.value = "";
                noteContentInput.value = "";
                editingNoteId = null;
                saveNoteBtn.textContent = "Save Note";
                loadNotes();
            } else {
                alert(data.error || "Failed to save note.");
            }
        })
        .catch(function () {
            alert("Could not connect to server.");
        });
    });


    function loadNotes() {
        fetch("api/notes.php?_t=" + Date.now(), { credentials: "same-origin" })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.success && data.notes && data.notes.length > 0) {
                    renderNotes(data.notes);
                } else {
                    notesListEl.innerHTML = '<p class="empty-state">No notes yet.</p>';
                }
            })
            .catch(function () {
                notesListEl.innerHTML = '<p class="empty-state">Connect to server to view notes.</p>';
            });
    }


    function renderNotes(notes) {
        var html = "";
        notes.forEach(function (note) {
            var dateObj = new Date(note.created_at);
            var dateStr = dateObj.toLocaleDateString("en-US", { credentials: "same-origin", month: "short", day: "numeric", year: "numeric" });

            html += '<div class="note-item">' +
                '<div class="note-item-header">' +
                    '<h4>' + escapeHtml(note.title) + '</h4>' +
                    '<span>' + dateStr + '</span>' +
                '</div>' +
                '<p>' + escapeHtml(note.content) + '</p>' +
                '<div class="note-actions">' +
                    '<button class="edit-btn edit-note-btn" data-id="' + note.id + '" data-title="' + escapeHtml(note.title).replace(/"/g, '&quot;') + '" data-content="' + escapeHtml(note.content).replace(/"/g, '&quot;') + '">✎ Edit</button>' +
                    '<button class="delete-btn" data-note-id="' + note.id + '" title="Delete">✕ Delete</button>' +
                '</div>' +
            '</div>';
        });
        notesListEl.innerHTML = html;

        notesListEl.querySelectorAll(".edit-note-btn").forEach(function (btn) {
            btn.addEventListener("click", function () {
                var id = btn.dataset.id;
                var title = btn.dataset.title;
                var content = btn.dataset.content;
                
                editingNoteId = id;
                saveNoteBtn.textContent = "Update Note";
                noteTitleInput.value = title;
                noteContentInput.value = content;
                noteTitleInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
            });
        });

        notesListEl.querySelectorAll(".delete-btn").forEach(function (btn) {
            btn.addEventListener("click", function () {
                var id = btn.dataset.noteId;
                if (confirm("Delete this note?")) {
                    deleteNote(id, btn);
                }
            });
        });
    }


    function deleteNote(id, btnElement) {
        fetch("api/notes.php", { credentials: "same-origin",
            method: "DELETE",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ id: id })
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data.success) {
                if (btnElement) {
                    var card = btnElement.closest(".note-card");
                    if (card) card.remove();
                }
                alert("Note deleted successfully.");
                loadNotes();
            } else {
                alert(data.error || "Failed to delete note.");
            }
        })
        .catch(function () {
            alert("Could not connect to server.");
        });
    }


    function escapeHtml(text) {
        var div = document.createElement("div");
        div.textContent = text;
        return div.innerHTML;
    }


    /* =========================================
       SETTINGS
       ========================================= */

    function loadSettings() {
        var enableFertilityCheckbox = document.getElementById("enableFertility");
        var navItemFertility = document.getElementById("navItemFertility");
        
        var isFertilityEnabled = localStorage.getItem("fertilityEnabled");
        if (isFertilityEnabled === "false") {
            if (enableFertilityCheckbox) enableFertilityCheckbox.checked = false;
            if (navItemFertility) navItemFertility.style.display = "none";
        } else {
            if (enableFertilityCheckbox) enableFertilityCheckbox.checked = true;
            if (navItemFertility) navItemFertility.style.display = "block";
        }

        fetch("api/settings.php", { credentials: "same-origin" })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.success && data.settings) {
                    userCycleLength = parseInt(data.settings.cycle_length) || 28;
                    userPeriodDuration = parseInt(data.settings.period_duration) || 5;
                    cycleLengthInput.value = userCycleLength;
                    periodDurationInput.value = userPeriodDuration;
                    renderCalendar();
                }
            })
            .catch(function () {
                /* Use defaults */
            });
    }


    saveSettingsBtn.addEventListener("click", function () {
        var cycleLength = parseInt(cycleLengthInput.value);
        var periodDuration = parseInt(periodDurationInput.value);

        var enableFertilityCheckbox = document.getElementById("enableFertility");
        var navItemFertility = document.getElementById("navItemFertility");
        if (enableFertilityCheckbox) {
            var isEnabled = enableFertilityCheckbox.checked;
            localStorage.setItem("fertilityEnabled", isEnabled ? "true" : "false");
            if (navItemFertility) {
                navItemFertility.style.display = isEnabled ? "block" : "none";
                
                if (!isEnabled && document.getElementById("section-fertility") && document.getElementById("section-fertility").classList.contains("active")) {
                    var dashBtn = document.querySelector('.nav-item[data-section="dashboard"]');
                    if(dashBtn) dashBtn.click();
                }
                renderCalendar(); // Re-render to show/hide fertility on calendar
            }
        }

        if (isNaN(cycleLength) || cycleLength < 20 || cycleLength > 45) {
            alert("Cycle length must be between 20 and 45 days.");
            return;
        }

        if (isNaN(periodDuration) || periodDuration < 2 || periodDuration > 10) {
            alert("Period duration must be between 2 and 10 days.");
            return;
        }

        fetch("api/settings.php", { credentials: "same-origin",
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({
                cycle_length: cycleLength,
                period_duration: periodDuration
            })
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data.success) {
                settingsStatus.textContent = "✓ Settings saved successfully!";
                setTimeout(function () {
                    settingsStatus.textContent = "";
                }, 3000);
            } else {
                alert(data.error || "Failed to save settings.");
            }
        })
        .catch(function () {
            alert("Could not connect to server.");
        });
    });

    /* =========================================
       FERTILITY SETTINGS
       ========================================= */

    function loadFertilityPageData() {
        const estContent = document.getElementById("fertilityPageEstimatesContent");
        
        if (cycleData.length > 0) {
            var mostRecentCycle = cycleData[0];
            for (var i = 1; i < cycleData.length; i++) {
                if (new Date(cycleData[i].start_date) > new Date(mostRecentCycle.start_date)) {
                    mostRecentCycle = cycleData[i];
                }
            }
            
            var lastStart = new Date(mostRecentCycle.start_date + "T00:00:00");
            var predictedStart = new Date(lastStart);
            predictedStart.setDate(predictedStart.getDate() + userCycleLength);
            
            var predictedOvulation = new Date(predictedStart);
            predictedOvulation.setDate(predictedOvulation.getDate() + (userCycleLength - 14));
            
            var fertileStart = new Date(predictedOvulation);
            fertileStart.setDate(fertileStart.getDate() - 5);
            
            var ovDateToUse = mostRecentCycle.estimated_ovulation_date ? new Date(mostRecentCycle.estimated_ovulation_date + "T00:00:00") : predictedOvulation;
            var fertileStartToUse = mostRecentCycle.estimated_ovulation_date ? new Date(mostRecentCycle.estimated_ovulation_date + "T00:00:00") : fertileStart;
            if(mostRecentCycle.estimated_ovulation_date) fertileStartToUse.setDate(fertileStartToUse.getDate() - 5);
            
            var today = new Date();
            today.setHours(0, 0, 0, 0);
            if (ovDateToUse < today && predictedOvulation >= today) {
                ovDateToUse = predictedOvulation;
                fertileStartToUse = fertileStart;
            }
            
            if (estContent) {
                var options = { month: 'short', day: 'numeric', year: 'numeric' };
                var fStartStr = fertileStartToUse.toLocaleDateString(undefined, options);
                var ovStr = ovDateToUse.toLocaleDateString(undefined, options);
                
                estContent.innerHTML = `
                    <div style="display: flex; flex-direction: column; gap: 12px;">
                        <div style="background: white; border-radius: 12px; padding: 12px 16px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                            <div>
                                <span style="display: block; font-size: 13px; color: #831843; font-weight: 500; margin-bottom: 4px;">Fertile Window</span>
                                <span style="font-size: 16px; color: #5a4b52; font-weight: 600;">${fStartStr} - ${ovStr}</span>
                            </div>
                            <div style="font-size: 24px;">✨</div>
                        </div>
                        <div style="background: white; border-radius: 12px; padding: 12px 16px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                            <div>
                                <span style="display: block; font-size: 13px; color: #831843; font-weight: 500; margin-bottom: 4px;">Est. Ovulation Day</span>
                                <span style="font-size: 16px; color: #5a4b52; font-weight: 600;">${ovStr}</span>
                            </div>
                            <div style="font-size: 24px;">🥚</div>
                        </div>
                        <p style="font-size: 12px; color: #be185d; margin: 0; font-style: italic;">*These are estimates based on your cycle data. They are not medically confirmed.</p>
                    </div>
                `;
            }
        } else {
            if (estContent) {
                estContent.innerHTML = '<p style="color: #9d174d; font-size: 15px;">Log a period to calculate your fertile window.</p>';
            }
        }
        
        renderSexualActivityHistory();
    }

    function renderSexualActivityHistory() {
        const historyList = document.getElementById("sexualActivityHistoryList");
        if (!historyList) return;
        
        let html = '';
        
        // Convert logs object to array and sort by date descending
        const logsArray = [];
        for (const [date, log] of Object.entries(fertilityLogs)) {
            if (log.sexualActivity) {
                logsArray.push({ date: date, ...log });
            }
        }
        
        logsArray.sort((a, b) => new Date(b.date) - new Date(a.date));
        
        if (logsArray.length === 0) {
            html = '<p class="empty-state">No activity logged yet.</p>';
        } else {
            logsArray.forEach(log => {
                const dateObj = new Date(log.date + "T00:00:00");
                const options = { weekday: 'short', month: 'short', day: 'numeric', year: 'numeric' };
                const displayDate = dateObj.toLocaleDateString(undefined, options);
                
                html += `
                    <div class="fertility-log-card" style="display: flex; justify-content: space-between; align-items: center; padding: 16px; border: 1px solid #fbcfe8; border-radius: 12px; background: white;">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <div style="font-size: 20px;">❤️</div>
                            <div>
                                <h4 style="margin: 0 0 4px 0; font-size: 15px; color: #5a4b52;">Sexual Activity</h4>
                                <span style="font-size: 13px; color: #806875;">${displayDate}</span>
                            </div>
                        </div>
                        <div style="display: flex; gap: 8px;">
                            <button class="btn-icon" onclick="editSexualActivity('${log.date}')" title="Edit" style="background: none; border: none; cursor: pointer; color: #5a4b52;">✏️</button>
                            <button class="btn-icon" onclick="deleteSexualActivity('${log.date}', this)" title="Delete" style="background: none; border: none; cursor: pointer; color: #5a4b52;">🗑️</button>
                        </div>
                    </div>
                `;
            });
            
            historyList.innerHTML = html;
        }
    }

    // Expose edit/delete to global scope for onclick handlers
    window.editSexualActivity = function(dateStr) {
        var dateInput = document.getElementById("sexualActivityDateInput");
        if (dateInput) {
            dateInput.value = dateStr;
            dateInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    };
    
    window.deleteSexualActivity = function(dateStr, btnElement) {
        if (confirm("Are you sure you want to delete this log?")) {
            fetch("api/log_sexual_activity.php", { credentials: "same-origin",
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ date: dateStr, action: "remove" })
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.success) {
                    if (fertilityLogs[dateStr]) {
                        delete fertilityLogs[dateStr].sexualActivity;
                        // Clean up empty logs
                        if (Object.keys(fertilityLogs[dateStr]).length === 0) {
                            delete fertilityLogs[dateStr];
                        }
                        localStorage.setItem("perioda_fertility_logs", JSON.stringify(fertilityLogs));
                    }
                    if (btnElement) {
                        var card = btnElement.closest(".fertility-log-card");
                        if (card) card.remove();
                    }
                    alert("Activity deleted successfully.");
                    renderSexualActivityHistory();
                    renderCalendar();
                } else {
                    alert("Error: " + (data.error || "Failed to delete log."));
                }
            })
            .catch(function() {
                alert("Network error.");
            });
        }
    };

    const btnSaveSexualActivity = document.getElementById("btnSaveSexualActivity");
    if (btnSaveSexualActivity) {
        btnSaveSexualActivity.addEventListener("click", function() {
            const dateInput = document.getElementById("sexualActivityDateInput").value;
            if (!dateInput) {
                alert("Please select a date.");
                return;
            }
            
            fetch("api/log_sexual_activity.php", { credentials: "same-origin",
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ date: dateInput, action: "add" })
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.success) {
                    if (!fertilityLogs[dateInput]) {
                        fertilityLogs[dateInput] = {};
                    }
                    
                    fertilityLogs[dateInput].sexualActivity = true;
                    localStorage.setItem("perioda_fertility_logs", JSON.stringify(fertilityLogs));
                    
                    document.getElementById("sexualActivityDateInput").value = "";
                    renderSexualActivityHistory();
                    renderCalendar();
                } else {
                    alert("Error: " + (data.error || "Failed to save log."));
                }
            })
            .catch(function() {
                alert("Network error.");
            });
        });
    }


    /* =========================================
       DASHBOARD STATS
       ========================================= */

    function loadDashboardStats() {

        var dashRecentActivity = document.getElementById("dashRecentActivity");

        /* Load settings for stat cards */
        fetch("api/settings.php", { credentials: "same-origin" })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.success && data.settings) {
                    var cl = document.getElementById("statCycleLength");
                    var pd = document.getElementById("statPeriodDuration");
                    if (cl) cl.textContent = data.settings.cycle_length || 28;
                    if (pd) pd.textContent = data.settings.period_duration || 5;
                }
            })
            .catch(function () {});

        /* Load mood count */
        fetch("api/moods.php", { credentials: "same-origin" })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.success && data.moods) {
                    var el = document.getElementById("statMoodCount");
                    if (el) el.textContent = data.moods.length;
                }
            })
            .catch(function () {});

        /* Load symptom count */
        fetch("api/symptoms.php", { credentials: "same-origin" })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.success && data.symptoms) {
                    var el = document.getElementById("statSymptomCount");
                    if (el) el.textContent = data.symptoms.length;
                }
            })
            .catch(function () {});

        /* Load recent activity from moods + symptoms combined */
        var recentItems = [];

        Promise.all([
            fetch("api/moods.php", { credentials: "same-origin" }).then(function (r) { return r.json(); }).catch(function () { return { moods: [] }; }),
            fetch("api/symptoms.php", { credentials: "same-origin" }).then(function (r) { return r.json(); }).catch(function () { return { symptoms: [] }; }),
            fetch("api/cycles.php", { credentials: "same-origin" }).then(function (r) { return r.json(); }).catch(function () { return { cycles: [] }; })
        ]).then(function (results) {

            var moods = (results[0].moods || []).slice(0, 5);
            var symptoms = (results[1].symptoms || []).slice(0, 5);
            var cycles = (results[2].cycles || []).slice(0, 3);

            var DASH_MOOD_EMOJIS = {
                happy: "😊", calm: "😌", energetic: "⚡", sad: "😢",
                anxious: "😰", irritable: "😤", tired: "😴", loved: "🥰"
            };

            var DASH_SYMPTOM_EMOJIS = {
                cramps: "🤕", headache: "🤯", bloating: "🫧", fatigue: "😩",
                acne: "😣", "back pain": "🔙", nausea: "🤢", "breast tenderness": "💗"
            };

            moods.forEach(function (m) {
                recentItems.push({
                    emoji: DASH_MOOD_EMOJIS[m.mood] || "🙂",
                    label: "Mood: " + m.mood,
                    date: m.log_date,
                    type: "mood"
                });
            });

            symptoms.forEach(function (s) {
                recentItems.push({
                    emoji: DASH_SYMPTOM_EMOJIS[s.symptom] || "🩺",
                    label: "Symptom: " + s.symptom + " (" + s.severity + ")",
                    date: s.log_date,
                    type: "symptom"
                });
            });

            cycles.forEach(function (c) {
                var startDate = new Date(c.start_date + "T00:00:00");
                var str = startDate.toLocaleDateString("en-US", { month: "short", day: "numeric" });
                recentItems.push({
                    emoji: "🩸",
                    label: "Period started: " + str,
                    date: c.start_date,
                    type: "cycle"
                });
            });

            /* Sort by date desc */
            recentItems.sort(function (a, b) {
                return new Date(b.date) - new Date(a.date);
            });

            /* Show top 8 */
            recentItems = recentItems.slice(0, 8);

            if (recentItems.length === 0) {
                dashRecentActivity.innerHTML = '<p class="empty-state">No activity yet. Start logging your moods, symptoms, and periods!</p>';
                return;
            }

            var html = "";
            recentItems.forEach(function (item) {
                var dateObj = new Date(item.date + "T00:00:00");
                var dateStr = dateObj.toLocaleDateString("en-US", { month: "short", day: "numeric", year: "numeric" });

                html += '<div class="history-item">' +
                    '<div class="history-emoji">' + item.emoji + '</div>' +
                    '<div class="history-details">' +
                        '<strong>' + item.label + '</strong>' +
                        '<span>' + dateStr + '</span>' +
                    '</div>' +
                '</div>';
            });

            dashRecentActivity.innerHTML = html;

            /* Update cycle info text */
            if (cycles.length > 0) {
                var lastCycle = cycles[0];
                var lastStart = new Date(lastCycle.start_date + "T00:00:00");
                var today = new Date();
                today.setHours(0, 0, 0, 0);
                
                var predictedStart = new Date(lastStart);
                predictedStart.setDate(predictedStart.getDate() + userCycleLength);
                
                var predictedEnd = new Date(predictedStart);
                predictedEnd.setDate(predictedEnd.getDate() + userPeriodDuration - 1);

                var options = { month: 'short', day: 'numeric' };
                var pStartStr = predictedStart.toLocaleDateString("en-US", options);
                var pEndStr = predictedEnd.toLocaleDateString("en-US", options);

                var daysUntilNext = Math.round((predictedStart - today) / (1000 * 60 * 60 * 24));
                var daysSince = Math.round((today - lastStart) / (1000 * 60 * 60 * 24));
                
                var infoEl = document.getElementById("dashCycleInfo");
                if (infoEl) {
                    var predictionText = "Your next period is predicted for <strong>" + pStartStr + " – " + pEndStr + "</strong>.";
                    
                    if (daysSince === 0) {
                        infoEl.innerHTML = "Your period started today. Take care of yourself! 💕<br>" + predictionText;
                    } else if (daysUntilNext > 0) {
                        infoEl.innerHTML = "It has been " + daysSince + " day" + (daysSince !== 1 ? "s" : "") + " since your last period started.<br>" + predictionText;
                    } else if (daysUntilNext === 0) {
                        infoEl.innerHTML = "It has been " + daysSince + " day" + (daysSince !== 1 ? "s" : "") + " since your last period started.<br>Your next period is <strong>predicted to start today!</strong> (" + pStartStr + " – " + pEndStr + ")";
                    } else {
                        var overdue = Math.abs(daysUntilNext);
                        infoEl.innerHTML = "Your period is <strong>" + overdue + " day" + (overdue !== 1 ? "s" : "") + " late</strong>.<br>It was predicted for " + pStartStr + " – " + pEndStr + ".";
                    }
                }
            }
        });
    }

    /* =========================================
       DYNAMIC HEALTH TIPS
       ========================================= */

    function loadHealthTips() {
        var tipsGrid = document.getElementById("tipsGrid");
        if (!tipsGrid) return;
        
        // Show loading state
        tipsGrid.innerHTML = '<p class="empty-state">Loading personalized health tips...</p>';

        fetch("api/health_tips.php", { credentials: "same-origin" })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.success && data.tips && data.tips.length > 0) {
                    var html = "";
                    
                    // Map categories to icons
                    var categoryIcons = {
                        'Menstrual Hygiene': '💧',
                        'Nutrition': '🥗',
                        'Exercise': '🧘',
                        'Rest and Sleep': '😴',
                        'Period Comfort': '🌿',
                        'General Wellness': '🩺'
                    };

                    data.tips.forEach(function (tip) {
                        var icon = categoryIcons[tip.category] || '💡';
                        html += '<div class="tip-card">' +
                                    '<div class="tip-icon">' + icon + '</div>' +
                                    '<h3>' + tip.title + '</h3>' +
                                    '<p>' + tip.content + '</p>' +
                                '</div>';
                    });
                    tipsGrid.innerHTML = html;
                } else {
                    tipsGrid.innerHTML = '<p class="empty-state">Could not load health tips at this time.</p>';
                }
            })
            .catch(function () {
                tipsGrid.innerHTML = '<p class="empty-state">Could not connect to server to load health tips.</p>';
            });
    }
    /* ==============================================================
       HEALTH TIPS (SYMPTOM ADVISOR)
       ============================================================== */
    const symptomSearchInput = document.getElementById("symptomSearchInput");
    const symptomSearchBtn = document.getElementById("symptomSearchBtn");
    const advisorResultContainer = document.getElementById("advisorResultContainer");

    if (symptomSearchBtn && symptomSearchInput && advisorResultContainer) {
        symptomSearchBtn.addEventListener("click", performSymptomSearch);
        symptomSearchInput.addEventListener("keypress", function(e) {
            if (e.key === "Enter") {
                performSymptomSearch();
            }
        });

        const faqBtns = document.querySelectorAll('.faq-btn');
        faqBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                symptomSearchInput.value = this.textContent;
                performSymptomSearch();
            });
        });

        function performSymptomSearch() {
            const query = symptomSearchInput.value.trim();
            if (!query) {
                alert("Please enter your symptoms to search.");
                return;
            }

            symptomSearchBtn.textContent = "Searching...";
            symptomSearchBtn.disabled = true;
            advisorResultContainer.style.display = "block";
            advisorResultContainer.innerHTML = '<div class="card"><p class="empty-state">Analyzing symptoms...</p></div>';

            fetch("api/symptom_advisor.php?q=" + encodeURIComponent(query) + "&_=" + new Date().getTime(), { credentials: "same-origin" })
                .then(function(response) { return response.json(); })
                .then(function(data) {
                    symptomSearchBtn.textContent = "Search";
                    symptomSearchBtn.disabled = false;

                    if (!data.success) {
                        advisorResultContainer.innerHTML = '<div class="card" style="border-left: 4px solid var(--rose-light);"><h3 style="color:var(--rose-dark);">Error</h3><p>' + (data.error || 'Failed to analyze symptoms.') + '</p></div>';
                        return;
                    }

                    if (!data.condition) {
                        advisorResultContainer.innerHTML = '<div class="card"><p>No specific conditions found for these symptoms. If you are concerned, please consult a healthcare provider.</p></div>';
                        return;
                    }

                    const item = data.condition;
                    let html = '<h3 style="margin-bottom: 1rem;">Possible Condition</h3>';
                    html += '<div class="card" style="border-left: 4px solid var(--rose-light); margin-bottom: 1rem; padding: 1.5rem;">';
                    html += '<h4 style="color: var(--rose-dark); font-size: 1.2rem; margin-bottom: 0.5rem;">' + item.condition_name + '</h4>';
                    html += '<p style="margin-bottom: 1rem; color: var(--text-color);">' + item.description + '</p>';
                    
                    if (item.self_care && item.self_care.length > 0) {
                        html += '<h5 style="margin-bottom: 0.5rem; font-size: 1rem;">Recommended Action:</h5>';
                        html += '<ul style="background: var(--bg-color); padding: 10px 10px 10px 30px; border-radius: 8px; margin-bottom: 1rem;">';
                        item.self_care.forEach(function(sc) { html += '<li style="margin-bottom: 4px;">' + sc + '</li>'; });
                        html += '</ul>';
                    }
                    
                    if (item.warning_signs) {
                        html += '<div style="margin-top: 1rem; color: #d6336c; font-weight: 500;">⚠️ ' + item.warning_signs + '</div>';
                    }
                    html += '</div>';
                    
                    advisorResultContainer.innerHTML = html;
                    loadHealthTipHistory();
                })
                .catch(function() {
                    symptomSearchBtn.textContent = "Search";
                    symptomSearchBtn.disabled = false;
                    advisorResultContainer.innerHTML = '<div class="card" style="border-left: 4px solid var(--rose-light);"><p>A network error occurred. Please try again.</p></div>';
                });
        }
    }

    /* ==============================================================
       ACCORDION LOGIC
       ============================================================== */
    const accordions = document.querySelectorAll('.accordion-header');
    accordions.forEach(function(acc) {
        acc.addEventListener('click', function() {
            // Toggle active class on parent item
            const item = this.parentElement;
            
            // Optional: Close other accordions
            const currentlyActive = document.querySelector('.accordion-item.active');
            if (currentlyActive && currentlyActive !== item) {
                currentlyActive.classList.remove('active');
            }
            
            item.classList.toggle('active');
        });
    });

    // Old modal logic removed

    /* =========================================
       HEALTH TIP HISTORY
       ========================================= */
       
    function loadHealthTipHistory() {
        var listEl = document.getElementById("healthTipHistoryList");
        if (!listEl) return;
        
        listEl.innerHTML = '<p class="empty-state">Loading history...</p>';

        fetch("api/user_health_tip_history.php?_t=" + Date.now(), { credentials: "same-origin" })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.success && data.history) {
                    if (data.history.length === 0) {
                        listEl.innerHTML = '<p class="empty-state">No searches yet.</p>';
                    } else {
                        renderHealthTipHistory(data.history, listEl);
                    }
                } else {
                    listEl.innerHTML = '<p class="empty-state">Could not load search history.</p>';
                }
            })
            .catch(function () {
                listEl.innerHTML = '<p class="empty-state">Could not connect to server.</p>';
            });
    }

    function renderHealthTipHistory(historyData, listEl) {
        var html = "";
        historyData.forEach(function (entry) {
            var dateObj = new Date(entry.created_at);
            var dateStr = dateObj.toLocaleDateString("en-US", { month: "short", day: "numeric", year: "numeric", hour: "numeric", minute: "2-digit" });

            html += '<div class="history-item">' +
                '<div class="history-emoji">🔍</div>' +
                '<div class="history-details" style="flex:1;">' +
                    '<strong>' + entry.condition_name + '</strong>' +
                    '<span>' + dateStr + '</span>' +
                '</div>' +
                '<div class="cycle-actions" style="margin-left: 10px;">' +
                    '<button class="delete-btn delete-history-btn" data-id="' + entry.id + '" title="Delete">✕</button>' +
                '</div>' +
            '</div>';
        });
        listEl.innerHTML = html;

        listEl.querySelectorAll(".delete-history-btn").forEach(function (btn) {
            btn.addEventListener("click", function () {
                var id = btn.dataset.id;
                if (confirm("Delete this search record?")) {
                    fetch("api/user_health_tip_history.php", { credentials: "same-origin",
                        method: "DELETE",
                        headers: { "Content-Type": "application/json" },
                        body: JSON.stringify({ id: id })
                    })
                    .then(function (r) { return r.json(); })
                    .then(function (data) {
                        if (data.success) {
                            var card = btn.closest(".history-item");
                            if (card) card.remove();
                        } else {
                            alert(data.error || "Failed to delete.");
                        }
                    })
                    .catch(function () {
                        alert("Network error.");
                    });
                }
            });
        });
    }

});