<?php
require_once './config/config.php';
require_once './lib/functions.php';

// Check if admin is logged in
if (!isLoggedIn()) {
    header("Location: " . MAIN_URL . "auth/login.php");
    exit;
}

$currentAdmin = getCurrentAdmin();



// Get online users (active in last 5 minutes)
function getOnlineUsers()
{
    global $conn;
    try {
        $sql = "SELECT * FROM user_activity_log 
               WHERE last_activity >= DATE_SUB(NOW(), INTERVAL 20 MINUTE)
                ORDER BY last_activity DESC";
        $result = $conn->query($sql);
        $users = [];
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
        return $users;
    } catch (Exception $e) {
        return [];
    }
}

// Get offline users (inactive for more than 5 minutes)
function getOfflineUsers()
{
    global $conn;
    try {
        $sql = "SELECT * FROM user_activity_log 
WHERE last_activity < DATE_SUB(NOW(), INTERVAL 1 MINUTE)
                ORDER BY last_activity DESC";
        $result = $conn->query($sql);
        $users = [];
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
        return $users;
    } catch (Exception $e) {
        return [];
    }
}

// Get all users from users table
function getAllUsers()
{
    global $conn;
    try {
        $sql = "SELECT 
                    u.id,
                    u.user_uid,
                    u.name,
                    u.phone,
                    u.email,
                    u.created_at,
                    l.last_activity,
                    l.current_page
                FROM users u
                LEFT JOIN user_activity_log l 
                    ON u.id = l.user_id
                AND l.last_activity = (
                    SELECT MAX(last_activity) 
                    FROM user_activity_log 
                    WHERE user_id = u.id
                )
                ORDER BY u.name ASC";

        $result = $conn->query($sql);
        $users = [];

        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }

        return $users;
    } catch (Exception $e) {
        return [];
    }
}

