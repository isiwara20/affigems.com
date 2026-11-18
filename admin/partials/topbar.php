            <!-- Top Bar -->
            <header class="topbar">
                <div class="topbar-left">
                    <button class="menu-toggle" id="menuToggle">
                        <i class="fas fa-bars"></i>
                    </button>
                    <div class="search-box" style="position:relative;">
                        <i class="fas fa-search"></i>
                        <input type="text" id="adminSearchInput" placeholder="Search pages..." autocomplete="off" oninput="handleAdminSearch(this.value)" onkeydown="handleAdminSearchKey(event)">
                        <div class="admin-search-results" id="adminSearchResults"></div>
                    </div>
                </div>
                <div class="topbar-right">
                    <!-- Settings Button -->
                    <div class="topbar-dropdown-wrap">
                        <button class="topbar-icon" onclick="toggleDropdown('settingsDropdown', event)">
                            <i class="fas fa-cog"></i>
                        </button>
                        <div class="topbar-dropdown" id="settingsDropdown">
                            <div class="topbar-dropdown-header">
                                <h4>Settings</h4>
                            </div>
                            <div class="topbar-dropdown-body">
                                <div class="theme-toggle-row">
                                    <div class="theme-toggle-info">
                                        <i class="fas fa-moon"></i>
                                        <span>Dark Theme</span>
                                    </div>
                                    <label class="theme-switch">
                                        <input type="checkbox" id="darkThemeToggle" onchange="toggleDarkTheme(this.checked)">
                                        <span class="theme-slider"></span>
                                    </label>
                                </div>
                                <a href="<?php echo BASE_URL; ?>admin/site-settings.php" class="topbar-dropdown-item">
                                    <i class="fas fa-cog"></i> Site Settings
                                </a>
                                <a href="<?php echo BASE_URL; ?>admin/admins.php" class="topbar-dropdown-item">
                                    <i class="fas fa-user-shield"></i> Manage Admins
                                </a>
                            </div>
                            <div class="topbar-dropdown-footer">
                                <a href="#" onclick="showAdminLogoutConfirm(event)" class="topbar-dropdown-item logout-item">
                                    <i class="fas fa-sign-out-alt"></i> Logout
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Notification Button -->
                    <div class="topbar-dropdown-wrap">
                        <button class="topbar-icon notification-btn" onclick="toggleDropdown('notificationDropdown', event); markNotificationsRead();">
                            <i class="fas fa-bell"></i>
                            <span class="notification-dot" id="notifDot"></span>
                        </button>
                        <div class="topbar-dropdown notification-dropdown" id="notificationDropdown">
                            <div class="topbar-dropdown-header">
                                <h4>Notifications</h4>
                                <a href="#" onclick="clearAllNotifications(event)" class="clear-all-btn">Clear All</a>
                            </div>
                            <div class="topbar-dropdown-body notification-list" id="notificationList">
                                <div class="notification-loading">
                                    <i class="fas fa-spinner fa-spin"></i> Loading...
                                </div>
                            </div>
                            <div class="topbar-dropdown-footer">
                                <a href="<?php echo BASE_URL; ?>admin/orders.php" class="topbar-dropdown-item" style="justify-content:center; color:#2563eb;">
                                    View All Orders <i class="fas fa-arrow-right" style="font-size:10px;"></i>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Admin Profile -->
                    <div class="admin-profile" onclick="showAdminLogoutConfirm(event)" style="cursor:pointer;" title="Click to logout">
                        <div class="admin-avatar">
                            <i class="fas fa-user"></i>
                        </div>
                        <div class="admin-info">
                            <span class="admin-name"><?php echo htmlspecialchars($adminName); ?></span>
                            <span class="admin-role">Administrator</span>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Logout Confirmation Overlay -->
            <div class="admin-logout-overlay" id="adminLogoutOverlay">
                <div class="admin-logout-modal">
                    <div class="admin-logout-icon">
                        <i class="fas fa-sign-out-alt"></i>
                    </div>
                    <h3>Confirm Logout</h3>
                    <p>Are you sure you want to log out of the admin dashboard?</p>
                    <div class="admin-logout-actions">
                        <button class="admin-logout-cancel" onclick="hideAdminLogoutConfirm()">Cancel</button>
                        <a href="<?php echo BASE_URL; ?>controllers/AuthController.php?action=logout" class="admin-logout-confirm">Yes, Logout</a>
                    </div>
                </div>
            </div>

            <style>
            /* Admin Search */
            .admin-search-results {
                display: none; position: absolute; top: calc(100% + 6px); left: 0; right: 0;
                background: #fff; border: 1px solid #e8ecf1; border-radius: 12px;
                box-shadow: 0 10px 30px rgba(0,0,0,0.12); z-index: 9999; max-height: 300px;
                overflow-y: auto; padding: 6px;
            }
            .admin-search-results.show { display: block; }
            .admin-search-item {
                display: flex; align-items: center; gap: 10px; padding: 10px 12px;
                border-radius: 8px; text-decoration: none; color: #333; transition: background 0.15s; cursor: pointer;
            }
            .admin-search-item:hover, .admin-search-item.highlighted { background: #f5f7fa; }
            .admin-search-item i { width: 18px; text-align: center; color: #6b7099; font-size: 13px; }
            .admin-search-item span { font-size: 13px; font-weight: 500; }
            .admin-search-item small { font-size: 10px; color: #aaa; margin-left: auto; }
            .admin-search-empty { text-align: center; padding: 16px; font-size: 12px; color: #aaa; }

            /* Topbar Dropdowns */
            .topbar-dropdown-wrap { position: relative; }
            .topbar-dropdown {
                display: none;
                position: absolute;
                top: calc(100% + 10px);
                right: 0;
                background: #fff;
                border: 1px solid #e8ecf1;
                border-radius: 14px;
                box-shadow: 0 12px 40px rgba(0,0,0,0.12);
                z-index: 9999;
                min-width: 280px;
                overflow: hidden;
            }
            .topbar-dropdown.show { display: block; animation: dropdownFadeIn 0.2s ease; }
            @keyframes dropdownFadeIn { from { opacity:0; transform:translateY(-8px); } to { opacity:1; transform:translateY(0); } }
            .topbar-dropdown-header {
                display: flex; justify-content: space-between; align-items: center;
                padding: 14px 18px; border-bottom: 1px solid #f0f0f0;
            }
            .topbar-dropdown-header h4 { font-size: 14px; font-weight: 700; color: #0d1137; margin: 0; }
            .clear-all-btn { font-size: 11px; color: #6b7099; text-decoration: none; font-weight: 500; }
            .clear-all-btn:hover { color: #ef4444; }
            .topbar-dropdown-body { max-height: 320px; overflow-y: auto; padding: 8px; }
            .topbar-dropdown-body::-webkit-scrollbar { width: 4px; }
            .topbar-dropdown-body::-webkit-scrollbar-thumb { background: #ddd; border-radius: 4px; }
            .topbar-dropdown-footer { border-top: 1px solid #f0f0f0; padding: 8px; }
            .topbar-dropdown-item {
                display: flex; align-items: center; gap: 10px;
                padding: 10px 14px; font-size: 13px; color: #333;
                text-decoration: none; border-radius: 8px; transition: background 0.15s;
            }
            .topbar-dropdown-item:hover { background: #f5f7fa; }
            .topbar-dropdown-item.logout-item { color: #ef4444; }
            .topbar-dropdown-item.logout-item:hover { background: #fef2f2; }
            .topbar-dropdown-item i { width: 16px; text-align: center; font-size: 13px; color: #6b7099; }
            .topbar-dropdown-item.logout-item i { color: #ef4444; }

            /* Theme Toggle */
            .theme-toggle-row {
                display: flex; justify-content: space-between; align-items: center;
                padding: 12px 14px; border-radius: 8px; background: #f8f9fc; margin-bottom: 6px;
            }
            .theme-toggle-info { display: flex; align-items: center; gap: 10px; font-size: 13px; color: #333; font-weight: 500; }
            .theme-toggle-info i { color: #6b7099; }
            .theme-switch { position: relative; width: 42px; height: 22px; }
            .theme-switch input { opacity: 0; width: 0; height: 0; }
            .theme-slider {
                position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0;
                background: #e4e8f0; border-radius: 22px; transition: 0.3s;
            }
            .theme-slider:before {
                content: ''; position: absolute; height: 16px; width: 16px; left: 3px; bottom: 3px;
                background: #fff; border-radius: 50%; transition: 0.3s; box-shadow: 0 1px 3px rgba(0,0,0,0.15);
            }
            .theme-switch input:checked + .theme-slider { background: #2563eb; }
            .theme-switch input:checked + .theme-slider:before { transform: translateX(20px); }

            /* Notification Items */
            .notification-list { padding: 6px 8px; }
            .notification-item {
                display: flex; gap: 12px; padding: 12px; border-radius: 10px; transition: background 0.15s; cursor: pointer;
            }
            .notification-item:hover { background: #f5f7fa; }
            .notification-item .notif-icon {
                width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center;
                font-size: 14px; flex-shrink: 0;
            }
            .notification-item .notif-icon.order { background: #eff6ff; color: #2563eb; }
            .notification-item .notif-icon.whatsapp { background: #ecfdf5; color: #25d366; }
            .notification-item .notif-icon.payment { background: #f0fdf4; color: #16a34a; }
            .notification-item .notif-icon.cancelled { background: #fef2f2; color: #ef4444; }
            .notification-item .notif-content { flex: 1; min-width: 0; }
            .notification-item .notif-title { font-size: 12px; font-weight: 600; color: #0d1137; margin-bottom: 2px; }
            .notification-item .notif-desc { font-size: 11px; color: #6b7099; line-height: 1.4; }
            .notification-item .notif-time { font-size: 10px; color: #aaa; margin-top: 4px; }
            .notification-empty { text-align: center; padding: 30px 20px; color: #aaa; font-size: 12px; }
            .notification-empty i { font-size: 24px; display: block; margin-bottom: 8px; }
            .notification-loading { text-align: center; padding: 20px; color: #aaa; font-size: 12px; }

            /* Logout Overlay */
            .admin-logout-overlay {
                display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0;
                background: rgba(0,0,0,0.5); z-index: 99999; align-items: center; justify-content: center;
                backdrop-filter: blur(3px);
            }
            .admin-logout-overlay.show { display: flex; animation: fadeInOverlay 0.2s ease; }
            .admin-logout-modal {
                background: #fff; border-radius: 16px; padding: 36px; max-width: 380px; width: 90%;
                text-align: center; animation: scaleIn 0.25s ease; box-shadow: 0 20px 50px rgba(0,0,0,0.15);
            }
            .admin-logout-icon {
                width: 60px; height: 60px; background: #fef2f2; border-radius: 50%;
                display: flex; align-items: center; justify-content: center;
                margin: 0 auto 18px; font-size: 24px; color: #ef4444;
            }
            .admin-logout-modal h3 { font-size: 18px; font-weight: 700; color: #0d1137; margin-bottom: 8px; }
            .admin-logout-modal p { font-size: 13px; color: #666; margin-bottom: 24px; line-height: 1.5; }
            .admin-logout-actions { display: flex; gap: 12px; justify-content: center; }
            .admin-logout-cancel {
                padding: 11px 24px; background: #f0f2f8; color: #333; border: none; border-radius: 10px;
                font-size: 13px; font-weight: 600; cursor: pointer; font-family: 'Poppins', sans-serif; transition: background 0.2s;
            }
            .admin-logout-cancel:hover { background: #e4e8f0; }
            .admin-logout-confirm {
                padding: 11px 24px; background: #ef4444; color: #fff; border: none; border-radius: 10px;
                font-size: 13px; font-weight: 600; cursor: pointer; font-family: 'Poppins', sans-serif;
                text-decoration: none; display: inline-flex; align-items: center; transition: background 0.2s;
            }
            .admin-logout-confirm:hover { background: #dc2626; }
            @keyframes fadeInOverlay { from { opacity: 0; } to { opacity: 1; } }
            @keyframes scaleIn { from { transform: scale(0.9); opacity: 0; } to { transform: scale(1); opacity: 1; } }

            /* Dark Theme */
            body.dark-theme { background: #1a1d2e; color: #e0e0e0; }
            body.dark-theme .topbar { background: #232738; border-color: #2d3148; }
            body.dark-theme .topbar-icon { background: #2d3148; border-color: #3a3f57; color: #a0a5c8; }
            body.dark-theme .topbar-icon:hover { background: #363b54; color: #fff; }
            body.dark-theme .search-box { background: #2d3148; border-color: #3a3f57; }
            body.dark-theme .search-box input { color: #e0e0e0; }
            body.dark-theme .search-box input::placeholder { color: #6b7099; }
            body.dark-theme .sidebar { background: #1e2235; border-color: #2d3148; }
            body.dark-theme .sidebar-nav .nav-item { color: #c8cce6; }
            body.dark-theme .sidebar-nav .nav-item:hover { background: #282d44; color: #fff; }
            body.dark-theme .sidebar-nav .nav-item.active { background: #2563eb; color: #fff; }
            body.dark-theme .nav-section-title { color: #6b7099; }
            body.dark-theme .dashboard-content { background: #1a1d2e; }
            body.dark-theme .stat-card,
            body.dark-theme .dashboard-card,
            body.dark-theme .table-card,
            body.dark-theme .content-card,
            body.dark-theme .order-card,
            body.dark-theme .ordering-card,
            body.dark-theme .payment-card,
            body.dark-theme .admin-card,
            body.dark-theme .add-admin-form { background: #232738; border-color: #2d3148; color: #e0e0e0; }
            body.dark-theme .stat-card .stat-label { color: #a0a5c8; }
            body.dark-theme .stat-card .stat-value { color: #fff; }
            body.dark-theme .card-header h3 { color: #e0e0e0; }
            body.dark-theme .page-title { color: #fff; }
            body.dark-theme .page-subtitle { color: #8b90c0; }
            body.dark-theme .page-header { color: #fff; }
            body.dark-theme h1, body.dark-theme h2, body.dark-theme h3, body.dark-theme h4 { color: #fff; }
            body.dark-theme p { color: #c8cce6; }
            body.dark-theme strong { color: #e8ecf5; }
            body.dark-theme span { color: #c8cce6; }
            body.dark-theme small { color: #8b90c0; }
            body.dark-theme label { color: #c8cce6; }
            body.dark-theme a { color: #93b5f5; }
            body.dark-theme table th { background: #2d3148; color: #a0a5c8; border-color: #3a3f57; }
            body.dark-theme table td { border-color: #2d3148; color: #e0e0e0; }
            body.dark-theme table tr:hover { background: #282d44; }
            body.dark-theme table { border-color: #2d3148; }
            body.dark-theme .admin-footer p { color: #6b7099; }
            body.dark-theme .topbar-dropdown { background: #232738; border-color: #2d3148; }
            body.dark-theme .topbar-dropdown-header { border-color: #2d3148; }
            body.dark-theme .topbar-dropdown-header h4 { color: #fff; }
            body.dark-theme .topbar-dropdown-footer { border-color: #2d3148; }
            body.dark-theme .topbar-dropdown-item { color: #e0e0e0; }
            body.dark-theme .topbar-dropdown-item:hover { background: #2d3148; }
            body.dark-theme .theme-toggle-row { background: #2d3148; }
            body.dark-theme .theme-toggle-info { color: #e0e0e0; }
            body.dark-theme .notification-item:hover { background: #2d3148; }
            body.dark-theme .notification-item .notif-title { color: #fff; }
            body.dark-theme .notification-item .notif-desc { color: #a0a5c8; }
            body.dark-theme .admin-name { color: #fff; }
            body.dark-theme .admin-role { color: #8b90c0; }
            body.dark-theme .order-stats .order-stat-card { background: #232738; border-color: #2d3148; }
            body.dark-theme .order-stats .order-stat-card p { color: #8b90c0; }
            body.dark-theme .order-stats .order-stat-card h4 { color: #fff; }
            body.dark-theme .welcome-section { color: #fff; }
            body.dark-theme .welcome-name { color: #fff; }
            body.dark-theme .welcome-text { color: #a0a5c8; }
            body.dark-theme .date-filter { color: #a0a5c8; border-color: #3a3f57; background: #2d3148; }
            body.dark-theme input, body.dark-theme select, body.dark-theme textarea { background: #2d3148; border-color: #3a3f57; color: #e0e0e0; }
            body.dark-theme input::placeholder { color: #6b7099; }
            body.dark-theme .view-all { color: #93b5f5; }
            body.dark-theme .order-id { color: #93b5f5; }
            body.dark-theme .order-number { color: #93b5f5; }
            body.dark-theme .product-row { border-color: #2d3148; }
            body.dark-theme .product-row p { color: #e0e0e0; }
            body.dark-theme .product-rank { color: #8b90c0; }
            body.dark-theme .product-sales { color: #a0a5c8; }
            body.dark-theme .product-revenue { color: #e0e0e0; }
            body.dark-theme .stock-item { border-color: #2d3148; }
            body.dark-theme .stock-info span { color: #e0e0e0; }
            body.dark-theme .stock-info i { color: #6b7099; }
            body.dark-theme .customer-stat { border-color: #2d3148; }
            body.dark-theme .customer-info span { color: #a0a5c8; }
            body.dark-theme .customer-info strong { color: #fff; }
            body.dark-theme .quick-actions h3 { color: #fff; }
            body.dark-theme .action-btn { background: #2d3148; color: #e0e0e0; border-color: #3a3f57; }
            body.dark-theme .action-btn:hover { background: #363b54; color: #fff; }
            body.dark-theme .chart-bar span { color: #8b90c0; }
            body.dark-theme .chart-tooltip { background: #2d3148; color: #fff; }
            body.dark-theme .donut-center h4 { color: #fff; }
            body.dark-theme .donut-center p { color: #8b90c0; }
            body.dark-theme .legend-item span { color: #c8cce6; }
            body.dark-theme .legend-item strong { color: #e0e0e0; }
            body.dark-theme .card-filter { background: #2d3148; border-color: #3a3f57; color: #e0e0e0; }
            body.dark-theme .dataTables_wrapper .dataTables_filter input { background: #2d3148; border-color: #3a3f57; color: #e0e0e0; }
            body.dark-theme .dataTables_wrapper .dataTables_length select { background: #2d3148; border-color: #3a3f57; color: #e0e0e0; }
            body.dark-theme .dataTables_wrapper .dataTables_info { color: #6b7099; }
            body.dark-theme .dataTables_wrapper .dataTables_paginate .paginate_button { color: #a0a5c8 !important; background: #2d3148 !important; border-color: #3a3f57 !important; }
            body.dark-theme .dataTables_wrapper .dataTables_paginate .paginate_button.current { background: #2563eb !important; color: #fff !important; }
            body.dark-theme .order-status-select, body.dark-theme .payment-status-select { background: #2d3148; border-color: #3a3f57; color: #e0e0e0; }
            body.dark-theme .btn-view-order { background: #2d3148; color: #93b5f5; }
            body.dark-theme .btn-print-label { background: #2d3148; color: #a0a5c8; }
            body.dark-theme .ordering-card-info h3 { color: #fff; }
            body.dark-theme .ordering-card-info p { color: #8b90c0; }
            body.dark-theme .payment-card-info h3 { color: #fff; }
            body.dark-theme .payment-card-info p { color: #8b90c0; }
            body.dark-theme .admin-card-info h4 { color: #fff; }
            body.dark-theme .admin-card-info p { color: #8b90c0; }
            body.dark-theme .add-admin-form h3 { color: #fff; }
            body.dark-theme .form-group label { color: #c8cce6; }
            body.dark-theme .admin-search-results { background: #232738; border-color: #2d3148; }
            body.dark-theme .admin-search-item { color: #e0e0e0; }
            body.dark-theme .admin-search-item:hover, body.dark-theme .admin-search-item.highlighted { background: #2d3148; }
            body.dark-theme .admin-search-item small { color: #6b7099; }
            body.dark-theme .ordering-note { background: #2d3148; border-color: #3a3f57; color: #e0e0e0; }
            body.dark-theme .badge-count { color: #fff; }
            </style>

            <script>
            // ---- Mobile Sidebar Close on Outside Click ----
            document.addEventListener('click', function(e) {
                var layout = document.querySelector('.admin-layout');
                var sidebar = document.getElementById('sidebar');
                if (layout && layout.classList.contains('sidebar-collapsed') && window.innerWidth <= 768) {
                    if (!sidebar.contains(e.target) && !e.target.closest('#menuToggle')) {
                        layout.classList.remove('sidebar-collapsed');
                    }
                }
            });

            // ---- Dropdown Toggle ----
            function toggleDropdown(id, e) {
                e.stopPropagation();
                var dropdown = document.getElementById(id);
                var isOpen = dropdown.classList.contains('show');
                // Close all dropdowns first
                document.querySelectorAll('.topbar-dropdown').forEach(function(d) { d.classList.remove('show'); });
                if (!isOpen) dropdown.classList.add('show');
            }
            document.addEventListener('click', function(e) {
                if (!e.target.closest('.topbar-dropdown-wrap')) {
                    document.querySelectorAll('.topbar-dropdown').forEach(function(d) { d.classList.remove('show'); });
                }
            });

            // ---- Notifications ----
            function loadNotifications() {
                fetch('<?php echo BASE_URL; ?>controllers/NotificationController.php?action=get_recent')
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    var list = document.getElementById('notificationList');
                    if (data.notifications && data.notifications.length > 0) {
                        var html = '';
                        data.notifications.forEach(function(n) {
                            var iconClass = 'order';
                            if (n.type === 'whatsapp') iconClass = 'whatsapp';
                            else if (n.type === 'payment') iconClass = 'payment';
                            else if (n.type === 'cancelled') iconClass = 'cancelled';
                            
                            var icon = 'fa-shopping-bag';
                            if (n.type === 'whatsapp') icon = 'fa-whatsapp';
                            else if (n.type === 'payment') icon = 'fa-credit-card';
                            else if (n.type === 'cancelled') icon = 'fa-times-circle';

                            html += '<div class="notification-item" onclick="window.location.href=\'' + (n.url || '#') + '\'">';
                            html += '<div class="notif-icon ' + iconClass + '"><i class="fas ' + icon + '"></i></div>';
                            html += '<div class="notif-content">';
                            html += '<div class="notif-title">' + n.title + '</div>';
                            html += '<div class="notif-desc">' + n.message + '</div>';
                            html += '<div class="notif-time">' + n.time_ago + '</div>';
                            html += '</div></div>';
                        });
                        list.innerHTML = html;
                        // Show/hide dot
                        if (data.unread_count > 0) {
                            document.getElementById('notifDot').style.display = 'block';
                        } else {
                            document.getElementById('notifDot').style.display = 'none';
                        }
                    } else {
                        list.innerHTML = '<div class="notification-empty"><i class="fas fa-bell-slash"></i>No notifications yet</div>';
                        document.getElementById('notifDot').style.display = 'none';
                    }
                })
                .catch(function() {
                    document.getElementById('notificationList').innerHTML = '<div class="notification-empty"><i class="fas fa-bell-slash"></i>No notifications yet</div>';
                });
            }

            function markNotificationsRead() {
                fetch('<?php echo BASE_URL; ?>controllers/NotificationController.php?action=mark_read');
                document.getElementById('notifDot').style.display = 'none';
            }

            function clearAllNotifications(e) {
                e.preventDefault();
                fetch('<?php echo BASE_URL; ?>controllers/NotificationController.php?action=clear_all')
                .then(function() {
                    document.getElementById('notificationList').innerHTML = '<div class="notification-empty"><i class="fas fa-bell-slash"></i>No notifications yet</div>';
                    document.getElementById('notifDot').style.display = 'none';
                });
            }

            // Load notifications on page load
            loadNotifications();
            // Refresh every 30 seconds
            setInterval(loadNotifications, 30000);

            // ---- Dark Theme ----
            function toggleDarkTheme(enabled) {
                if (enabled) {
                    document.body.classList.add('dark-theme');
                    localStorage.setItem('admin_dark_theme', '1');
                } else {
                    document.body.classList.remove('dark-theme');
                    localStorage.setItem('admin_dark_theme', '0');
                }
            }
            // Apply saved theme on load
            (function() {
                var saved = localStorage.getItem('admin_dark_theme');
                if (saved === '1') {
                    document.body.classList.add('dark-theme');
                    var toggle = document.getElementById('darkThemeToggle');
                    if (toggle) toggle.checked = true;
                }
            })();

            // ---- Logout Confirmation ----
            function showAdminLogoutConfirm(e) {
                e.preventDefault();
                e.stopPropagation();
                document.querySelectorAll('.topbar-dropdown').forEach(function(d) { d.classList.remove('show'); });
                document.getElementById('adminLogoutOverlay').classList.add('show');
            }
            function hideAdminLogoutConfirm() {
                document.getElementById('adminLogoutOverlay').classList.remove('show');
            }
            document.getElementById('adminLogoutOverlay').addEventListener('click', function(e) {
                if (e.target === this) hideAdminLogoutConfirm();
            });

            // ---- Admin Search ----
            var adminPages = [
                { name: 'Dashboard', icon: 'fa-th-large', url: '<?php echo BASE_URL; ?>admin/index.php', category: 'Main' },
                { name: 'Products', icon: 'fa-box', url: '<?php echo BASE_URL; ?>admin/products.php', category: 'Manage' },
                { name: 'Add Product', icon: 'fa-plus-circle', url: '<?php echo BASE_URL; ?>admin/add-product.php', category: 'Manage' },
                { name: 'Categories', icon: 'fa-layer-group', url: '<?php echo BASE_URL; ?>admin/categories.php', category: 'Manage' },
                { name: 'Orders', icon: 'fa-shopping-bag', url: '<?php echo BASE_URL; ?>admin/orders.php', category: 'Manage' },
                { name: 'WhatsApp Orders', icon: 'fa-whatsapp', url: '<?php echo BASE_URL; ?>admin/whatsapp-orders.php', category: 'Manage' },
                { name: 'Customers', icon: 'fa-users', url: '<?php echo BASE_URL; ?>admin/customers.php', category: 'Manage' },
                { name: 'Reviews', icon: 'fa-star', url: '<?php echo BASE_URL; ?>admin/reviews.php', category: 'Manage' },
                { name: 'Coupons', icon: 'fa-ticket-alt', url: '<?php echo BASE_URL; ?>admin/coupons.php', category: 'Manage' },
                { name: 'Chat Support', icon: 'fa-headset', url: '<?php echo BASE_URL; ?>admin/chats.php', category: 'Manage' },
                { name: 'Banners', icon: 'fa-images', url: '<?php echo BASE_URL; ?>admin/banners.php', category: 'Content' },
                { name: 'Testimonials', icon: 'fa-quote-left', url: '<?php echo BASE_URL; ?>admin/testimonials.php', category: 'Content' },
                { name: 'Subscribers', icon: 'fa-envelope-open-text', url: '<?php echo BASE_URL; ?>admin/subscribers.php', category: 'Content' },
                { name: 'Site Settings', icon: 'fa-cog', url: '<?php echo BASE_URL; ?>admin/site-settings.php', category: 'Settings' },
                { name: 'Payment Methods', icon: 'fa-credit-card', url: '<?php echo BASE_URL; ?>admin/payment-methods.php', category: 'Settings' },
                { name: 'Ordering Process', icon: 'fa-clipboard-list', url: '<?php echo BASE_URL; ?>admin/ordering-process.php', category: 'Settings' },
                { name: 'Manage Admins', icon: 'fa-user-shield', url: '<?php echo BASE_URL; ?>admin/admins.php', category: 'Settings' }
            ];
            var searchHighlightIndex = -1;

            function handleAdminSearch(query) {
                var results = document.getElementById('adminSearchResults');
                searchHighlightIndex = -1;
                if (query.length < 1) {
                    results.classList.remove('show');
                    results.innerHTML = '';
                    return;
                }
                var q = query.toLowerCase();
                var matches = adminPages.filter(function(p) {
                    return p.name.toLowerCase().includes(q) || p.category.toLowerCase().includes(q);
                });
                if (matches.length > 0) {
                    var html = '';
                    matches.forEach(function(m, i) {
                        html += '<a href="' + m.url + '" class="admin-search-item" data-index="' + i + '">';
                        html += '<i class="fas ' + m.icon + '"></i>';
                        html += '<span>' + m.name + '</span>';
                        html += '<small>' + m.category + '</small>';
                        html += '</a>';
                    });
                    results.innerHTML = html;
                    results.classList.add('show');
                } else {
                    results.innerHTML = '<div class="admin-search-empty">No results found</div>';
                    results.classList.add('show');
                }
            }

            function handleAdminSearchKey(e) {
                var results = document.getElementById('adminSearchResults');
                var items = results.querySelectorAll('.admin-search-item');
                if (!items.length) return;
                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    searchHighlightIndex = Math.min(searchHighlightIndex + 1, items.length - 1);
                    items.forEach(function(el) { el.classList.remove('highlighted'); });
                    items[searchHighlightIndex].classList.add('highlighted');
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    searchHighlightIndex = Math.max(searchHighlightIndex - 1, 0);
                    items.forEach(function(el) { el.classList.remove('highlighted'); });
                    items[searchHighlightIndex].classList.add('highlighted');
                } else if (e.key === 'Enter') {
                    e.preventDefault();
                    if (searchHighlightIndex >= 0 && items[searchHighlightIndex]) {
                        window.location.href = items[searchHighlightIndex].href;
                    } else if (items.length > 0) {
                        window.location.href = items[0].href;
                    }
                } else if (e.key === 'Escape') {
                    results.classList.remove('show');
                    document.getElementById('adminSearchInput').blur();
                }
            }

            // Close search on click outside
            document.addEventListener('click', function(e) {
                if (!e.target.closest('.search-box')) {
                    document.getElementById('adminSearchResults').classList.remove('show');
                }
            });
            </script>

<!-- Auto-Refresh Polling for New Orders -->
<script>
(function() {
    var pollInterval = 15000; // 15 seconds
    var knownOrderCount = null;

    function checkForNewOrders() {
        fetch('<?php echo BASE_URL; ?>controllers/AdminPollController.php?action=check_new_orders&since=' + encodeURIComponent(new Date().toISOString()))
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (knownOrderCount === null) {
                // First load - just store the count, don't notify
                knownOrderCount = data.total_orders;
            } else if (data.total_orders > knownOrderCount) {
                var newCount = data.total_orders - knownOrderCount;
                showAdminNotification('New Order Received! (' + newCount + ' new)', 'success');
                playNotificationSound();
                updateDashboardStats(data);
                knownOrderCount = data.total_orders;
            }
        })
        .catch(function() {});
    }

    function showAdminNotification(message, type) {
        var existing = document.querySelector('.admin-live-toast');
        if (existing) existing.remove();

        var color = type === 'success' ? '#16a34a' : '#ef4444';
        var toast = document.createElement('div');
        toast.className = 'admin-live-toast';
        toast.style.cssText = 'position:fixed; top:20px; right:20px; z-index:99999; background:#fff; color:' + color + '; padding:16px 22px; border-radius:12px; font-size:13px; font-weight:600; display:flex; align-items:center; gap:10px; box-shadow:0 8px 30px rgba(0,0,0,0.15); border-left:4px solid ' + color + '; font-family:Poppins,sans-serif; animation:slideInRight 0.4s ease; max-width:350px;';
        toast.innerHTML = '<i class="fas fa-bell" style="font-size:16px;"></i> ' + message + ' <button onclick="this.parentElement.remove()" style="background:none;border:none;color:#999;cursor:pointer;font-size:16px;margin-left:8px;">&times;</button>';
        document.body.appendChild(toast);
        
        setTimeout(function() { 
            toast.style.opacity = '0'; 
            toast.style.transform = 'translateX(100%)'; 
            toast.style.transition = 'all 0.3s'; 
            setTimeout(function() { toast.remove(); }, 300); 
        }, 8000);
    }

    function playNotificationSound() {
        try {
            var audio = new Audio('data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdH2JkZmPfG1jaXOAi5OXko2DfH2Ah4yQkZCMiIWEhYiLjo+PjYuJh4aGh4mLjI2NjIuKiYiIiImKi4yMjIuLioqJiYmKioqLi4uLi4qKiomKiomKiouLi4qLioqKi4qKi4uKi4uLiouKi4qKi4uLi4uLi4uKi4qLioqLi4uLi4uLi4uLi4qKi4uKi4uLi4uLi4uLi4uLi4uLi4uLi4uLi4uLi4uLi4uLi4uLi4uLi4uLi4uLi4uLi4uLi4s=');
            audio.volume = 0.3;
            audio.play();
        } catch(e) {}
    }

    function updateDashboardStats(data) {
        var statElements = document.querySelectorAll('.stat-value');
        if (statElements.length > 0) {
            statElements.forEach(function(el) {
                var label = el.closest('.stat-card');
                if (label) {
                    var labelText = label.querySelector('.stat-label');
                    if (labelText) {
                        if (labelText.textContent.toLowerCase().includes('order')) {
                            el.textContent = data.total_orders;
                        }
                        if (labelText.textContent.toLowerCase().includes('revenue')) {
                            el.textContent = 'LKR ' + Number(data.total_revenue).toLocaleString();
                        }
                        if (labelText.textContent.toLowerCase().includes('pending')) {
                            el.textContent = data.pending_orders;
                        }
                    }
                }
            });
        }
        
        // If on orders page or products page, reload after a short delay
        var ordersTable = document.getElementById('ordersTable');
        var productsTable = document.getElementById('productsTable');
        if (ordersTable || productsTable) {
            setTimeout(function() { location.reload(); }, 2000);
        }
    }

    // Start polling after page loads
    setTimeout(function() {
        checkForNewOrders(); // Initial check - just stores count
        setInterval(checkForNewOrders, pollInterval);
    }, 3000);
})();
</script>
