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

        localStorage.setItem('periodaAdminActiveSection', sectionId);

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

    // Restore active section on page load
    var savedAdminSection = localStorage.getItem('periodaAdminActiveSection');
    if (savedAdminSection) {
        switchSection(savedAdminSection);
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
        adminLogout.addEventListener("click", function (e) {
            e.preventDefault();
            /* Clear admin session from localStorage */
            localStorage.removeItem("periodaAdmin");

            /* Destroy server session too */
            fetch("admin-logout-handler.php")
                .then(function () {
                    window.location.href = "admin-login.html";
                })
                .catch(function () {
                    window.location.href = "admin-login.html";
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

        fetch("api/admin.php?_=" + Date.now())
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

        fetch("api/admin.php?_=" + Date.now())
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
            html += '<td class="actions-cell" style="display:flex; gap:10px; align-items:center;">';
            html += '<button class="btn-small btn-danger" onclick="deleteUser(' + u.user_id + ', this)">Delete</button>';
            html += '</td>';
            html += '</tr>';

        });

        html += '</tbody></table>';
        container.innerHTML = html;

    }

    window.deleteUser = function(id, btnElement) {
        if (!confirm("Are you sure you want to permanently delete this user? This cannot be undone.")) return;
        fetch("api/admin_delete_user.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ id: id })
        })
        .then(function(r) { 
            return r.text().then(function(text) {
                try {
                    return JSON.parse(text);
                } catch(e) {
                    throw new Error("Server returned invalid JSON: " + text);
                }
            });
        })
        .then(function(data) {
            if (data.success) {
                if (btnElement) {
                    var tr = btnElement.closest("tr");
                    if (tr) tr.remove();
                }
                showAlert("User deleted successfully.", "success");
                loadDashboardStats();
                loadUserData();
                // Optionally call loadUsers() if you want to ensure total consistency, but remove() gives instant feedback.
            } else {
                showAlert(data.error || "Failed to delete user.", "error");
            }
        })
        .catch(function(err) {
            alert("Error deleting user: " + err.message);
        });
    };


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
       ALL USERS DATA SECTION
    ========================== */

    function loadUserData() {

        var container = document.getElementById("userDataTableContainer");

        let isLoggedIn = localStorage.getItem("periodaLoggedIn") === "true" || localStorage.getItem("periodaAdmin");
        let adminDataStr = localStorage.getItem("periodaAdmin");

        if (!isLoggedIn || !adminDataStr) {
            window.location.href = "login.html";
            return;
        }

        try {
            let adminData = JSON.parse(adminDataStr);
            // Only load data if they are actually an admin
            if (!adminData || adminData.role !== "admin") {
                window.location.href = "login.html";
                return;
            }
        } catch(e) {
            window.location.href = "login.html";
            return;
        }

        fetch("api/admin.php?_=" + Date.now())
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
                html += '<th>Name</th><th>Email</th><th>Age</th><th>Joined</th><th>Actions</th>';
                html += '</tr></thead><tbody>';

                users.forEach(function (u) {
                    html += '<tr>';
                    html += '<td>' + escapeHTML(u.name || u.full_name || "–") + '</td>';
                    html += '<td>' + escapeHTML(u.email || "–") + '</td>';
                    html += '<td>' + escapeHTML(String(u.age || "–")) + '</td>';
                    html += '<td>' + escapeHTML(u.created_at ? new Date(u.created_at).toLocaleDateString() : "–") + '</td>';
                    html += '<td><button class="btn-small btn-outline" onclick="viewUserHistory(' + u.user_id + ')">View History</button></td>';
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

    loadUserData();

    /* =========================
       MODAL LOGIC
    ========================== */
    var modal = document.getElementById("historyModal");
    var closeBtn = document.getElementById("closeHistoryModal");
    var modalTitle = document.getElementById("historyModalTitle");
    var modalContent = document.getElementById("historyModalContent");

    if (closeBtn) {
        closeBtn.onclick = function() { modal.style.display = "none"; }
    }
    window.onclick = function(event) {
        if (event.target == modal) modal.style.display = "none";
    }

    window.viewUserHistory = function(id) {
        modal.style.display = "flex";
        modalContent.innerHTML = '<div class="empty-state">Loading history...</div>';
        
        fetch("api/admin_user_history.php?id=" + id)
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (!data.success) {
                    modalContent.innerHTML = '<div class="empty-state" style="color:red;">Failed to load data.</div>';
                    return;
                }
                modalTitle.textContent = data.full_name + "'s History";
                
                var html = '';
                
                // Cycles
                html += '<h3 style="margin-top:0;">Cycles</h3>';
                if (data.cycles.length > 0) {
                    html += '<table class="data-table" style="margin-bottom:20px;"><thead><tr><th>Start Date</th><th>End Date</th><th>Est. Ovulation</th><th>Fertile Window</th></tr></thead><tbody>';
                    data.cycles.forEach(function(c) {
                        var fertileWindow = 'N/A';
                        if (c.estimated_ovulation_date) {
                            var ovDate = new Date(c.estimated_ovulation_date);
                            var startWindow = new Date(ovDate);
                            startWindow.setDate(startWindow.getDate() - 5);
                            var endWindow = new Date(ovDate);
                            
                            var options = { month: 'short', day: 'numeric' };
                            fertileWindow = startWindow.toLocaleDateString(undefined, options) + ' - ' + endWindow.toLocaleDateString(undefined, options);
                        }
                        
                        html += '<tr><td>' + c.start_date + '</td><td>' + (c.end_date || 'Active') + '</td><td>' + (c.estimated_ovulation_date || 'N/A') + '</td><td>' + fertileWindow + '</td></tr>';
                    });
                    html += '</tbody></table>';
                } else {
                    html += '<p style="color:var(--text-muted); margin-bottom:20px;">No cycles logged.</p>';
                }
                
                // Moods
                html += '<h3>Moods</h3>';
                if (data.moods.length > 0) {
                    html += '<table class="data-table" style="margin-bottom:20px;"><thead><tr><th>Date</th><th>Mood</th><th>Intensity</th></tr></thead><tbody>';
                    data.moods.forEach(function(m) {
                        html += '<tr><td>' + m.log_date + '</td><td>' + m.mood + '</td><td>' + m.intensity + '/5</td></tr>';
                    });
                    html += '</tbody></table>';
                } else {
                    html += '<p style="color:var(--text-muted); margin-bottom:20px;">No moods logged.</p>';
                }
                
                // Symptoms
                html += '<h3>Symptoms</h3>';
                if (data.symptoms.length > 0) {
                    html += '<table class="data-table" style="margin-bottom:20px;"><thead><tr><th>Date</th><th>Symptom</th><th>Severity</th></tr></thead><tbody>';
                    data.symptoms.forEach(function(s) {
                        html += '<tr><td>' + s.log_date + '</td><td>' + s.symptom + '</td><td>' + s.severity + '/5</td></tr>';
                    });
                    html += '</tbody></table>';
                } else {
                    html += '<p style="color:var(--text-muted); margin-bottom:20px;">No symptoms logged.</p>';
                }
                
                // Sexual Activity
                html += '<h3>Sexual Activity</h3>';
                var sexualActivities = [];
                data.cycles.forEach(function(c) {
                    if (c.sexual_activity_dates) {
                        var dates = c.sexual_activity_dates.split(',');
                        dates.forEach(function(d) {
                            d = d.trim();
                            if (d && !sexualActivities.includes(d)) {
                                sexualActivities.push(d);
                            }
                        });
                    }
                });
                
                if (sexualActivities.length > 0) {
                    sexualActivities.sort(function(a, b) { return new Date(b) - new Date(a); });
                    html += '<table class="data-table" style="margin-bottom:20px;"><thead><tr><th>Date</th><th>Total Logged</th></tr></thead><tbody>';
                    sexualActivities.forEach(function(date) {
                        html += '<tr><td>' + date + '</td><td>1</td></tr>';
                    });
                    html += '</tbody></table>';
                } else {
                    html += '<p style="color:var(--text-muted); margin-bottom:20px;">No sexual activity logged.</p>';
                }
                
                // Health Tips History
                html += '<h3>Health Tips Requested</h3>';
                if (data.health_tips_history && data.health_tips_history.length > 0) {
                    html += '<table class="data-table"><thead><tr><th>Date</th><th>Searched</th><th>Condition Found</th><th>Actions</th></tr></thead><tbody>';
                    data.health_tips_history.forEach(function(h) {
                        html += '<tr><td>' + h.created_at + '</td><td>' + h.query + '</td><td>' + h.condition_name + '</td><td><button class="btn btn-danger btn-sm" onclick="deleteHealthTipHistory(' + h.id + ', ' + id + ', this)">Delete</button></td></tr>';
                    });
                    html += '</tbody></table>';
                } else {
                    html += '<p style="color:var(--text-muted);">No health tips requested.</p>';
                }
                
                // Notes History
                html += '<h3 style="margin-top:20px;">Notes History</h3>';
                if (data.notes && data.notes.length > 0) {
                    html += '<p style="color:var(--text-muted); font-size:13px; margin-bottom:10px;">Total notes logged: ' + data.notes.length + '</p>';
                    html += '<table class="data-table"><thead><tr><th>Date</th><th>Content</th></tr></thead><tbody>';
                    data.notes.forEach(function(n) {
                        html += '<tr><td>' + n.log_date + '</td><td style="color:#a1a1aa; font-style:italic;">[Hidden for privacy]</td></tr>';
                    });
                    html += '</tbody></table>';
                } else {
                    html += '<p style="color:var(--text-muted);">No notes logged.</p>';
                }
                
                modalContent.innerHTML = html;
            })
            .catch(function() {
                modalContent.innerHTML = '<div class="empty-state" style="color:red;">Connection error.</div>';
            });
    }

    window.deleteHealthTipHistory = function(history_id, user_id, btnElement) {
        if (!confirm("Are you sure you want to delete this health tip history record?")) return;
        
        fetch("api/admin_delete_health_tip_history.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify({ history_id: history_id })
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                if (btnElement) {
                    var tr = btnElement.closest("tr");
                    if (tr) tr.remove();
                }
                showAlert("Health tip history deleted successfully.", "success");
            } else {
                showAlert(data.error || "Failed to delete history.", "error");
            }
        })
        .catch(function(e) {
            showAlert("An error occurred while deleting.", "error");
        });
    };

    window.deleteUserData = function(type, item_id, user_id, btnElement) {
        if (!confirm("Are you sure you want to delete this " + type + " record?")) return;
        
        fetch("api/admin_delete_user_data.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify({ type: type, item_id: item_id, user_id: user_id })
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                if (btnElement) {
                    var tr = btnElement.closest("tr");
                    if (tr) tr.remove();
                }
                showAlert(type.charAt(0).toUpperCase() + type.slice(1) + " deleted successfully.", "success");
            } else {
                showAlert(data.error || "Failed to delete " + type + ".", "error");
            }
        })
        .catch(function(e) {
            showAlert("An error occurred while deleting.", "error");
        });
    };

});