<?php
require_once './config/config.php';
require_once './lib/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header("Location: " . MAIN_URL . "auth/login.php");
    exit;
}

$currentAdmin = getCurrentAdmin();

// Get all plans
function getAllPlans() {
    global $conn;
    $sql = "SELECT * FROM subscription_plans ORDER BY amount ASC, duration ASC";
    $result = $conn->query($sql);
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Get plan by ID
function getPlanById($id) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM subscription_plans WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

$plans = getAllPlans();
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

        .plan-card {
            background: white;
            border-radius: 20px;
            padding: 20px;
            margin-bottom: 20px;
            border: 1px solid #e2e8f0;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }

        .plan-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            border-color: var(--primary);
        }

        .plan-header {
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 15px;
            margin-bottom: 15px;
        }

        .plan-name {
            font-size: 24px;
            font-weight: 700;
            color: var(--dark);
        }

        .duration-badge {
            background: #eef2ff;
            color: var(--primary);
            padding: 4px 12px;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 600;
        }

        .price-section {
            background: #f8fafc;
            padding: 15px;
            border-radius: 12px;
            margin: 15px 0;
        }

        .base-price {
            font-size: 28px;
            font-weight: 700;
            color: var(--dark);
        }

        .gst-amount {
            color: var(--info);
            font-weight: 500;
        }

        .total-price {
            font-size: 32px;
            font-weight: 800;
            color: var(--primary);
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
            box-shadow: 0 5px 15px rgba(79,70,229,0.3);
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

        .status-badge {
            padding: 4px 12px;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-active {
            background: #d1fae5;
            color: var(--success);
        }

        .status-inactive {
            background: #fee2e2;
            color: var(--danger);
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

        .form-control, .form-select {
            border-radius: 12px;
            border: 2px solid #e2e8f0;
            padding: 10px 15px;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(79,70,229,0.1);
        }

        .calculation-preview {
            background: #f8fafc;
            padding: 15px;
            border-radius: 12px;
            margin-top: 15px;
        }

        .calculation-preview h6 {
            color: var(--dark);
            margin-bottom: 10px;
        }

        .price-preview {
            font-size: 20px;
            font-weight: 700;
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
                        <i class="bi bi-tag-fill d-none d-md-inline-block fs-4" style="color:var(--primary);"></i>
                        <h5 class="mb-0 d-none d-md-block">Plan Settings</h5>
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
                            <h2 class="mb-1 fw-bold" style="color: var(--dark);">Subscription Plans</h2>
                            <p class="text-secondary mb-0">Manage subscription plans with GST calculation</p>
                        </div>
                        <button class="btn btn-primary-custom" onclick="openAddPlanModal()">
                            <i class="bi bi-plus-circle me-2"></i>Add New Plan
                        </button>
                    </div>

                    <!-- Plans Grid -->
                    <div class="row">
                        <?php if (!empty($plans)): ?>
                            <?php foreach ($plans as $plan): ?>
                                <div class="col-lg-6 col-xl-4 mb-4">
                                    <div class="plan-card">
                                        <div class="plan-header">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div>
                                                    <h3 class="plan-name mb-1"><?= htmlspecialchars($plan['plan_name']) ?></h3>
                                                    <span class="duration-badge">
                                                        <i class="bi bi-calendar-check me-1"></i>
                                                        <?= htmlspecialchars($plan['duration']) ?>
                                                    </span>
                                                </div>
                                                <span class="status-badge <?= $plan['status'] == 1 ? 'status-active' : 'status-inactive' ?>">
                                                    <?= $plan['status'] == 1 ? 'Active' : 'Inactive' ?>
                                                </span>
                                            </div>
                                        </div>

                                        <div class="price-section">
                                            <div class="d-flex justify-content-between mb-2">
                                                <span class="text-muted">Base Amount:</span>
                                                <strong>₹<?= number_format($plan['amount'], 2) ?></strong>
                                            </div>
                                            <div class="d-flex justify-content-between mb-2">
                                                <span class="text-muted">GST (<?= $plan['gst_percentage'] ?>%):</span>
                                                <strong class="gst-amount">+ ₹<?= number_format($plan['gst_amount'], 2) ?></strong>
                                            </div>
                                            <div class="d-flex justify-content-between pt-2 border-top mt-2">
                                                <span class="fw-bold">Total Amount:</span>
                                                <span class="total-price">₹<?= number_format($plan['total_amount'], 2) ?></span>
                                            </div>
                                        </div>

                                        <div class="d-flex gap-2">
                                            <button class="btn btn-outline-custom flex-grow-1" onclick="editPlan(<?= $plan['id'] ?>)">
                                                <i class="bi bi-pencil"></i> Edit
                                            </button>
                                            <button class="btn btn-outline-custom flex-grow-1" onclick="togglePlanStatus(<?= $plan['id'] ?>, <?= $plan['status'] ?>)">
                                                <i class="bi bi-<?= $plan['status'] == 1 ? 'toggle-on' : 'toggle-off' ?>"></i>
                                                <?= $plan['status'] == 1 ? 'Deactivate' : 'Activate' ?>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="col-12">
                                <div class="empty-state">
                                    <i class="bi bi-tag fs-1 text-muted"></i>
                                    <h5>No Plans Found</h5>
                                    <p class="text-secondary">Click "Add New Plan" to create subscription plans</p>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <!-- Add/Edit Plan Modal -->
    <div class="modal fade" id="planModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-plus-circle me-2"></i>
                        <span id="modalTitle">Add New Plan</span>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="planForm">
                        <input type="hidden" id="plan_id" name="plan_id">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Plan Name *</label>
                                <input type="text" class="form-control" id="plan_name" name="plan_name" 
                                       placeholder="e.g., Welcome, Starter, Professional" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Duration *</label>
                                <select class="form-select" id="duration" name="duration" required>
                                    <option value="">Select Duration</option>
                                    <option value="1 Month">1 Month</option>
                                    <option value="2 Months">2 Months</option>
                                    <option value="3 Months">3 Months</option>
                                    <option value="6 Months">6 Months</option>
                                    <option value="1 Year">1 Year</option>
                                    <option value="2 Years">2 Years</option>
                                    <option value="3 Years">3 Years</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Base Amount (₹) *</label>
                                <input type="number" class="form-control" id="amount" name="amount" 
                                       step="0.01" min="0" placeholder="Enter base amount" required oninput="calculateTotal()">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">GST Percentage (%) *</label>
                                <select class="form-select" id="gst_percentage" name="gst_percentage" required onchange="calculateTotal()">
                                    <option value="0">0% (No GST)</option>
                                    <option value="5">5% GST</option>
                                    <option value="12">12% GST</option>
                                    <option value="18" selected>18% GST</option>
                                    <option value="28">28% GST</option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Live Calculation Preview -->
                        <div class="calculation-preview">
                            <h6><i class="bi bi-calculator me-2"></i>Price Calculation Preview</h6>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Base Amount:</span>
                                <strong id="preview_base">₹0.00</strong>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">GST Amount (<span id="preview_gst_percent">18</span>%):</span>
                                <strong id="preview_gst" class="text-info">+ ₹0.00</strong>
                            </div>
                            <div class="d-flex justify-content-between pt-2 border-top mt-2">
                                <span class="fw-bold fs-5">Total Amount:</span>
                                <span class="price-preview text-primary" id="preview_total">₹0.00</span>
                            </div>
                        </div>
                        
                        <div class="mb-3 mt-3">
                            <label class="form-label fw-semibold">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary-custom" onclick="savePlan()">
                        <i class="bi bi-save me-2"></i>Save Plan
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>

        let planModal;

        document.addEventListener('DOMContentLoaded', function() {
            planModal = new bootstrap.Modal(document.getElementById('planModal'));
        });

        // Calculate total with GST
        function calculateTotal() {
            const amount = parseFloat(document.getElementById('amount').value) || 0;
            const gstPercent = parseFloat(document.getElementById('gst_percentage').value) || 0;
            
            const gstAmount = (amount * gstPercent) / 100;
            const totalAmount = amount + gstAmount;
            
            // Update preview
            document.getElementById('preview_base').innerHTML = '₹' + amount.toLocaleString('en-IN', {minimumFractionDigits: 2});
            document.getElementById('preview_gst_percent').innerHTML = gstPercent;
            document.getElementById('preview_gst').innerHTML = '+ ₹' + gstAmount.toLocaleString('en-IN', {minimumFractionDigits: 2});
            document.getElementById('preview_total').innerHTML = '₹' + totalAmount.toLocaleString('en-IN', {minimumFractionDigits: 2});
            
            // Store calculated values in hidden fields or data attributes
            document.getElementById('planForm').dataset.gstAmount = gstAmount;
            document.getElementById('planForm').dataset.totalAmount = totalAmount;
        }

        // Open Add Plan Modal
        function openAddPlanModal() {
            document.getElementById('modalTitle').innerText = 'Add New Plan';
            document.getElementById('planForm').reset();
            document.getElementById('plan_id').value = '';
            document.getElementById('status').value = '1';
            document.getElementById('gst_percentage').value = '18';
            calculateTotal();
            planModal.show();
        }

        // Edit Plan
        function editPlan(planId) {
            Swal.fire({
                title: 'Loading...',
                text: 'Fetching plan details',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            fetch(MAIN_URL + 'ajax/get-plan.php?id=' + planId)
                .then(response => response.json())
                .then(data => {
                    Swal.close();
                    if (data.success) {
                        document.getElementById('modalTitle').innerText = 'Edit Plan';
                        document.getElementById('plan_id').value = data.plan.id;
                        document.getElementById('plan_name').value = data.plan.plan_name;
                        document.getElementById('duration').value = data.plan.duration;
                        document.getElementById('amount').value = data.plan.amount;
                        document.getElementById('gst_percentage').value = data.plan.gst_percentage;
                        document.getElementById('status').value = data.plan.status;
                        calculateTotal();
                        planModal.show();
                    } else {
                        Swal.fire('Error', data.message, 'error');
                    }
                })
                .catch(error => {
                    Swal.close();
                    Swal.fire('Error', 'Failed to load plan details', 'error');
                });
        }

        // Save Plan
        function savePlan() {
            const form = document.getElementById('planForm');
            const formData = new FormData(form);
            
            // Get calculated values
            const amount = parseFloat(document.getElementById('amount').value) || 0;
            const gstPercent = parseFloat(document.getElementById('gst_percentage').value) || 0;
            const gstAmount = (amount * gstPercent) / 100;
            const totalAmount = amount + gstAmount;
            
            formData.append('gst_amount', gstAmount);
            formData.append('total_amount', totalAmount);
            
            // Validate
            if (!formData.get('plan_name')) {
                Swal.fire('Error', 'Please enter plan name', 'error');
                return;
            }
            if (!formData.get('duration')) {
                Swal.fire('Error', 'Please select duration', 'error');
                return;
            }
            if (!formData.get('amount') || parseFloat(formData.get('amount')) <= 0) {
                Swal.fire('Error', 'Please enter valid amount', 'error');
                return;
            }
            
            Swal.fire({
                title: 'Saving...',
                text: 'Please wait',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            fetch(MAIN_URL + 'ajax/save-plan.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                Swal.close();
                if (data.success) {
                    Swal.fire('Success', data.message, 'success').then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            })
            .catch(error => {
                Swal.close();
                Swal.fire('Error', 'An error occurred', 'error');
            });
        }

        // Toggle Plan Status
        function togglePlanStatus(planId, currentStatus) {
            const newStatus = currentStatus == 1 ? 0 : 1;
            const action = newStatus == 1 ? 'activate' : 'deactivate';
            
            Swal.fire({
                title: `Are you sure?`,
                text: `Do you want to ${action} this plan?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#4f46e5',
                cancelButtonColor: '#ef4444',
                confirmButtonText: `Yes, ${action} it!`
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Updating...',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                    
                    fetch(MAIN_URL + 'ajax/toggle-plan-status.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'plan_id=' + planId + '&status=' + newStatus
                    })
                    .then(response => response.json())
                    .then(data => {
                        Swal.close();
                        if (data.success) {
                            Swal.fire('Success', data.message, 'success').then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire('Error', data.message, 'error');
                        }
                    })
                    .catch(error => {
                        Swal.close();
                        Swal.fire('Error', 'An error occurred', 'error');
                    });
                }
            });
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
                if (sidebar.classList.contains('active')) {
                    sidebar.classList.remove('active');
                }
                if (overlay) overlay.remove();
                document.body.classList.remove('sidebar-open');
            }
        });

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