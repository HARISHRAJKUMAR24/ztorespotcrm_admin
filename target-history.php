<?php
require_once './config/config.php';
require_once './lib/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header("Location: " . MAIN_URL . "auth/login.php");
    exit;
}

// Get current admin info
$currentAdmin = getCurrentAdmin();

// Get filter parameters
$selected_user_uid = isset($_GET['user_uid']) ? $_GET['user_uid'] : '';
$selected_type = isset($_GET['type']) ? $_GET['type'] : '';
$user_name = isset($_GET['name']) ? urldecode($_GET['name']) : '';

// Function to get all sellers with their target summary
function getAllSellersWithTargetSummary() {
    global $conn;
    
    $sql = "SELECT 
            u.id,
            u.user_uid,
            u.name,
            u.phone,
            u.email,
            u.profile_image,
            COUNT(ts.id) as total_targets,
            SUM(CASE WHEN ts.status = 'active' THEN 1 ELSE 0 END) as active_targets,
            SUM(CASE WHEN ts.status = 'completed' THEN 1 ELSE 0 END) as completed_targets,
            SUM(CASE WHEN ts.achievement_percentage >= 100 THEN 1 ELSE 0 END) as achieved_targets,
            COALESCE(AVG(ts.achievement_percentage), 0) as avg_achievement,
            COALESCE(SUM(ts.target_amount), 0) as total_target_amount,
            COALESCE(SUM(ts.achieved_amount), 0) as total_achieved_amount
            FROM users u
            LEFT JOIN target_settings ts ON ts.user_uid = u.user_uid AND ts.target_type = 'individual'
            WHERE u.id > 0
            GROUP BY u.id, u.user_uid, u.name, u.phone, u.email, u.profile_image
            ORDER BY u.name ASC";
    
    $result = $conn->query($sql);
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Function to get team targets summary
function getTeamTargetsSummary() {
    global $conn;
    
    $sql = "SELECT 
            COUNT(*) as total_targets,
            SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_targets,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_targets,
            SUM(CASE WHEN achievement_percentage >= 100 THEN 1 ELSE 0 END) as achieved_targets,
            COALESCE(AVG(achievement_percentage), 0) as avg_achievement,
            COALESCE(SUM(target_amount), 0) as total_target_amount,
            COALESCE(SUM(achieved_amount), 0) as total_achieved_amount
            FROM target_settings 
            WHERE target_type = 'team'";
    
    $result = $conn->query($sql);
    return $result->fetch_assoc();
}

// Function to get individual seller's target history
function getSellerTargetHistory($user_uid) {
    global $conn;
    
    $sql = "SELECT ts.*,
            adm.username as created_by_name,
            DATE_FORMAT(ts.start_date, '%d %b %Y') as start_date_formatted,
            DATE_FORMAT(ts.end_date, '%d %b %Y') as end_date_formatted,
            CASE 
                WHEN ts.status = 'active' AND ts.end_date < CURDATE() THEN 'overdue'
                WHEN ts.status = 'active' AND ts.achievement_percentage >= 100 THEN 'achieved'
                ELSE ts.status
            END as display_status
            FROM target_settings ts
            LEFT JOIN admin_users adm ON ts.created_by = adm.id
            WHERE ts.user_uid = ? AND ts.target_type = 'individual'
            ORDER BY ts.created_at DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $user_uid);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Function to get team target history
function getTeamTargetHistory() {
    global $conn;
    
    $sql = "SELECT ts.*,
            adm.username as created_by_name,
            DATE_FORMAT(ts.start_date, '%d %b %Y') as start_date_formatted,
            DATE_FORMAT(ts.end_date, '%d %b %Y') as end_date_formatted,
            CASE 
                WHEN ts.status = 'active' AND ts.end_date < CURDATE() THEN 'overdue'
                WHEN ts.status = 'active' AND ts.achievement_percentage >= 100 THEN 'achieved'
                ELSE ts.status
            END as display_status
            FROM target_settings ts
            LEFT JOIN admin_users adm ON ts.created_by = adm.id
            WHERE ts.target_type = 'team'
            ORDER BY ts.created_at DESC";
    
    $result = $conn->query($sql);
    return $result->fetch_all(MYSQLI_ASSOC);
}

$sellers = getAllSellersWithTargetSummary();
$teamSummary = getTeamTargetsSummary();
$sellerHistory = [];
$teamHistory = [];

if (!empty($selected_user_uid) && $selected_type === 'individual') {
    $sellerHistory = getSellerTargetHistory($selected_user_uid);
} elseif ($selected_type === 'team') {
    $teamHistory = getTeamTargetHistory();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php template('head-tag'); ?>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- SweetAlert2 -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        :root {
            --primary: #4f46e5;
            --secondary: #9333ea;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --info: #3b82f6;
            --dark: #1e293b;
            --light: #f8fafc;
        }

        body {
            background: #f1f5f9;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        }

        /* Seller Card Styles */
        .seller-card {
            background: white;
            border-radius: 20px;
            padding: 20px;
            margin-bottom: 20px;
            border: 1px solid #e2e8f0;
            transition: all 0.3s;
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }

        .seller-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            border-color: var(--primary);
        }

        .seller-avatar {
            width: 70px;
            height: 70px;
            border-radius: 20px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 28px;
        }

        .stat-badge {
            background: #f1f5f9;
            padding: 5px 12px;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 500;
        }

        .progress {
            height: 6px;
            border-radius: 10px;
            background: #e2e8f0;
        }

        .progress-bar {
            background: linear-gradient(90deg, var(--primary), var(--secondary));
            border-radius: 10px;
        }

        /* Target History Card */
        .history-card {
            background: white;
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 20px;
            border: 1px solid #e2e8f0;
            transition: all 0.3s;
        }

        .history-card:hover {
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        }

        .status-badge {
            padding: 4px 12px;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-active {
            background: #eef2ff;
            color: var(--primary);
        }

        .status-completed {
            background: #d1fae5;
            color: var(--success);
        }

        .status-cancelled {
            background: #fee2e2;
            color: var(--danger);
        }

        .status-achieved {
            background: #d1fae5;
            color: var(--success);
        }

        .status-overdue {
            background: #ffedd5;
            color: var(--warning);
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
            margin-bottom: 20px;
            transition: all 0.3s;
        }

        .back-link:hover {
            transform: translateX(-5px);
            color: var(--secondary);
        }

        .team-card {
            background: linear-gradient(135deg, var(--info), #06b6d4);
            color: white;
        }

        .team-card .stat-badge {
            background: rgba(255,255,255,0.2);
            color: white;
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

        .empty-state {
            text-align: center;
            padding: 60px;
            background: white;
            border-radius: 20px;
        }

        .empty-state i {
            font-size: 60px;
            color: #cbd5e1;
            margin-bottom: 20px;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .seller-card, .history-card {
            animation: slideIn 0.3s ease;
        }
    </style>
</head>

<body>
    <div class="container-fluid g-0 dashboard-container">
        <div class="row g-0" style="height: 100%;">

            <!-- SIDEBAR -->
            <?php template('side-navbar'); ?>

            <!-- MAIN CONTENT -->
            <div class="col main-content" style="flex: 1 1 0; min-width: 0;">

                <!-- TOPBAR -->
                <div class="topbar d-flex justify-content-between align-items-center">
                    <button class="btn btn-link menu-toggle d-md-none p-0 border-0" onclick="toggleSidebar()">
                        <i class="bi bi-list fs-2" style="color:#1e293b;"></i>
                    </button>
                    <div class="d-flex align-items-center gap-3">
                        <i class="bi bi-clock-history d-none d-md-inline-block fs-4" style="color:var(--primary);"></i>
                        <h5 class="mb-0 d-none d-md-block">Target History</h5>
                    </div>
                    <div class="user-profile">
                        <span class="d-none d-sm-inline">
                            <?= htmlspecialchars($currentAdmin['username'] ?? 'Admin') ?>
                        </span>
                        <img src="https://ui-avatars.com/api/?name=<?= urlencode($currentAdmin['username'] ?? 'Admin') ?>&background=4f46e5&color=fff&size=40"
                            alt="avatar" class="avatar">
                    </div>
                </div>

                <!-- SCROLLABLE CONTENT -->
                <div class="scrollable-content p-4">

                    <?php if (empty($selected_user_uid) && empty($selected_type)): ?>
                        <!-- Main View: Show all sellers and team card -->
                        
                        <!-- Page Header -->
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div>
                                <h2 class="mb-1 fw-bold" style="color: var(--dark);">
                                    <i class="bi bi-people me-2" style="color: var(--primary);"></i>
                                    Sales Team Performance
                                </h2>
                                <p class="text-secondary mb-0">Click on any seller to view their target history</p>
                            </div>
                            <a href="<?= MAIN_URL ?>target-settings.php" class="btn btn-primary-custom">
                                <i class="bi bi-plus-circle me-2"></i>Add New Target
                            </a>
                        </div>

                        <!-- Team Target Card -->
                        <div class="seller-card team-card mb-4" onclick="viewTeamHistory()">
                            <div class="row align-items-center">
                                <div class="col-auto">
                                    <div class="seller-avatar" style="background: rgba(255,255,255,0.2);">
                                        <i class="bi bi-people-fill fs-1"></i>
                                    </div>
                                </div>
                                <div class="col">
                                    <div class="d-flex justify-content-between align-items-start flex-wrap">
                                        <div>
                                            <h4 class="mb-1 fw-bold">Team Targets</h4>
                                            <p class="mb-0 opacity-75">Overall team performance</p>
                                        </div>
                                        <div class="text-end">
                                            <div class="stat-badge mb-2">
                                                <i class="bi bi-tag me-1"></i><?= $teamSummary['total_targets'] ?? 0 ?> Total Targets
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row mt-3">
                                        <div class="col-md-3 col-6 mb-2">
                                            <small class="opacity-75">Active</small>
                                            <h5 class="mb-0"><?= $teamSummary['active_targets'] ?? 0 ?></h5>
                                        </div>
                                        <div class="col-md-3 col-6 mb-2">
                                            <small class="opacity-75">Completed</small>
                                            <h5 class="mb-0"><?= $teamSummary['completed_targets'] ?? 0 ?></h5>
                                        </div>
                                        <div class="col-md-3 col-6 mb-2">
                                            <small class="opacity-75">Achieved</small>
                                            <h5 class="mb-0"><?= $teamSummary['achieved_targets'] ?? 0 ?></h5>
                                        </div>
                                        <div class="col-md-3 col-6 mb-2">
                                            <small class="opacity-75">Avg Achievement</small>
                                            <h5 class="mb-0"><?= round($teamSummary['avg_achievement'] ?? 0) ?>%</h5>
                                        </div>
                                    </div>
                                    
                                    <div class="mt-2">
                                        <div class="d-flex justify-content-between small mb-1">
                                            <span>Overall Progress</span>
                                            <span>₹<?= number_format($teamSummary['total_achieved_amount'] ?? 0, 0) ?> / ₹<?= number_format($teamSummary['total_target_amount'] ?? 0, 0) ?></span>
                                        </div>
                                        <div class="progress">
                                            <?php $teamPercent = ($teamSummary['total_target_amount'] > 0) ? ($teamSummary['total_achieved_amount'] / $teamSummary['total_target_amount']) * 100 : 0; ?>
                                            <div class="progress-bar" style="width: <?= round($teamPercent) ?>%"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Individual Sellers Cards -->
                        <h4 class="mb-3 fw-semibold">
                            <i class="bi bi-person-badge me-2" style="color: var(--primary);"></i>
                            Individual Sellers
                        </h4>
                        
                        <div class="row">
                            <?php if (!empty($sellers)): ?>
                                <?php foreach ($sellers as $seller): ?>
                                    <div class="col-lg-6 col-xl-4 mb-4">
                                        <div class="seller-card" onclick="viewSellerHistory('<?= $seller['user_uid'] ?>', '<?= addslashes($seller['name']) ?>')">
                                            <div class="d-flex align-items-start gap-3">
                                                <div class="seller-avatar">
                                                    <?= strtoupper(substr($seller['name'], 0, 2)) ?>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <div class="d-flex justify-content-between align-items-start">
                                                        <div>
                                                            <h5 class="mb-0 fw-semibold"><?= htmlspecialchars($seller['name']) ?></h5>
                                                            <small class="text-secondary"><?= htmlspecialchars($seller['user_uid']) ?></small>
                                                        </div>
                                                        <div class="stat-badge">
                                                            <i class="bi bi-tag me-1"></i><?= $seller['total_targets'] ?? 0 ?> Targets
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="row mt-3">
                                                        <div class="col-4">
                                                            <small class="text-secondary">Active</small>
                                                            <h6 class="mb-0"><?= $seller['active_targets'] ?? 0 ?></h6>
                                                        </div>
                                                        <div class="col-4">
                                                            <small class="text-secondary">Completed</small>
                                                            <h6 class="mb-0"><?= $seller['completed_targets'] ?? 0 ?></h6>
                                                        </div>
                                                        <div class="col-4">
                                                            <small class="text-secondary">Achieved</small>
                                                            <h6 class="mb-0"><?= $seller['achieved_targets'] ?? 0 ?></h6>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="mt-2">
                                                        <div class="d-flex justify-content-between small mb-1">
                                                            <span>Achievement Rate</span>
                                                            <span class="fw-bold"><?= round($seller['avg_achievement'] ?? 0) ?>%</span>
                                                        </div>
                                                        <div class="progress">
                                                            <div class="progress-bar" style="width: <?= round($seller['avg_achievement'] ?? 0) ?>%"></div>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="mt-2 text-end">
                                                        <small class="text-primary">
                                                            <i class="bi bi-eye"></i> Click to view history →
                                                        </small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="col-12">
                                    <div class="empty-state">
                                        <i class="bi bi-person-x"></i>
                                        <h5>No Sellers Found</h5>
                                        <p class="text-secondary">No sales persons available in the system.</p>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>

                    <?php elseif ($selected_type === 'team'): ?>
                        <!-- Team Target History View -->
                        
                        <a href="<?= MAIN_URL ?>target-history.php" class="back-link">
                            <i class="bi bi-arrow-left-circle"></i> Back to All Sellers
                        </a>
                        
                        <div class="d-flex align-items-center gap-3 mb-4">
                            <div class="seller-avatar" style="background: linear-gradient(135deg, var(--info), #06b6d4); width: 60px; height: 60px;">
                                <i class="bi bi-people-fill fs-2"></i>
                            </div>
                            <div>
                                <h2 class="mb-1 fw-bold" style="color: var(--dark);">Team Targets History</h2>
                                <p class="text-secondary mb-0">Complete history of all team targets</p>
                            </div>
                        </div>
                        
                        <?php if (!empty($teamHistory)): ?>
                            <?php foreach ($teamHistory as $target): ?>
                                <div class="history-card">
                                    <div class="d-flex justify-content-between align-items-start flex-wrap mb-3">
                                        <div>
                                            <h5 class="mb-1 fw-semibold">Team Target</h5>
                                            <small class="text-secondary">
                                                <i class="bi bi-calendar me-1"></i>
                                                <?= $target['start_date_formatted'] ?> - <?= $target['end_date_formatted'] ?>
                                            </small>
                                        </div>
                                        <div>
                                            <span class="status-badge status-<?= $target['display_status'] ?>">
                                                <?= strtoupper($target['display_status']) ?>
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <div class="row mb-3">
                                        <div class="col-md-4 mb-2">
                                            <small class="text-secondary">Target Amount</small>
                                            <h5 class="mb-0 text-primary">₹<?= number_format($target['target_amount'], 2) ?></h5>
                                        </div>
                                        <div class="col-md-4 mb-2">
                                            <small class="text-secondary">Achieved Amount</small>
                                            <h5 class="mb-0 text-success">₹<?= number_format($target['achieved_amount'] ?? 0, 2) ?></h5>
                                        </div>
                                        <div class="col-md-4 mb-2">
                                            <small class="text-secondary">Achievement</small>
                                            <h5 class="mb-0"><?= round($target['achievement_percentage'] ?? 0) ?>%</h5>
                                        </div>
                                    </div>
                                    
                                    <div class="progress mb-3">
                                        <div class="progress-bar" style="width: <?= $target['achievement_percentage'] ?? 0 ?>%"></div>
                                    </div>
                                    
                                    <?php if (!empty($target['notes'])): ?>
                                        <div class="alert alert-light p-2 mb-0 small">
                                            <i class="bi bi-chat"></i> <?= htmlspecialchars($target['notes']) ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="mt-2 text-end">
                                        <small class="text-secondary">
                                            <i class="bi bi-person"></i> Created by: <?= htmlspecialchars($target['created_by_name'] ?? 'System') ?>
                                            <i class="bi bi-clock ms-2"></i> <?= date('d M Y H:i', strtotime($target['created_at'])) ?>
                                        </small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="bi bi-archive"></i>
                                <h5>No Team Targets Found</h5>
                                <p class="text-secondary">No team targets have been created yet.</p>
                                <a href="<?= MAIN_URL ?>target-settings.php" class="btn btn-primary-custom mt-3">
                                    <i class="bi bi-plus-circle me-2"></i>Create Team Target
                                </a>
                            </div>
                        <?php endif; ?>
                        
                    <?php elseif (!empty($selected_user_uid)): ?>
                        <!-- Individual Seller History View -->
                        
                        <a href="<?= MAIN_URL ?>target-history.php" class="back-link">
                            <i class="bi bi-arrow-left-circle"></i> Back to All Sellers
                        </a>
                        
                        <div class="d-flex align-items-center gap-3 mb-4">
                            <div class="seller-avatar" style="width: 60px; height: 60px;">
                                <?= strtoupper(substr($user_name, 0, 2)) ?>
                            </div>
                            <div>
                                <h2 class="mb-1 fw-bold" style="color: var(--dark);"><?= htmlspecialchars($user_name) ?></h2>
                                <p class="text-secondary mb-0">User ID: <?= htmlspecialchars($selected_user_uid) ?></p>
                            </div>
                        </div>
                        
                        <?php if (!empty($sellerHistory)): ?>
                            <div class="row">
                                <?php foreach ($sellerHistory as $target): ?>
                                    <div class="col-lg-6 mb-4">
                                        <div class="history-card">
                                            <div class="d-flex justify-content-between align-items-start flex-wrap mb-3">
                                                <div>
                                                    <h5 class="mb-1 fw-semibold">
                                                        Target Period: <?= $target['start_date_formatted'] ?> - <?= $target['end_date_formatted'] ?>
                                                    </h5>
                                                    <small class="text-secondary">
                                                        <i class="bi bi-calendar-week"></i> Created on: <?= date('d M Y', strtotime($target['created_at'])) ?>
                                                    </small>
                                                </div>
                                                <div>
                                                    <span class="status-badge status-<?= $target['display_status'] ?>">
                                                        <?= strtoupper($target['display_status']) ?>
                                                    </span>
                                                </div>
                                            </div>
                                            
                                            <div class="row mb-3">
                                                <div class="col-md-6 mb-2">
                                                    <small class="text-secondary">Target Amount</small>
                                                    <h5 class="mb-0 text-primary">₹<?= number_format($target['target_amount'], 2) ?></h5>
                                                </div>
                                                <div class="col-md-6 mb-2">
                                                    <small class="text-secondary">Achieved Amount</small>
                                                    <h5 class="mb-0 text-success">₹<?= number_format($target['achieved_amount'] ?? 0, 2) ?></h5>
                                                </div>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <div class="d-flex justify-content-between small mb-1">
                                                    <span>Progress</span>
                                                    <span class="fw-bold"><?= round($target['achievement_percentage'] ?? 0) ?>%</span>
                                                </div>
                                                <div class="progress">
                                                    <div class="progress-bar" style="width: <?= $target['achievement_percentage'] ?? 0 ?>%"></div>
                                                </div>
                                            </div>
                                            
                                            <?php if (!empty($target['notes'])): ?>
                                                <div class="alert alert-light p-2 mb-2 small">
                                                    <i class="bi bi-chat"></i> <?= htmlspecialchars($target['notes']) ?>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <div class="text-end">
                                                <small class="text-secondary">
                                                    <i class="bi bi-person"></i> Created by: <?= htmlspecialchars($target['created_by_name'] ?? 'System') ?>
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="bi bi-archive"></i>
                                <h5>No Target History Found</h5>
                                <p class="text-secondary">This seller has no target history yet.</p>
                                <a href="<?= MAIN_URL ?>target-settings.php" class="btn btn-primary-custom mt-3">
                                    <i class="bi bi-plus-circle me-2"></i>Create Target for <?= htmlspecialchars($user_name) ?>
                                </a>
                            </div>
                        <?php endif; ?>
                        
                    <?php endif; ?>

                </div> <!-- end scrollable content -->
            </div> <!-- end main col -->
        </div> <!-- end row -->
    </div> <!-- end container-fluid -->

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
        // View seller history
        function viewSellerHistory(userUid, userName) {
            window.location.href = '<?= MAIN_URL ?>target-history.php?user_uid=' + userUid + '&type=individual&name=' + encodeURIComponent(userName);
        }
        
        // View team history
        function viewTeamHistory() {
            window.location.href = '<?= MAIN_URL ?>target-history.php?type=team';
        }
        
        // Sidebar toggle function
        function toggleSidebar() {
            const sidebar = document.getElementById('mainSidebar');
            const body = document.body;
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
                        document.body.appendChild(overlay);
                        document.body.classList.add('sidebar-open');
                    }
                } else {
                    const overlay = document.getElementById('sidebarOverlay');
                    if (overlay) overlay.remove();
                    document.body.classList.remove('sidebar-open');
                }
            }
        }

        // Close sidebar on resize
        window.addEventListener('resize', function() {
            const sidebar = document.getElementById('mainSidebar');
            const overlay = document.getElementById('sidebarOverlay');
            if (window.innerWidth > 768) {
                if (sidebar.classList.contains('active')) {
                    sidebar.classList.remove('active');
                }
                if (overlay) overlay.remove();
                document.body.classList.remove('sidebar-open');
            }
        });

        // Escape key handler
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