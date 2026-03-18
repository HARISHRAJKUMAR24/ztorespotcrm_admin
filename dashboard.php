<?php
require_once './config/config.php';
require_once './lib/functions.php';

// CHECK IF USER IS LOGGED IN - IF NOT, REDIRECT TO LOGIN
if (!isLoggedIn()) {
    header("Location: " . MAIN_URL . "auth/login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php
    // Load head template
    template('head-tag');
    ?>
</head>

<body>
    <div class="container-fluid g-0 dashboard-container">
        <div class="row g-0" style="height: 100%;">

            <!-- SIDEBAR with close icon (mobile) -->
            <?php
            template('side-navbar');
            ?>

            <!-- MAIN CONTENT with fixed topbar and scrollable area -->
            <div class="col main-content" style="flex: 1 1 0; min-width: 0;">

                <!-- TOPBAR with menu toggle + user (fixed at top) -->
                <div class="topbar d-flex justify-content-between align-items-center">
                    <button class="btn btn-link menu-toggle d-md-none p-0 border-0" onclick="toggleSidebar()">
                        <i class="bi bi-list fs-2" style="color:#1e293b;"></i>
                    </button>
                    <div class="d-flex align-items-center gap-3">
                        <!-- breadcrumb / page title appears on medium+ -->
                        <i class="bi bi-grid-fill d-none d-md-inline-block fs-4" style="color:#4f46e5;"></i>
                        <h5 class="mb-0 d-none d-md-block">Dashboard</h5>
                    </div>
                    <div class="user-profile">
                        <span class="d-none d-sm-inline">Alex</span>
                        <img src="https://i.pravatar.cc/120?u=100" alt="avatar" class="avatar">
                    </div>
                </div>

                <!-- SCROLLABLE CONTENT AREA (ONLY this scrolls when mouse is active here) -->
                <div class="scrollable-content">

                    <!-- METRIC CARDS (row) -->
                    <div class="row g-4">
                        <div class="col-lg-3 col-md-6 col-sm-6">
                            <div class="card-box bg1">
                                <h6><i class="bi bi-bag-check me-1"></i> Total Orders</h6>
                                <h3>1,250</h3>
                                <small class="opacity-75">↑ 12% vs last month</small>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6 col-sm-6">
                            <div class="card-box bg2">
                                <h6><i class="bi bi-currency-rupee me-1"></i> Revenue</h6>
                                <h3>₹85,000</h3>
                                <small class="opacity-75">↑ 8.2%</small>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6 col-sm-6">
                            <div class="card-box bg3">
                                <h6><i class="bi bi-person-up me-1"></i> Customers</h6>
                                <h3>320</h3>
                                <small class="opacity-75">+24 new</small>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6 col-sm-6">
                            <div class="card-box bg4">
                                <h6><i class="bi bi-hourglass-split me-1"></i> Pending</h6>
                                <h3>45</h3>
                                <small class="opacity-75">requires attention</small>
                            </div>
                        </div>
                    </div>

                    <!-- RECENT ORDERS + extra filter -->
                    <div class="recent-header">
                        <h5><i class="bi bi-clock-history me-2" style="color:#4f46e5;"></i> Recent orders</h5>
                        <div>
                            <span class="badge bg-light text-dark p-3 px-4 rounded-pill"><i class="bi bi-funnel me-1"></i> This week</span>
                        </div>
                    </div>

                    <!-- MODERN TABLE (frosted) -->
                    <div class="table-wrap">
                        <div class="table-responsive">
                            <table class="table align-middle">
                                <thead>
                                    <tr>
                                        <th>Order ID</th>
                                        <th>Customer</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th style="width: 80px;"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><span class="fw-bold">#1001</span></td>
                                        <td><i class="bi bi-person-circle me-2"></i>Harish</td>
                                        <td><span class="fw-semibold">₹1,200</span></td>
                                        <td><span class="badge bg-success">Paid</span></td>
                                        <td><i class="bi bi-eye text-secondary"></i></td>
                                    </tr>
                                    <tr>
                                        <td><span class="fw-bold">#1002</span></td>
                                        <td><i class="bi bi-person-circle me-2"></i>Ravi</td>
                                        <td><span class="fw-semibold">₹850</span></td>
                                        <td><span class="badge bg-warning">Pending</span></td>
                                        <td><i class="bi bi-eye text-secondary"></i></td>
                                    </tr>
                                    <tr>
                                        <td><span class="fw-bold">#1003</span></td>
                                        <td><i class="bi bi-person-circle me-2"></i>Kumar</td>
                                        <td><span class="fw-semibold">₹2,300</span></td>
                                        <td><span class="badge bg-danger">Failed</span></td>
                                        <td><i class="bi bi-eye text-secondary"></i></td>
                                    </tr>
                                    <tr>
                                        <td><span class="fw-bold">#1004</span></td>
                                        <td><i class="bi bi-person-circle me-2"></i>Divya</td>
                                        <td><span class="fw-semibold">₹3,420</span></td>
                                        <td><span class="badge bg-success">Paid</span></td>
                                        <td><i class="bi bi-eye text-secondary"></i></td>
                                    </tr>
                                    <tr>
                                        <td><span class="fw-bold">#1005</span></td>
                                        <td><i class="bi bi-person-circle me-2"></i>Priya</td>
                                        <td><span class="fw-semibold">₹5,670</span></td>
                                        <td><span class="badge bg-success">Paid</span></td>
                                        <td><i class="bi bi-eye text-secondary"></i></td>
                                    </tr>
                                    <tr>
                                        <td><span class="fw-bold">#1006</span></td>
                                        <td><i class="bi bi-person-circle me-2"></i>Rahul</td>
                                        <td><span class="fw-semibold">₹950</span></td>
                                        <td><span class="badge bg-warning">Pending</span></td>
                                        <td><i class="bi bi-eye text-secondary"></i></td>
                                    </tr>
                                    <tr>
                                        <td><span class="fw-bold">#1007</span></td>
                                        <td><i class="bi bi-person-circle me-2"></i>Anjali</td>
                                        <td><span class="fw-semibold">₹4,230</span></td>
                                        <td><span class="badge bg-success">Paid</span></td>
                                        <td><i class="bi bi-eye text-secondary"></i></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- subtle extra stats row (optional) -->
                    <div class="row mt-5 g-3">
                        <div class="col-md-6">
                            <div class="p-4 bg-white rounded-5 shadow-sm d-flex align-items-center gap-3">
                                <div class="bg-primary bg-opacity-10 p-3 rounded-4"><i class="bi bi-truck fs-2" style="color:#4f46e5;"></i></div>
                                <div><span class="text-secondary">delivery success</span> <br> <strong class="fs-5">98.3%</strong></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-4 bg-white rounded-5 shadow-sm d-flex align-items-center gap-3">
                                <div class="bg-success bg-opacity-10 p-3 rounded-4"><i class="bi bi-star fs-2" style="color:#0f766e;"></i></div>
                                <div><span class="text-secondary">avg. rating</span> <br> <strong class="fs-5">4.8 ★</strong></div>
                            </div>
                        </div>
                    </div>

                </div> <!-- end scrollable content area -->
            </div> <!-- end main col -->
        </div> <!-- end row -->
    </div> <!-- end container-fluid -->

    <!-- overlay + sidebar toggle script -->
    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('mainSidebar');
            const body = document.body;
            sidebar.classList.toggle('active');

            // add/remove overlay on mobile
            if (window.innerWidth <= 768) {
                if (sidebar.classList.contains('active')) {
                    // create overlay if not exists
                    if (!document.getElementById('sidebarOverlay')) {
                        const overlay = document.createElement('div');
                        overlay.id = 'sidebarOverlay';
                        overlay.style.position = 'fixed';
                        overlay.style.inset = '0';
                        overlay.style.background = 'rgba(0,0,0,0.3)';
                        overlay.style.backdropFilter = 'blur(3px)';
                        overlay.style.zIndex = '1040';
                        overlay.addEventListener('click', function() {
                            toggleSidebar(); // close when clicking overlay
                        });
                        body.appendChild(overlay);
                        body.classList.add('sidebar-open');
                    }
                } else {
                    const overlay = document.getElementById('sidebarOverlay');
                    if (overlay) overlay.remove();
                    body.classList.remove('sidebar-open');
                }
            }
        }

        // close sidebar if window resizes beyond mobile and overlay exists
        window.addEventListener('resize', function() {
            const sidebar = document.getElementById('mainSidebar');
            const overlay = document.getElementById('sidebarOverlay');
            if (window.innerWidth > 768) {
                if (sidebar.classList.contains('active')) {
                    sidebar.classList.remove('active');
                }
                if (overlay) overlay.remove();
                document.body.classList.remove('sidebar-open');
            } else {
                // on mobile, if sidebar becomes inactive but overlay left (rare)
                if (!sidebar.classList.contains('active') && overlay) {
                    overlay.remove();
                    document.body.classList.remove('sidebar-open');
                }
            }
        });

        // optional: close on escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                const sidebar = document.getElementById('mainSidebar');
                if (sidebar.classList.contains('active')) {
                    toggleSidebar();
                }
            }
        });
    </script>
</body>

</html>