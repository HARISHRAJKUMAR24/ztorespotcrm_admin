<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../config/config.php';

$userId = intval($_GET['user_id'] ?? 0);

if (!$userId) {
    die("Invalid user ID");
}

// Get user
$userResult = $conn->query("SELECT name, user_uid FROM users WHERE id = $userId");

if (!$userResult) {
    die("User query error: " . $conn->error);
}

$user = $userResult->fetch_assoc();

// Get last updated customer from sales_person_sellers
$customerResult = $conn->query("
    SELECT * FROM sales_person_sellers 
    WHERE user_uid = '{$user['user_uid']}'
    ORDER BY updated_at DESC 
    LIMIT 10
");

if (!$customerResult) {
    die("Customer query error: " . $conn->error);
}

$customers = [];
while ($row = $customerResult->fetch_assoc()) {
    $customers[] = $row;
}?>

<style>
    /* Professional clean styling - no colors */
    .updates-container {
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        line-height: 1.5;
        color: #1a1a1a;
    }
    
    .updates-header {
        margin-bottom: 1.5rem;
        padding-bottom: 1rem;
        border-bottom: 2px solid #e5e7eb;
    }
    
    .updates-title {
        font-size: 1.25rem;
        font-weight: 600;
        margin: 0;
        color: #111827;
    }
    
    .updates-subtitle {
        font-size: 0.875rem;
        color: #6b7280;
        margin-top: 0.25rem;
    }
    
    /* Table styling */
    .updates-table-wrapper {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        margin: 0 -1rem;
        padding: 0 1rem;
    }
    
    .updates-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.875rem;
        min-width: 640px;
    }
    
    .updates-table th {
        text-align: left;
        padding: 0.875rem 1rem;
        background-color: #f9fafb;
        font-weight: 600;
        border-bottom: 2px solid #e5e7eb;
        color: #374151;
    }
    
    .updates-table td {
        padding: 0.875rem 1rem;
        border-bottom: 1px solid #f0f0f0;
        vertical-align: top;
    }
    
    .updates-table tr:hover {
        background-color: #fafafa;
    }
    
    /* Status badge - monochrome */
    .status-badge {
        display: inline-block;
        padding: 0.25rem 0.5rem;
        font-size: 0.75rem;
        font-weight: 500;
        line-height: 1;
        border: 1px solid #d1d5db;
        background-color: #ffffff;
        border-radius: 0.25rem;
    }
    
    /* Empty state */
    .empty-state {
        text-align: center;
        padding: 3rem 1rem;
        background-color: #fafafa;
        border: 1px solid #e5e7eb;
        border-radius: 0.5rem;
        margin: 1rem 0;
    }
    
    .empty-state-text {
        color: #6b7280;
        margin: 0;
        font-size: 0.875rem;
    }
    
    /* Mobile optimizations */
    @media (max-width: 768px) {
        .updates-table th,
        .updates-table td {
            padding: 0.75rem 0.75rem;
            font-size: 0.8125rem;
        }
        
        .updates-title {
            font-size: 1.125rem;
        }
        
        .status-badge {
            font-size: 0.6875rem;
            padding: 0.1875rem 0.375rem;
        }
    }
    
    /* Small screens */
    @media (max-width: 480px) {
        .updates-table th,
        .updates-table td {
            padding: 0.625rem 0.5rem;
            font-size: 0.75rem;
        }
        
        .updates-header {
            margin-bottom: 1rem;
        }
    }
    
    /* Print styles - manager friendly */
    @media print {
        .updates-table-wrapper {
            overflow: visible;
            margin: 0;
            padding: 0;
        }
        
        .updates-table {
            border: 1px solid #ddd;
        }
        
        .updates-table th {
            background-color: #f5f5f5 !important;
            print-color-adjust: exact;
        }
        
        .status-badge {
            border: 1px solid #000;
            print-color-adjust: exact;
        }
        
        .updates-table tr:hover {
            background-color: transparent;
        }
    }
</style>

<div class="updates-container">
    <div class="updates-header">
        <h5 class="updates-title"><?= htmlspecialchars($user['name']) ?> - Recent Customer Updates</h5>
        <div class="updates-subtitle">Last 10 updated customers</div>
    </div>

    <?php if (!empty($customers)): ?>
        <div class="updates-table-wrapper">
            <table class="updates-table">
                <thead>
                    <tr>
                        <th style="width: 5%">#</th>
                        <th style="width: 12%">Phone</th>
                        <th style="width: 30%">Work Update</th>
                        <th style="width: 10%">Status</th>
                        <th style="width: 28%">Response</th>
                        <th style="width: 15%">Updated Time</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($customers as $index => $c): ?>
                        <tr>
                            <td><?= $index + 1 ?></td>
                            <td>
                                <span style="font-family: monospace; font-size: 0.8125rem;">
                                    <?= htmlspecialchars($c['phone_number']) ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($c['work_details_update'] ?: '—') ?></td>
                            <td>
                                <span class="status-badge">
                                    <?= htmlspecialchars($c['current_status'] ?: '—') ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($c['customer_response'] ?: '—') ?></td>
                            <td style="white-space: nowrap;">
                                <?= date('d M Y, H:i', strtotime($c['updated_at'])) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Optional: Summary for managers -->
        <div style="margin-top: 1rem; padding-top: 0.75rem; border-top: 1px solid #e5e7eb; font-size: 0.75rem; color: #6b7280; text-align: right;">
            Showing <?= count($customers) ?> of <?= count($customers) ?> records
        </div>
        
    <?php else: ?>
        <div class="empty-state">
            <p class="empty-state-text">No recent updates found for this sales person</p>
        </div>
    <?php endif; ?>
</div>