// Get activity statistics
function getActivityStats()
{
    global $conn;
    try {
        // Total users
        $total_result = $conn->query("SELECT COUNT(DISTINCT user_id) as count FROM user_activity_log");
        $total = $total_result->fetch_assoc();

        // Online now
        $online_result = $conn->query("
    SELECT COUNT(DISTINCT user_id) as count 
    FROM user_activity_log 
    WHERE last_activity >= DATE_SUB(NOW(), INTERVAL 20 MINUTE)
");
        $online = $online_result->fetch_assoc();

        // Active today
        $today_result = $conn->query("SELECT COUNT(DISTINCT user_id) as count FROM user_activity_log 
                                      WHERE DATE(last_activity) = CURDATE()");
        $today = $today_result->fetch_assoc();

        return [
            'total' => $total['count'] ?? 0,
            'online' => $online['count'] ?? 0,
            'today' => $today['count'] ?? 0
        ];
    } catch (Exception $e) {
        return ['total' => 0, 'online' => 0, 'today' => 0];
    }
}

$onlineUsers = getOnlineUsers();
$offlineUsers = getOfflineUsers();
$allUsers = getAllUsers();
$stats = getActivityStats();

// Get online user IDs for quick lookup
$onlineUserIds = array_column($onlineUsers, 'user_id');
?>

<!DOCTYPE html>
<html lang="en">
<style>
    /* Online card - green border */
    .online-card {
        border: 2px solid #10b981 !important;
        box-shadow: 0 0 10px rgba(16, 185, 129, 0.2);
    }

    /* Offline card - subtle */
    .offline-card {
        border: 1px solid #e2e8f0;
    }
</style>

<head>
    <?php template('head-tag'); ?>
    <meta name="user-id" content="<?= $currentAdmin['id'] ?>">

    <meta name="user-name" content="<?= $currentAdmin['username'] ?>">

    <title>User Activity - Admin Panel</title>

    <style>
        :root {
            --primary: #4f46e5;
            --secondary: #9333ea;
            --success: #10b981;
            --danger: #ef4444;
            --info: #3b82f6;
            --dark: #1e293b;
        }

        body {
            background: #f1f5f9;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        }

        .online-indicator {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 8px;
        }

        .online {
            background-color: #10b981;
            box-shadow: 0 0 5px #10b981;
        }

        .offline {
            background-color: #ef4444;
        }

        .user-card {
            transition: all 0.3s;
            border: 1px solid #e2e8f0;
        }

        .user-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            border-color: var(--primary);
        }

        .status-badge {
            padding: 4px 12px;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 600;
        }

        .stat-card {
            background: white;
            border-radius: 20px;
            padding: 20px;
            border: 1px solid #e2e8f0;
            transition: all 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }

        .btn-primary-custom {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border: none;
            color: white;
            padding: 8px 20px;
            border-radius: 12px;
            transition: all 0.3s;
        }

        .btn-primary-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(79, 70, 229, 0.3);
            color: white;
        }

        .nav-tabs .nav-link {
            color: var(--dark);
            font-weight: 500;
        }

        .nav-tabs .nav-link.active {
            color: var(--primary);
            border-bottom-color: var(--primary);
        }

        .last-active-time {
            font-size: 11px;
        }
    </style>
</head>

<body>
    <div class="container-fluid g-0 dashboard-container">
        <div class="row g-0" style="height: 100%;">

            <?php template('side-navbar'); ?>

            <div class="col main-content" style="flex: 1 1 0; min-width: 0;">

                <div class="topbar d-flex justify-content-between align-items-center">
                    <button class="btn btn-link menu-toggle d-md-none p-0 border-0" onclick="toggleSidebar()">
                        <i class="bi bi-list fs-2" style="color:#1e293b;"></i>
                    </button>
                    <div class="d-flex align-items-center gap-3">
                        <i class="bi bi-people-fill d-none d-md-inline-block fs-4" style="color:var(--primary);"></i>
                        <h5 class="mb-0 d-none d-md-block">User Activity Tracker</h5>
                    </div>
                    <div class="user-profile">
                        <span class="d-none d-sm-inline">
                            <?= htmlspecialchars($currentAdmin['username'] ?? 'Admin') ?>
                        </span>
                        <img src="https://ui-avatars.com/api/?name=<?= urlencode($currentAdmin['username'] ?? 'Admin') ?>&background=4f46e5&color=fff&size=40"
                            alt="avatar" class="avatar">
                    </div>
                </div>

                <div class="scrollable-content p-4">

                    <!-- Header -->
                    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-4 border-bottom">
                        <div>
                            <h2 class="mb-1 fw-bold" style="color: var(--dark);">
                                <i class="bi bi-people-fill text-primary me-2"></i>
                                Sales Team Activity Tracker
                            </h2>
                            <p class="text-secondary mb-0">Monitor sales team online/offline status in real-time</p>
                            <small class="text-muted">
                                <i class="bi bi-info-circle"></i>
                                Status updates are sent automatically from the sales team panel every 30 seconds
                            </small>
                        </div>
                        <button onclick="location.reload()" class="btn btn-primary-custom">
                            <i class="bi bi-arrow-repeat me-2"></i>Refresh
                        </button>
                    </div>

                    <!-- Statistics Cards -->
                    <div class="row mb-4">
                        <div class="col-md-4 mb-3">
                            <div class="stat-card">
                                <div class="d-flex align-items-center">
                                    <div class="bg-primary bg-opacity-10 p-3 rounded-circle me-3">
                                        <i class="bi bi-people fs-3 text-primary"></i>
                                    </div>
                                    <div>
                                        <h6 class="text-muted mb-1">Total Sales Team</h6>
                                        <h3 class="mb-0 fw-bold"><?= count($allUsers) ?></h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="stat-card">
                                <div class="d-flex align-items-center">
                                    <div class="bg-success bg-opacity-10 p-3 rounded-circle me-3">
                                        <i class="bi bi-circle-fill fs-3 text-success"></i>
                                    </div>
                                    <div>
                                        <h6 class="text-muted mb-1">Online Now</h6>
                                        <h3 class="mb-0 fw-bold text-success"><?= $stats['online'] ?></h3>
                                        <small class="text-muted">Active in last 5 min</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="stat-card">
                                <div class="d-flex align-items-center">
                                    <div class="bg-info bg-opacity-10 p-3 rounded-circle me-3">
                                        <i class="bi bi-calendar-check fs-3 text-info"></i>
                                    </div>
                                    <div>
                                        <h6 class="text-muted mb-1">Active Today</h6>
                                        <h3 class="mb-0 fw-bold"><?= $stats['today'] ?></h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>


                    <div class="row">
                        <?php if (!empty($allUsers)): ?>
                            <?php foreach ($allUsers as $user):
                                $isOnline = in_array($user['id'], $onlineUserIds);
                            ?>
                                <div class="col-md-6 col-lg-4 mb-3">
                                    <div class="card user-card shadow-sm h-100 <?= $isOnline ? 'online-card' : 'offline-card' ?>"
                                        onclick="openUserModal(<?= $user['id'] ?>)">
                                        <div class="card-body">

                                            <!-- USER INFO -->
                                            <div class="d-flex align-items-center mb-3">
                                                <div class="bg-<?= $isOnline ? 'primary' : 'secondary' ?> bg-opacity-10 rounded-circle p-3 me-3">
                                                    <i class="bi bi-person fs-4 text-<?= $isOnline ? 'primary' : 'secondary' ?>"></i>
                                                </div>
                                                <div>
                                                    <h6 class="mb-0 fw-bold"><?= htmlspecialchars($user['name']) ?></h6>
                                                    <small class="text-muted"><?= htmlspecialchars($user['user_uid']) ?></small>
                                                </div>
                                            </div>

                                            <!-- STATUS -->
                                            <div class="mb-2">
                                                <span class="online-indicator <?= $isOnline ? 'online' : 'offline' ?>"></span>
                                                <span class="status-badge bg-<?= $isOnline ? 'success' : 'danger' ?> bg-opacity-10 text-<?= $isOnline ? 'success' : 'danger' ?>">
                                                    <?= $isOnline ? 'Online' : 'Offline' ?>
                                                </span>

                                                <a href="<?= MAIN_URL ?>ajax/auth/direct-login.php?user_id=<?= $user['id'] ?>"
                                                    class="btn btn-success btn-sm"
                                                  >
                                                    
                                                    Login
                                                </a>
                                            </div>

                                            <!-- LAST SEEN -->
                                            <div class="small text-muted mt-1">
                                                <i class="bi bi-clock"></i>
                                                <?= $isOnline
                                                    ? 'Active now'
                                                    : (!empty($user['last_activity'])
                                                        ? 'Last seen: ' . date('d M H:i', strtotime($user['last_activity']))
                                                        : 'No activity') ?>
                                            </div>

                                            <!-- LAST PAGE -->
                                            <div class="small text-muted mt-1">
                                                <i class="bi bi-file-text"></i>
                                                <?= !empty($user['current_page'])
                                                    ? basename($user['current_page'])
                                                    : 'No page data' ?>
                                            </div>

                                            <!-- PHONE -->
                                            <div class="small text-muted mt-1">
                                                <i class="bi bi-phone"></i> <?= htmlspecialchars($user['phone']) ?>
                                            </div>

                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="col-12 text-center py-4">
                                <p class="text-muted">No sales team members found</p>
                            </div>
                        <?php endif; ?>
                    </div>


                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        function openUserModal(userId) {

            // Show modal
            var modal = new bootstrap.Modal(document.getElementById('userModal'));
            modal.show();

            // Loading state
            document.getElementById('modalContent').innerHTML = "Loading...";

            // Fetch data
            fetch("ajax/get-user-details.php?user_id=" + userId)
                .then(res => res.text())
                .then(data => {
                    document.getElementById('modalContent').innerHTML = data;
                })
                .catch(() => {
                    document.getElementById('modalContent').innerHTML = "Error loading data";
                });
        }
    </script>
    <script>
        //  const MAIN_URL = "<?= MAIN_URL ?>";

        function toggleSidebar() {
            const sidebar = document.getElementById('mainSidebar');
            const body = document.body;
            if (!sidebar) return;

            sidebar.classList.toggle('active');

            if (window.innerWidth <= 768) {
                if (sidebar.classList.contains('active')) {
                    if (!document.getElementById('sidebarOverlay')) {
                        const overlay = document.createElement('div');
                        overlay.id = 'sidebarOverlay';
                        overlay.style.position = 'fixed';
                        overlay.style.inset = '0';
                        overlay.style.background = 'rgba(0,0,0,0.3)';
                        overlay.style.backdropFilter = 'blur(3px)';
                        overlay.style.zIndex = '1040';
                        overlay.addEventListener('click', toggleSidebar);
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

        window.addEventListener('resize', function() {
            const sidebar = document.getElementById('mainSidebar');
            const overlay = document.getElementById('sidebarOverlay');
            if (window.innerWidth > 768) {
                if (sidebar && sidebar.classList.contains('active')) {
                    sidebar.classList.remove('active');
                }
                if (overlay) overlay.remove();
                document.body.classList.remove('sidebar-open');
            }
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                const sidebar = document.getElementById('mainSidebar');
                if (sidebar && sidebar.classList.contains('active')) {
                    toggleSidebar();
                }
            }
        });

        // Auto refresh every 30 seconds
        setInterval(function() {
            location.reload();
        }, 30000);


    function openLogin(userId) {
    var url = "<?= MAIN_URL ?>ajax/auth/direct-login.php?user_id=" + userId;

    var popup = window.open(url, '_blank', 'width=1200,height=800');

    if (!popup || popup.closed || typeof popup.closed == 'undefined') {
        window.location.href = url;
    }
}
    </script>

    <!-- User Details Modal -->
    <div class="modal fade" id="userModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title">Sales Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body" id="modalContent">
                    <p>Loading...</p>
                </div>

            </div>
        </div>
    </div>

</body>


</html>