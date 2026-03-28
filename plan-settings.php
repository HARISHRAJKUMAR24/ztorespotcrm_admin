<?php
require_once './config/config.php';
require_once './lib/functions.php';
require_once './config/ztorespot_config.php';

// CHECK IF USER IS LOGGED IN
if (!isLoggedIn()) {
    header("Location: " . MAIN_URL . "auth/login.php");
    exit;
}

// Get current admin info
$currentAdmin = getCurrentAdmin();

// Fetch subscription plans from ztorespot database
function getSubscriptionPlans() {
    global $ztorespot_db;
    
    try {
        // Get only active plans, ordered by amount
        $stmt = $ztorespot_db->query("SELECT * FROM subscription_plans WHERE status = 1 ORDER BY CAST(amount AS UNSIGNED) ASC");
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

$plans = getSubscriptionPlans();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php template('head-tag'); ?>
    <style>
        :root {
            --primary: #4f46e5;
            --secondary: #9333ea;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --dark: #1e293b;
            --light: #f8fafc;
        }

        body {
            background: #f1f5f9;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        }

        /* Custom Card Styles */
        .plan-card {
            background: white;
            border-radius: 24px;
            padding: 28px;
            height: 100%;
            transition: all 0.3s ease;
            border: 1px solid rgba(0,0,0,0.05);
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,0.02);
        }

        .plan-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(79, 70, 229, 0.15);
            border-color: var(--primary);
        }

        .plan-card.popular {
            border: 2px solid var(--primary);
            background: linear-gradient(135deg, #fff, #faf5ff);
        }

        .popular-badge {
            position: absolute;
            top: 20px;
            right: -30px;
            background: var(--primary);
            color: white;
            padding: 8px 40px;
            transform: rotate(45deg);
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 1px;
            box-shadow: 0 2px 10px rgba(79, 70, 229, 0.3);
        }

        .plan-name {
            font-size: 24px;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .plan-name i {
            color: var(--primary);
            font-size: 28px;
        }

        .price-section {
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 2px dashed #e2e8f0;
        }

        .current-price {
            font-size: 42px;
            font-weight: 800;
            color: var(--dark);
            line-height: 1;
        }

        .current-price small {
            font-size: 16px;
            font-weight: 500;
            color: #64748b;
        }

        .old-price {
            font-size: 18px;
            color: #94a3b8;
            text-decoration: line-through;
            margin-left: 10px;
        }

        .duration {
            display: inline-block;
            background: #eef2ff;
            color: var(--primary);
            padding: 6px 16px;
            border-radius: 30px;
            font-size: 14px;
            font-weight: 600;
            margin-top: 10px;
        }

        .feature-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 0;
            color: #475569;
            border-bottom: 1px solid #f1f5f9;
        }

        .feature-item:last-child {
            border-bottom: none;
        }

        .feature-item i {
            color: var(--success);
            font-size: 18px;
            min-width: 24px;
        }

        .feature-item .feature-label {
            font-size: 15px;
        }

        .feature-item .feature-value {
            font-weight: 700;
            color: var(--dark);
            margin-left: auto;
            background: #f1f5f9;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 13px;
        }

        .plan-footer {
            margin-top: 25px;
            text-align: center;
        }

        .select-btn {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            border: none;
            padding: 14px 30px;
            border-radius: 50px;
            font-weight: 600;
            width: 100%;
            transition: all 0.3s;
            cursor: pointer;
            font-size: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .select-btn:hover {
            transform: scale(1.02);
            box-shadow: 0 10px 25px rgba(79, 70, 229, 0.3);
        }

        .stats-card {
            background: white;
            border-radius: 20px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.03);
            border: 1px solid #e2e8f0;
        }

        .page-header {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 40px;
            border-radius: 30px;
            margin-bottom: 30px;
            box-shadow: 0 15px 35px rgba(79, 70, 229, 0.2);
        }

        .page-header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .page-header p {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        .plan-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 25px;
            margin-top: 20px;
        }

        .badge-new {
            background: var(--success);
            color: white;
            padding: 4px 12px;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 600;
            margin-left: 10px;
        }

        .empty-state {
            text-align: center;
            padding: 80px;
            background: white;
            border-radius: 30px;
            color: #94a3b8;
        }

        .empty-state i {
            font-size: 60px;
            margin-bottom: 20px;
        }

        @media (max-width: 768px) {
            .plan-grid {
                grid-template-columns: 1fr;
            }
            
            .page-header {
                padding: 30px 20px;
            }
            
            .page-header h1 {
                font-size: 2rem;
            }
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
                        <h5 class="mb-0 d-none d-md-block">Subscription Plans</h5>
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
                <div class="scrollable-content">

                    <!-- Page Header -->
                    <div class="page-header">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <i class="bi bi-box-seam fs-1"></i>
                            <h1 class="mb-0">Subscription Plans</h1>
                        </div>
                        <p class="mb-0">Choose the perfect plan for your business needs</p>
                    </div>

                    <!-- Stats Summary -->
                    <div class="stats-card">
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <h5 class="mb-0">
                                    <i class="bi bi-grid-3x3-gap-fill me-2" style="color:var(--primary);"></i>
                                    Available Plans: <strong><?= count($plans) ?></strong>
                                </h5>
                            </div>
                            
                        </div>
                    </div>

                    <!-- Plans Grid -->
                    <?php if (!empty($plans)): ?>
                        <div class="plan-grid">
                            <?php foreach ($plans as $index => $plan): ?>
                                <div class="plan-card <?= ($index == 1) ? 'popular' : '' ?>">
                                    <?php if ($index == 1): ?>
                                        <div class="popular-badge">MOST POPULAR</div>
                                    <?php endif; ?>
                                    
                                    <!-- Plan Name -->
                                    <div class="plan-name">
                                        <i class="bi bi-crown"></i>
                                        <?= htmlspecialchars($plan['name']) ?>
                                        <?php if ($plan['new'] == 1): ?>
                                            <span class="badge-new">NEW</span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- Price Section -->
                                    <div class="price-section">
                                        <div>
                                            <span class="current-price">
                                                ₹<?= htmlspecialchars($plan['amount']) ?>
                                                <small>/<?= htmlspecialchars($plan['duration']) ?> months</small>
                                            </span>
                                            <?php if (!empty($plan['previous_amount'])): ?>
                                                <span class="old-price">₹<?= htmlspecialchars($plan['previous_amount']) ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <span class="duration">
                                            <i class="bi bi-calendar-check me-1"></i>
                                            <?= htmlspecialchars($plan['duration']) ?> Months Plan
                                        </span>
                                    </div>
                                    
                                    <!-- Features -->
                                    <div class="features-list">
                                        <div class="feature-item">
                                            <i class="bi bi-box"></i>
                                            <span class="feature-label">Products</span>
                                            <span class="feature-value"><?= htmlspecialchars($plan['product_limit']) ?></span>
                                        </div>
                                        
                                        <div class="feature-item">
                                            <i class="bi bi-tags"></i>
                                            <span class="feature-label">Categories</span>
                                            <span class="feature-value"><?= htmlspecialchars($plan['category_limit']) ?></span>
                                        </div>
                                        
                                        <div class="feature-item">
                                            <i class="bi bi-people"></i>
                                            <span class="feature-label">Staff Accounts</span>
                                            <span class="feature-value"><?= htmlspecialchars($plan['staff_limit']) ?></span>
                                        </div>
                                        
                                        <div class="feature-item">
                                            <i class="bi bi-file-text"></i>
                                            <span class="feature-label">Blog Posts</span>
                                            <span class="feature-value"><?= htmlspecialchars($plan['blog_limit']) ?></span>
                                        </div>
                                        
                                        <div class="feature-item">
                                            <i class="bi bi-files"></i>
                                            <span class="feature-label">Pages</span>
                                            <span class="feature-value"><?= htmlspecialchars($plan['page_limit']) ?></span>
                                        </div>
                                        
                                        <div class="feature-item">
                                            <i class="bi bi-percent"></i>
                                            <span class="feature-label">Discounts</span>
                                            <span class="feature-value"><?= htmlspecialchars($plan['discount_limit']) ?></span>
                                        </div>
                                        
                                        <?php if (!empty($plan['free_credits']) && $plan['free_credits'] > 0): ?>
                                        <div class="feature-item">
                                            <i class="bi bi-gift"></i>
                                            <span class="feature-label">Free Credits</span>
                                            <span class="feature-value">₹<?= htmlspecialchars($plan['free_credits']) ?></span>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <?php if ($plan['inventory'] == 1): ?>
                                        <div class="feature-item">
                                            <i class="bi bi-boxes"></i>
                                            <span class="feature-label">Inventory Management</span>
                                            <span class="feature-value"><i class="bi bi-check-lg" style="color:var(--success);"></i></span>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <?php if ($plan['bulk_product_upload'] == 1): ?>
                                        <div class="feature-item">
                                            <i class="bi bi-cloud-upload"></i>
                                            <span class="feature-label">Bulk Upload</span>
                                            <span class="feature-value"><i class="bi bi-check-lg" style="color:var(--success);"></i></span>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <?php if ($plan['custom_domain'] == 1): ?>
                                        <div class="feature-item">
                                            <i class="bi bi-globe"></i>
                                            <span class="feature-label">Custom Domain</span>
                                            <span class="feature-value"><i class="bi bi-check-lg" style="color:var(--success);"></i></span>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="bi bi-emoji-frown"></i>
                            <h3>No Plans Available</h3>
                            <p class="text-secondary">No active subscription plans found in the database.</p>
                        </div>
                    <?php endif; ?>

                </div> <!-- end scrollable content -->
            </div> <!-- end main col -->
        </div> <!-- end row -->
    </div> <!-- end container-fluid -->

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
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

        // Plan selection handler
        function selectPlan(planId) {
            Swal.fire({
                icon: 'info',
                title: 'Select Plan',
                text: 'This will redirect to plan checkout page.',
                confirmButtonColor: '#4f46e5',
                confirmButtonText: 'Proceed',
                showCancelButton: true,
                cancelButtonColor: '#64748b'
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Plan Selected!',
                        text: 'Redirecting to checkout...',
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.href = '<?= MAIN_URL ?>checkout.php?plan=' + planId;
                    });
                }
            });
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