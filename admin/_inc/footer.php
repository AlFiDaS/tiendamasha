    </main>
    </div>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var sidebar = document.getElementById('admin-nav-sidebar');
        var mobileToggle = document.getElementById('admin-mobile-toggle');
        var backdrop = document.getElementById('admin-sidebar-backdrop');
        var notifBtn = document.getElementById('admin-notifications-btn');
        var notifDropdown = document.getElementById('admin-notifications-dropdown');
        var mobileNotifBtn = document.getElementById('admin-mobile-notif-btn');
        var mobileNotifDropdown = document.getElementById('admin-mobile-notif-dropdown');
        var userMenu = document.getElementById('admin-user-menu');
        var userDropdown = document.getElementById('admin-user-dropdown');
        
        function closeSidebar() {
            if (sidebar) sidebar.classList.remove('open');
            if (backdrop) backdrop.classList.remove('open');
            if (mobileToggle) mobileToggle.classList.remove('active');
        }
        
        function closeAllDropdowns() {
            if (notifDropdown) notifDropdown.classList.remove('open');
            if (mobileNotifDropdown) mobileNotifDropdown.classList.remove('open');
            if (userDropdown) userDropdown.classList.remove('open');
        }
        
        function toggleSidebar() {
            var isOpen = sidebar.classList.toggle('open');
            if (backdrop) backdrop.classList.toggle('open', isOpen);
            if (mobileToggle) mobileToggle.classList.toggle('active', isOpen);
            closeAllDropdowns();
        }

        if (mobileToggle) {
            mobileToggle.addEventListener('click', function(e) {
                e.stopPropagation();
                toggleSidebar();
            });
        }
        
        if (backdrop) {
            backdrop.addEventListener('click', function() {
                closeSidebar();
                closeAllDropdowns();
            });
        }

        if (mobileNotifBtn) {
            mobileNotifBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                e.preventDefault();
                var isOpen = mobileNotifDropdown.classList.toggle('open');
                if (userDropdown) userDropdown.classList.remove('open');
                closeSidebar();
            });
        }
        
        document.addEventListener('click', function(e) {
            var t = e.target;

            if (notifBtn && notifBtn.contains(t) && !(notifDropdown && notifDropdown.contains(t))) {
                e.stopPropagation();
                e.preventDefault();
                notifDropdown.classList.toggle('open');
                if (userDropdown) userDropdown.classList.remove('open');
                return;
            }
            if (userMenu && userMenu.contains(t) && !(userDropdown && userDropdown.contains(t))) {
                e.stopPropagation();
                e.preventDefault();
                userDropdown.classList.toggle('open');
                if (notifDropdown) notifDropdown.classList.remove('open');
                return;
            }

            var inNotif = notifBtn && (notifBtn.contains(t) || (notifDropdown && notifDropdown.contains(t)));
            var inMobileNotif = mobileNotifBtn && (mobileNotifBtn.contains(t) || (mobileNotifDropdown && mobileNotifDropdown.contains(t)));
            var inUser = userMenu && (userMenu.contains(t) || (userDropdown && userDropdown.contains(t)));

            if (!inNotif && !inMobileNotif && !inUser) {
                closeAllDropdowns();
            }
            if (sidebar && !sidebar.contains(t) && !(mobileToggle && mobileToggle.contains(t)) && window.innerWidth <= 992) {
                closeSidebar();
            }
        }, true);
    });
    </script>
</body>
</html>
