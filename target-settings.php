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



$users = getUsers();
$individualTargets = getAllActiveTargets('individual');
$teamTargets = getTeamTargets();
$stats = getTargetStats();
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

        .stat-card {
            background: white;
            border-radius: 20px;
            padding: 20px;
            margin-bottom: 25px;
            border: 1px solid #e2e8f0;
            transition: all 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }

        .stat-value {
            font-size: 28px;
            font-weight: 700;
            color: var(--dark);
        }

        .stat-label {
            color: #64748b;
            font-size: 14px;
        }

        .target-card {
            background: white;
            border-radius: 20px;
            padding: 20px;
            margin-bottom: 20px;
            border: 1px solid #e2e8f0;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
            cursor: pointer;
        }

        .target-card:hover {
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
            border-color: var(--primary);
        }

        .target-badge {
            position: absolute;
            top: 20px;
            right: 20px;
            padding: 5px 15px;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge-individual {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
        }

        .badge-team {
            background: linear-gradient(135deg, var(--info), #06b6d4);
            color: white;
        }

        .progress {
            height: 8px;
            border-radius: 10px;
            background: #e2e8f0;
        }

        .progress-bar {
            background: linear-gradient(90deg, var(--primary), var(--secondary));
            border-radius: 10px;
            transition: width 0.5s ease;
        }

        .achievement-percent {
            font-size: 24px;
            font-weight: 700;
            color: var(--primary);
        }

        .user-avatar {
            width: 45px;
            height: 45px;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 18px;
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

        .btn-outline-custom {
            border: 2px solid var(--primary);
            background: transparent;
            color: var(--primary);
            border-radius: 12px;
            transition: all 0.3s;
        }

        .btn-outline-custom:hover {
            background: var(--primary);
            color: white;
        }

        .modal-content {
            border-radius: 20px;
            border: none;
        }

        .modal-header {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            border-radius: 20px 20px 0 0;
        }

        .form-control,
        .form-select {
            border-radius: 12px;
            border: 2px solid #e2e8f0;
            padding: 10px 15px;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
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

        .target-card {
            animation: slideIn 0.3s ease;
        }

        .click-hint {
            position: absolute;
            bottom: 10px;
            right: 15px;
            font-size: 11px;
            color: #94a3b8;
            opacity: 0;
            transition: opacity 0.3s;
        }

        .target-card:hover .click-hint {
            opacity: 1;
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
                        <i class="bi bi-bullseye d-none d-md-inline-block fs-4" style="color:var(--primary);"></i>
                        <h5 class="mb-0 d-none d-md-block">Target Settings</h5>
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

                    <!-- Page Header -->
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h2 class="mb-1 fw-bold" style="color: var(--dark);">Target Settings</h2>
                            <p class="text-secondary mb-0">Manage sales targets for individuals and teams</p>
                        </div>
                        <button class="btn btn-primary-custom" onclick="openAddTargetModal()">
                            <i class="bi bi-plus-circle me-2"></i>Add New Target
                        </button>
                    </div>

                    <!-- Statistics Cards -->
                    <div class="row mb-4">
                        <div class="col-md-6 mb-3">
                            <div class="stat-card">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <div class="stat-icon bg-primary bg-opacity-10 mb-3">
                                            <i class="bi bi-person" style="color: var(--primary);"></i>
                                        </div>
                                        <div class="stat-value"><?= $stats['individual']['total_targets'] ?? 0 ?></div>
                                        <div class="stat-label">Individual Targets</div>
                                    </div>
                                    <div class="text-end">
                                        <div class="small text-success">
                                            <i class="bi bi-check-circle-fill"></i>
                                            <?= $stats['individual']['achieved_targets'] ?? 0 ?> Achieved
                                        </div>
                                        <div class="small text-primary mt-1">
                                            <i class="bi bi-graph-up"></i>
                                            <?= round($stats['individual']['avg_achievement'] ?? 0) ?>% Avg
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="stat-card">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <div class="stat-icon bg-info bg-opacity-10 mb-3">
                                            <i class="bi bi-people" style="color: var(--info);"></i>
                                        </div>
                                        <div class="stat-value"><?= $stats['team']['total_targets'] ?? 0 ?></div>
                                        <div class="stat-label">Team Targets</div>
                                    </div>
                                    <div class="text-end">
                                        <div class="small text-success">
                                            <i class="bi bi-check-circle-fill"></i>
                                            <?= $stats['team']['achieved_targets'] ?? 0 ?> Achieved
                                        </div>
                                        <div class="small text-primary mt-1">
                                            <i class="bi bi-graph-up"></i>
                                            <?= round($stats['team']['avg_achievement'] ?? 0) ?>% Avg
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Individual Targets Section -->
                    <div class="mb-5">
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <i class="bi bi-person-badge fs-4" style="color: var(--primary);"></i>
                            <h4 class="mb-0 fw-semibold">Individual Sales Targets</h4>
                            <span class="badge bg-primary bg-opacity-10 text-primary ms-2">
                                <?= count($individualTargets) ?> Active
                            </span>
                        </div>

                        <div class="row">
                            <?php if (!empty($individualTargets)): ?>
                                <?php foreach ($individualTargets as $target): ?>
                                    <div class="col-lg-6 col-xl-4 mb-4">
                                        <div class="target-card" onclick="viewHistory('<?= $target['user_uid'] ?>', 'individual', '<?= addslashes($target['name']) ?>')">
                                            <div class="target-badge badge-individual">
                                                <i class="bi bi-person me-1"></i>Individual
                                            </div>

                                            <div class="d-flex align-items-center gap-3 mb-3">
                                                <div class="user-avatar">
                                                    <?= strtoupper(substr($target['name'], 0, 2)) ?>
                                                </div>
                                                <div>
                                                    <h6 class="mb-0 fw-semibold"><?= htmlspecialchars($target['name']) ?></h6>
                                                    <small class="text-secondary"><?= htmlspecialchars($target['user_uid']) ?></small>
                                                </div>
                                            </div>

                                            <div class="mb-3">
                                                <div class="d-flex justify-content-between mb-1">
                                                    <span class="small text-secondary">Target Amount</span>
                                                    <span class="fw-bold">₹<?= number_format($target['target_amount'], 2) ?></span>
                                                </div>
                                                <div class="d-flex justify-content-between mb-1">
                                                    <span class="small text-secondary">Achieved</span>
                                                    <span class="fw-bold text-success">₹<?= number_format($target['achieved_amount'] ?? 0, 2) ?></span>
                                                </div>
                                                <div class="progress mt-2">
                                                    <div class="progress-bar" style="width: <?= $target['achievement_percentage'] ?? 0 ?>%"></div>
                                                </div>
                                                <div class="text-center mt-2">
                                                    <span class="achievement-percent"><?= round($target['achievement_percentage'] ?? 0) ?>%</span>
                                                    <span class="text-secondary">Achieved</span>
                                                </div>
                                            </div>

                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <small class="text-secondary">
                                                        <i class="bi bi-calendar-range me-1"></i>
                                                        <?= date('d M', strtotime($target['start_date'])) ?> - <?= date('d M Y', strtotime($target['end_date'])) ?>
                                                    </small>
                                                    <?php if (!empty($target['notes'])): ?>
                                                        <div class="small text-secondary mt-1">
                                                            <i class="bi bi-chat"></i> <?= htmlspecialchars(substr($target['notes'], 0, 30)) ?>...
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                <button class="btn btn-sm btn-outline-custom" onclick="event.stopPropagation(); updateAchievement(<?= $target['id'] ?>, '<?= addslashes($target['name']) ?>', <?= $target['target_amount'] ?>, <?= $target['achieved_amount'] ?? 0 ?>)">
                                                    <i class="bi bi-pencil"></i> Update
                                                </button>
                                            </div>
                                            <div class="click-hint">
                                                <i class="bi bi-eye"></i> Click to view history
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="col-12">
                                    <div class="empty-state">
                                        <i class="bi bi-person-x"></i>
                                        <h5>No Active Individual Targets</h5>
                                        <p class="text-secondary">Click "Add New Target" to set individual sales targets</p>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Team Targets Section -->
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <i class="bi bi-people fs-4" style="color: var(--info);"></i>
                            <h4 class="mb-0 fw-semibold">Team Targets</h4>
                            <span class="badge bg-info bg-opacity-10 text-info ms-2">
                                <?= count($teamTargets) ?> Active
                            </span>
                        </div>

                        <div class="row">
                            <?php if (!empty($teamTargets)): ?>
                                <?php foreach ($teamTargets as $target): ?>
                                    <div class="col-lg-6 col-xl-4 mb-4">
                                        <div class="target-card" onclick="viewHistory('TEAM', 'team', 'Team Target')">
                                            <div class="target-badge badge-team">
                                                <i class="bi bi-people me-1"></i>Team Target
                                            </div>

                                            <div class="d-flex align-items-center gap-3 mb-3">
                                                <div class="user-avatar" style="background: linear-gradient(135deg, var(--info), #06b6d4);">
                                                    <i class="bi bi-people-fill"></i>
                                                </div>
                                                <div>
                                                    <h6 class="mb-0 fw-semibold">Team Target</h6>
                                                    <small class="text-secondary">
                                                        <i class="bi bi-calendar me-1"></i>
                                                        <?= date('d M Y', strtotime($target['created_at'])) ?>
                                                    </small>
                                                </div>
                                            </div>

                                            <div class="mb-3">
                                                <div class="d-flex justify-content-between mb-1">
                                                    <span class="small text-secondary">Target Amount</span>
                                                    <span class="fw-bold">₹<?= number_format($target['target_amount'], 2) ?></span>
                                                </div>
                                                <div class="d-flex justify-content-between mb-1">
                                                    <span class="small text-secondary">Achieved</span>
                                                    <span class="fw-bold text-success">₹<?= number_format($target['achieved_amount'] ?? 0, 2) ?></span>
                                                </div>
                                                <div class="progress mt-2">
                                                    <div class="progress-bar" style="width: <?= $target['achievement_percentage'] ?? 0 ?>%"></div>
                                                </div>
                                                <div class="text-center mt-2">
                                                    <span class="achievement-percent"><?= round($target['achievement_percentage'] ?? 0) ?>%</span>
                                                    <span class="text-secondary">Achieved</span>
                                                </div>
                                            </div>

                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <small class="text-secondary">
                                                        <i class="bi bi-calendar-range me-1"></i>
                                                        <?= date('d M', strtotime($target['start_date'])) ?> - <?= date('d M Y', strtotime($target['end_date'])) ?>
                                                    </small>
                                                    <?php if (!empty($target['notes'])): ?>
                                                        <div class="small text-secondary mt-1">
                                                            <i class="bi bi-chat"></i> <?= htmlspecialchars(substr($target['notes'], 0, 30)) ?>...
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                <button class="btn btn-sm btn-outline-custom" onclick="event.stopPropagation(); updateAchievement(<?= $target['id'] ?>, 'Team Target', <?= $target['target_amount'] ?>, <?= $target['achieved_amount'] ?? 0 ?>)">
                                                    <i class="bi bi-pencil"></i> Update
                                                </button>
                                            </div>
                                            <div class="click-hint">
                                                <i class="bi bi-eye"></i> Click to view history
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="col-12">
                                    <div class="empty-state">
                                        <i class="bi bi-people-x"></i>
                                        <h5>No Active Team Targets</h5>
                                        <p class="text-secondary">Click "Add New Target" to set team sales targets</p>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <!-- Add/Edit Target Modal -->
    <div class="modal fade" id="targetModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-plus-circle me-2"></i>
                        <span id="modalTitle">Add New Target</span>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="targetForm">
                        <input type="hidden" id="target_id" name="target_id">

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Target Type *</label>
                            <select class="form-select" id="target_type" name="target_type" required onchange="toggleUserSelection()">
                                <option value="individual">Individual Sales Person</option>
                                <option value="team">Team Target</option>
                            </select>
                        </div>

                        <div class="mb-3" id="userSelectDiv">
                            <label class="form-label fw-semibold">Select Sales Person *</label>
                            <select class="form-select" id="user_uid" name="user_uid">
                                <option value="">Select a sales person</option>
                                <?php foreach ($users as $user): ?>
                                    <option value="<?= htmlspecialchars($user['user_uid']) ?>">
                                        <?= htmlspecialchars($user['name']) ?> (<?= htmlspecialchars($user['user_uid']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Start Date *</label>
                                <input type="date" class="form-control" id="start_date" name="start_date" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">End Date *</label>
                                <input type="date" class="form-control" id="end_date" name="end_date" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Target Amount (₹) *</label>
                            <input type="number" class="form-control" id="target_amount" name="target_amount"
                                step="0.01" min="0" placeholder="Enter target amount" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Notes</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3"
                                placeholder="Additional notes about this target..."></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary-custom" onclick="saveTarget()">
                        <i class="bi bi-save me-2"></i>Save Target
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Update Achievement Modal -->
    <div class="modal fade" id="achievementModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-pencil-square me-2"></i>
                        Update Achievement
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="achievementForm">
                        <input type="hidden" id="achievement_target_id" name="target_id">

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Sales Person / Team</label>
                            <input type="text" class="form-control" id="achievement_user_name" readonly>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Target Amount</label>
                            <input type="text" class="form-control" id="achievement_target_amount" readonly>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Achieved Amount (₹) *</label>
                            <input type="number" class="form-control" id="achieved_amount" name="achieved_amount"
                                step="0.01" min="0" placeholder="Enter achieved amount" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Notes</label>
                            <textarea class="form-control" id="achievement_notes" name="notes" rows="3"
                                placeholder="Additional notes about achievement..."></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary-custom" onclick="saveAchievement()">
                        <i class="bi bi-check-circle me-2"></i>Update Achievement
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- At the bottom of your target-settings.php, replace the script section with: -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="<?= MAIN_URL ?>js/target-settings.js"></script>

</body>

</html>