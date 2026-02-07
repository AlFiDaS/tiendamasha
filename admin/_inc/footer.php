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
        
        if (sidebarToggle && sidebar) {
            sidebarToggle.addEventListener('click', function() {
                sidebar.classList.toggle('open');
            });
            document.addEventListener('click', function(e) {
                if (!sidebar.contains(e.target) && !sidebarToggle.contains(e.target) && window.innerWidth <= 992) {
                    sidebar.classList.remove('open');
                }
            });
        }
        
        if (notifBtn && notifDropdown) {
            notifBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                notifDropdown.classList.toggle('open');
                if (userDropdown) userDropdown.classList.remove('open');
            });
        }
        
        if (userMenu && userDropdown) {
            userMenu.addEventListener('click', function(e) {
                e.stopPropagation();
                userDropdown.classList.toggle('open');
                if (notifDropdown) notifDropdown.classList.remove('open');
            });
        }
        
        document.addEventListener('click', function() {
            if (notifDropdown) notifDropdown.classList.remove('open');
            if (userDropdown) userDropdown.classList.remove('open');
        });
    });
    </script>
</body>
</html>

