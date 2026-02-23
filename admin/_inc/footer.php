    </main>
    </div>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var sidebar = document.getElementById('admin-nav-sidebar');
        var sidebarToggle = document.getElementById('admin-sidebar-toggle');
        var notifBtn = document.getElementById('admin-notifications-btn');
        var notifDropdown = document.getElementById('admin-notifications-dropdown');
        var userMenu = document.getElementById('admin-user-menu');
        var userDropdown = document.getElementById('admin-user-dropdown');
        
        function closeDropdowns() {
            if (notifDropdown) notifDropdown.classList.remove('open');
            if (userDropdown) userDropdown.classList.remove('open');
        }
        
        document.addEventListener('click', function(e) {
            if (!sidebar || !sidebarToggle || !notifBtn || !notifDropdown || !userMenu || !userDropdown) return;
            var t = e.target;
            if (sidebarToggle.contains(t)) {
                e.stopPropagation();
                e.preventDefault();
                sidebar.classList.toggle('open');
                closeDropdowns();
                return;
            }
            if (notifBtn.contains(t) && !notifDropdown.contains(t)) {
                e.stopPropagation();
                e.preventDefault();
                notifDropdown.classList.toggle('open');
                if (userDropdown) userDropdown.classList.remove('open');
                return;
            }
            if (userMenu.contains(t) && !userDropdown.contains(t)) {
                e.stopPropagation();
                e.preventDefault();
                userDropdown.classList.toggle('open');
                if (notifDropdown) notifDropdown.classList.remove('open');
                if (sidebar && window.innerWidth <= 992) sidebar.classList.remove('open');
                return;
            }
            if (!notifBtn.contains(t) && !notifDropdown.contains(t) && !userMenu.contains(t) && !userDropdown.contains(t)) {
                if (window.innerWidth <= 992 && !sidebar.contains(t)) sidebar.classList.remove('open');
                closeDropdowns();
            }
        }, true);
    });
    </script>
</body>
</html>

