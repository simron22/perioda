document.addEventListener("DOMContentLoaded", function () {

    /* =========================
       AUTH CHECK
       Redirect to admin login if not authenticated
    ========================== */

    var adminSession = localStorage.getItem("periodaAdmin");

    if (!adminSession) {
        window.location.href = "login.html";
        return;
    }

    /* Show the admin name in the header */
    try {
        var adminInfo = JSON.parse(adminSession);
        var adminNameEl = document.getElementById("adminName");
        if (adminNameEl && adminInfo.full_name) {
            adminNameEl.textContent = adminInfo.full_name;
        }
    } catch (e) { /* ignore parse error */ }


    /* =========================
       ELEMENTS
    ========================== */

    const sidebar       = document.getElementById("sidebar");
    const sidebarOverlay= document.getElementById("sidebarOverlay");
    const mobileMenuBtn = document.getElementById("mobileMenuBtn");
    const sidebarNav    = document.getElementById("sidebarNav");
    const pageTitle     = document.getElementById("pageTitle");
    const adminLogout   = document.getElementById("adminLogout");


    /* =========================
       SECTION TITLES MAP
    ========================== */

    const sectionTitles = {
        "dashboard":   "Dashboard",
        "users":       "Manage Users",
        "health-tips": "Health Tips",
        "user-data":   "All Users Data",
        "reports":     "System Reports"
    };


    /* =========================
       NAVIGATION – Tab Switching
    ========================== */

    function switchSection(sectionId) {

        /* Hide all sections */
        document.querySelectorAll(".section").forEach(function (s) {
            s.classList.remove("active");
        });

        /* Remove active from all nav items */
        sidebarNav.querySelectorAll(".nav-item").forEach(function (item) {
            item.classList.remove("active");
        });

        /* Show the target section */
        var target = document.getElementById("section-" + sectionId);
        if (target) {
            target.classList.add("active");
        }

        /* Highlight the nav item */
        var navItem = sidebarNav.querySelector('[data-section="' + sectionId + '"]');
        if (navItem) {
            navItem.classList.add("active");
        }

        /* Update page title */
        pageTitle.textContent = sectionTitles[sectionId] || "Dashboard";

        /* Close mobile sidebar */
        sidebar.classList.remove("open");
        sidebarOverlay.classList.remove("active");
    }


    /* Sidebar nav clicks */
    sidebarNav.querySelectorAll(".nav-item").forEach(function (item) {

        item.addEventListener("click", function (e) {
            e.preventDefault();
            var section = item.getAttribute("data-section");
            switchSection(section);
        });

    });


    /* Quick action buttons & card links */
    document.querySelectorAll("[data-goto]").forEach(function (el) {

        el.addEventListener("click", function (e) {
            e.preventDefault();
            var section = el.getAttribute("data-goto");
            switchSection(section);
        });

    });


    /* =========================
       MOBILE SIDEBAR
    ========================== */

    if (mobileMenuBtn) {
        mobileMenuBtn.addEventListener("click", function () {
            sidebar.classList.toggle("open");
            sidebarOverlay.classList.toggle("active");
        });
    }

    if (sidebarOverlay) {
        sidebarOverlay.addEventListener("click", function () {
            sidebar.classList.remove("open");
            sidebarOverlay.classList.remove("active");
        });
    }


    /* =========================
       LOGOUT
    ========================== */

    if (adminLogout) {

        adminLogout.addEventListener("click", function () {

            var confirmLogout = confirm(
                "Are you sure you want to log out?"
            );

            if (!confirmLogout) return;

            /* Clear admin session from localStorage */
            localStorage.removeItem("periodaAdmin");

            /* Destroy server session too */
            fetch("admin-logout-handler.php")
                .then(function () {
                    window.location.href = "login.html";
                })
                .catch(function () {
                    window.location.href = "login.html";
                });

        });

    }


    /* =========================
       HELPER: Escape HTML
    ========================== */

    function escapeHTML(value) {
        if (!value) return "";
        return String(value)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }


    /* =========================
       FETCH ADMIN STATS
    ========================== */

    function loadDashboardStats() {

        fetch("api/admin.php")
            .then(function (r) { return r.json(); })
            .then(function (data) {

                if (data.success) {

                    /* Stat cards */
                    setTextById("statUsers",   data.totalUsers   || 0);
                    setTextById("statRecords", data.totalActivities || 0);

                    /* Report section numbers */
                    setTextById("reportTotalUsers",   data.totalUsers   || 0);
                    setTextById("reportCycleRecords", data.totalActivities || 0);

                    /* Health tips count – attempt */
                    setTextById("statTips", data.totalTips || 0);
                    setTextById("reportHealthTips", data.totalTips || 0);

                    /* Active users */
                    fetch("api/user.php")
                        .then(function (r2) { return r2.json(); })
                        .then(function (uData) {
                            var active = uData.loggedIn ? "1" : "0";
                            setTextById("statActive", active);
                            setTextById("reportActiveUsers", active);
                        })
                        .catch(function () {
                            setTextById("statActive", "0");
                            setTextById("reportActiveUsers", "0");
                        });

                    /* Recent user in dashboard */
                    if (data.lastUser) {
                        renderRecentUsers([data.lastUser]);
                    } else {
                        document.getElementById("recentUsersTable").innerHTML =
                            '<div class="empty-state">No registered users yet.</div>';
                    }

                    /* Build growth chart */
                    buildGrowthChart(data.totalUsers || 0);

                }

            })
            .catch(function (err) {
                console.error("Error fetching admin stats:", err);
                setTextById("statUsers", "–");
                setTextById("statRecords", "–");
                document.getElementById("recentUsersTable").innerHTML =
                    '<div class="empty-state">Unable to load data.</div>';
            });

    }


    function setTextById(id, value) {
        var el = document.getElementById(id);
        if (el) el.textContent = value;
    }


    function renderRecentUsers(users) {

        var container = document.getElementById("recentUsersTable");

        if (!users || users.length === 0) {
            container.innerHTML = '<div class="empty-state">No users yet.</div>';
            return;
        }

        var html = '<table class="data-table">';
        html += '<thead><tr><th>Name</th><th>Email</th><th>Age</th></tr></thead><tbody>';

        users.forEach(function (u) {
            html += '<tr>';
            html += '<td>' + escapeHTML(u.name || u.full_name || "–") + '</td>';
            html += '<td>' + escapeHTML(u.email || "–") + '</td>';
            html += '<td>' + escapeHTML(String(u.age || "–")) + '</td>';
            html += '</tr>';
        });

        html += '</tbody></table>';
        container.innerHTML = html;

    }


    function buildGrowthChart(total) {

        var chart = document.getElementById("userGrowthChart");
        if (!chart) return;

        /* Generate fake month data from total for visual effect */
        var months = ["Jan","Feb","Mar","Apr","May","Jun"];
        var heights = [15, 25, 35, 50, 70, 100];

        chart.innerHTML = "";

        months.forEach(function (month, i) {
            var bar = document.createElement("div");
            bar.className = "bar";
            bar.style.height = heights[i] + "%";

            var label = document.createElement("span");
            label.className = "bar-label";
            label.textContent = month;

            bar.appendChild(label);
            chart.appendChild(bar);
        });

    }


    /* =========================
       MANAGE USERS SECTION
    ========================== */

    var allUsers = [];

    function loadUsers() {

        var container = document.getElementById("usersTableContainer");

        fetch("api/admin.php")
            .then(function (r) { return r.json(); })
            .then(function (data) {

                if (data.success) {

                    /* The basic API only returns the last user.
                       We build a users list from what we have. */
                    allUsers = [];

                    if (data.lastUser) {
                        allUsers.push(data.lastUser);
                    }

                    /* If multiple users data is available */
                    if (data.users && data.users.length > 0) {
                        allUsers = data.users;
                    }

                    renderUsersTable(allUsers, container);

                } else {
                    container.innerHTML = '<div class="empty-state">No users found.</div>';
                }

            })
            .catch(function () {
                container.innerHTML = '<div class="empty-state">Unable to load users.</div>';
            });

    }


    function renderUsersTable(users, container) {

        if (!users || users.length === 0) {
            container.innerHTML = '<div class="empty-state">No users found.</div>';
            return;
        }

        var html = '<table class="data-table">';
        html += '<thead><tr>';
        html += '<th>Name</th><th>Email</th><th>Age</th><th>Status</th><th>Actions</th>';
        html += '</tr></thead><tbody>';

        users.forEach(function (u, idx) {

            var name   = escapeHTML(u.name || u.full_name || "–");
            var email  = escapeHTML(u.email || "–");
            var age    = escapeHTML(String(u.age || "–"));
            var status = u.status || "active";

            var badgeClass = status === "active" ? "badge-active" : "badge-disabled";

            html += '<tr>';
            html += '<td>' + name + '</td>';
            html += '<td>' + email + '</td>';
            html += '<td>' + age + '</td>';
            html += '<td><span class="badge ' + badgeClass + '">' + escapeHTML(status.charAt(0).toUpperCase() + status.slice(1)) + '</span></td>';
            html += '<td class="actions-cell">';
            html += '<button class="btn-small btn-edit" data-user-idx="' + idx + '">View</button>';
            html += '</td>';
            html += '</tr>';

        });

        html += '</tbody></table>';
        container.innerHTML = html;

    }


    /* User search */
    var searchInput = document.getElementById("userSearchInput");
    if (searchInput) {

        searchInput.addEventListener("input", function () {

            var query = searchInput.value.toLowerCase().trim();
            var container = document.getElementById("usersTableContainer");

            if (!query) {
                renderUsersTable(allUsers, container);
                return;
            }

            var filtered = allUsers.filter(function (u) {
                var name  = (u.name || u.full_name || "").toLowerCase();
                var email = (u.email || "").toLowerCase();
                return name.indexOf(query) !== -1 || email.indexOf(query) !== -1;
            });

            renderUsersTable(filtered, container);

        });

    }


    /* =========================
       HEALTH TIPS SECTION
    ========================== */

    var healthTips = [];

    function loadHealthTips() {

        var container = document.getElementById("tipsTableContainer");

        /* Try to fetch tips – if the endpoint doesn't exist yet,
           we show an empty state gracefully */

        container.innerHTML = '<div class="empty-state">No health tips yet. Add your first tip above!</div>';

        healthTips = getSavedTips();
        renderTipsTable();

    }


    function getSavedTips() {
        try {
            var stored = localStorage.getItem("perioda_health_tips");
            return stored ? JSON.parse(stored) : [];
        } catch (e) {
            return [];
        }
    }


    function saveTips() {
        localStorage.setItem("perioda_health_tips", JSON.stringify(healthTips));
    }


    function renderTipsTable() {

        var container = document.getElementById("tipsTableContainer");

        if (!healthTips || healthTips.length === 0) {
            container.innerHTML = '<div class="empty-state">No health tips yet. Add your first tip above!</div>';
            setTextById("statTips", "0");
            setTextById("reportHealthTips", "0");
            return;
        }

        setTextById("statTips", healthTips.length);
        setTextById("reportHealthTips", healthTips.length);

        var html = '<table class="data-table">';
        html += '<thead><tr><th>Title</th><th>Category</th><th>Actions</th></tr></thead><tbody>';

        healthTips.forEach(function (tip, idx) {
            html += '<tr>';
            html += '<td>' + escapeHTML(tip.title) + '</td>';
            html += '<td><span class="tag">' + escapeHTML(tip.category) + '</span></td>';
            html += '<td class="actions-cell">';
            html += '<button class="btn-small btn-edit" onclick="editTip(' + idx + ')">Edit</button> ';
            html += '<button class="btn-small btn-danger" onclick="deleteTip(' + idx + ')">Delete</button>';
            html += '</td>';
            html += '</tr>';
        });

        html += '</tbody></table>';
        container.innerHTML = html;

    }


    /* Health tip form */
    var tipForm      = document.getElementById("healthTipForm");
    var tipIdField   = document.getElementById("tipId");
    var tipTitle     = document.getElementById("tipTitle");
    var tipCategory  = document.getElementById("tipCategory");
    var tipContent   = document.getElementById("tipContent");
    var tipFormTitle  = document.getElementById("tipFormTitle");
    var tipSubmitBtn = document.getElementById("tipSubmitBtn");
    var tipCancelBtn = document.getElementById("tipCancelBtn");


    if (tipForm) {

        tipForm.addEventListener("submit", function (e) {
            e.preventDefault();

            var title    = tipTitle.value.trim();
            var category = tipCategory.value;
            var content  = tipContent.value.trim();

            if (!title || !category || !content) {
                alert("Please fill in all fields.");
                return;
            }

            var editIdx = tipIdField.value;

            if (editIdx !== "") {
                /* Editing */
                var idx = parseInt(editIdx, 10);
                healthTips[idx] = {
                    title: title,
                    category: category,
                    content: content
                };
                showAlert("Health tip updated successfully.", "success");
            } else {
                /* Adding */
                healthTips.push({
                    title: title,
                    category: category,
                    content: content
                });
                showAlert("Health tip added successfully.", "success");
            }

            saveTips();
            renderTipsTable();
            resetTipForm();

        });

    }


    if (tipCancelBtn) {
        tipCancelBtn.addEventListener("click", function () {
            resetTipForm();
        });
    }


    function resetTipForm() {
        tipIdField.value   = "";
        tipTitle.value     = "";
        tipCategory.value  = "";
        tipContent.value   = "";
        tipFormTitle.textContent   = "Add a Health Tip";
        tipSubmitBtn.textContent   = "Add Tip";
        tipCancelBtn.style.display = "none";
    }


    /* Global functions for inline onclick */
    window.editTip = function (idx) {

        var tip = healthTips[idx];
        if (!tip) return;

        tipIdField.value  = idx;
        tipTitle.value    = tip.title;
        tipCategory.value = tip.category;
        tipContent.value  = tip.content;

        tipFormTitle.textContent   = "Edit Health Tip";
        tipSubmitBtn.textContent   = "Update Tip";
        tipCancelBtn.style.display = "inline-block";

        /* Scroll to form */
        document.getElementById("section-health-tips").scrollIntoView({
            behavior: "smooth"
        });

    };


    window.deleteTip = function (idx) {

        var confirmed = confirm("Delete this health tip?");
        if (!confirmed) return;

        healthTips.splice(idx, 1);
        saveTips();
        renderTipsTable();
        showAlert("Health tip deleted.", "success");

    };


    /* =========================
       ALL USERS DATA SECTION
    ========================== */

    function loadUserData() {

        var container = document.getElementById("userDataTableContainer");

        fetch("api/admin.php")
            .then(function (r) { return r.json(); })
            .then(function (data) {

                if (!data.success) {
                    container.innerHTML = '<div class="empty-state">No data available.</div>';
                    return;
                }

                var users = [];

                if (data.users && data.users.length > 0) {
                    users = data.users;
                } else if (data.lastUser) {
                    users = [data.lastUser];
                }

                if (users.length === 0) {
                    container.innerHTML = '<div class="empty-state">No users found.</div>';
                    return;
                }

                var html = '<table class="data-table">';
                html += '<thead><tr>';
                html += '<th>Name</th><th>Email</th><th>Age</th><th>Joined</th>';
                html += '</tr></thead><tbody>';

                users.forEach(function (u) {
                    html += '<tr>';
                    html += '<td>' + escapeHTML(u.name || u.full_name || "–") + '</td>';
                    html += '<td>' + escapeHTML(u.email || "–") + '</td>';
                    html += '<td>' + escapeHTML(String(u.age || "–")) + '</td>';
                    html += '<td>' + escapeHTML(u.created_at || "–") + '</td>';
                    html += '</tr>';
                });

                html += '</tbody></table>';
                container.innerHTML = html;

            })
            .catch(function () {
                container.innerHTML = '<div class="empty-state">Unable to load user data.</div>';
            });

    }


    /* =========================
       ALERTS
    ========================== */

    function showAlert(message, type) {

        /* Remove existing alerts */
        document.querySelectorAll(".alert").forEach(function (a) {
            a.remove();
        });

        var alert = document.createElement("div");
        alert.className = "alert alert-" + (type || "success");
        alert.textContent = message;

        /* Insert at top of the active section */
        var activeSection = document.querySelector(".section.active");
        if (activeSection) {
            activeSection.insertBefore(alert, activeSection.firstChild);
        }

        /* Auto-dismiss */
        setTimeout(function () {
            alert.style.opacity = "0";
            alert.style.transition = "opacity 0.3s ease";
            setTimeout(function () {
                alert.remove();
            }, 300);
        }, 3000);

    }


    /* =========================
       INITIAL DATA LOAD
    ========================== */

    loadDashboardStats();
    loadUsers();
    loadHealthTips();
    loadUserData();


});