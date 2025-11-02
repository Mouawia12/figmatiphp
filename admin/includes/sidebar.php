<!-- Sidenav -->
<nav class="sidenav navbar navbar-vertical fixed-right navbar-expand-xs navbar-light bg-white" id="sidenav-main">
    <div class="scrollbar-inner">
        <!-- Brand -->
        <div class="sidenav-header d-flex align-items-center">
            <a class="navbar-brand" href="/admin">
                <img src="/assets/img/brand/blue.png" class="navbar-brand-img" alt="...">
            </a>
            <div class="ml-auto">
                <!-- Sidenav toggler -->
                <div class="sidenav-toggler d-none d-xl-block" data-action="sidenav-unpin" data-target="#sidenav-main">
                    <div class="sidenav-toggler-inner">
                        <i class="sidenav-toggler-line"></i>
                        <i class="sidenav-toggler-line"></i>
                        <i class="sidenav-toggler-line"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="navbar-inner">
            <!-- Collapse -->
            <div class="collapse navbar-collapse" id="sidenav-collapse-main">
                <!-- Nav items -->
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link <?= ($active_menu ?? '') === 'dashboard' ? 'active' : '' ?>" href="/admin">
                            <i class="ni ni-tv-2 text-primary"></i>
                            <span class="nav-link-text">الرئيسية</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= ($active_menu ?? '') === 'requests' ? 'active' : '' ?>" href="/admin/requests.php">
                            <i class="ni ni-bullet-list-67 text-orange"></i>
                            <span class="nav-link-text">الطلبات</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= ($active_menu ?? '') === 'forms' ? 'active' : '' ?>" href="/admin/forms.php">
                            <i class="ni ni-single-copy-04 text-info"></i>
                            <span class="nav-link-text">النماذج</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= ($active_menu ?? '') === 'settings' ? 'active' : '' ?>" href="/admin/settings.php">
                            <i class="ni ni-settings-gear-65 text-default"></i>
                            <span class="nav-link-text">الإعدادات</span>
                        </a>
                    </li>
                </ul>
                
                <!-- Divider -->
                <hr class="my-3">
                
                <!-- Navigation -->
                <div class="mt-3">
                    <h6 class="navbar-heading p-0 text-muted">
                        <span class="docs-normal">التنقل</span>
                    </h6>
                    <ul class="navbar-nav mb-md-3">
                        <li class="nav-item">
                            <a class="nav-link" href="/" target="_blank">
                                <i class="ni ni-spaceship"></i>
                                <span class="nav-link-text">عرض الموقع</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/admin/logout.php">
                                <i class="ni ni-user-run"></i>
                                <span class="nav-link-text">تسجيل خروج</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</nav>
