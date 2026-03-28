<?php
require_once './config/config.php';
require_once './lib/functions.php';

// CHECK IF USER IS LOGGED IN
if (!isLoggedIn()) {
    header("Location: " . MAIN_URL . "auth/login.php");
    exit;
}

// Get current admin info
$currentAdmin = getCurrentAdmin();

// Fetch additional profile data from database
$stmt = $conn->prepare("SELECT id, full_name, username, email, profile_image, phone, address, bio, created_at FROM admin_users WHERE id = ?");
$stmt->bind_param("i", $currentAdmin['id']);
$stmt->execute();
$result = $stmt->get_result();
$profile = $result->fetch_assoc();
$stmt->close();

// Set default values for missing data
if ($profile) {
    // Ensure all expected keys exist with default values
    $profile = [
        'id' => $profile['id'] ?? 0,
        'full_name' => !empty($profile['full_name']) ? $profile['full_name'] : $profile['username'],
        'username' => $profile['username'] ?? '',
        'email' => $profile['email'] ?? '',
        'profile_image' => $profile['profile_image'] ?? null,
        'phone' => $profile['phone'] ?? '',
        'address' => $profile['address'] ?? '',
        'bio' => $profile['bio'] ?? '',
        'created_at' => $profile['created_at'] ?? date('Y-m-d H:i:s')
    ];
}

// Define MAIN_URL if not defined in config
if (!defined('MAIN_URL')) {
    define('MAIN_URL', 'http://localhost/ztorespotcrm_admin/');
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php template('head-tag'); ?>
    <style>
        :root {
            --primary-color: #4f46e5;
            --secondary-color: #9333ea;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
        }

        body {
            background: #f3f4f6;
        }

        .settings-container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .settings-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 40px;
            border-radius: 24px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(79, 70, 229, 0.2);
        }

        .settings-header h1 {
            font-size: 2.5rem;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .settings-header p {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        .settings-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            padding: 30px;
            height: 100%;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .settings-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(79, 70, 229, 0.15);
        }

        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid var(--primary-color);
            padding: 3px;
            background: white;
        }

        .info-item {
            padding: 15px;
            border-radius: 12px;
            background: #f8fafc;
            margin-bottom: 10px;
            transition: background 0.3s ease;
        }

        .info-item:hover {
            background: #f1f5f9;
        }

        .info-label {
            font-size: 0.85rem;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .info-value {
            font-size: 1.1rem;
            color: #1e293b;
            font-weight: 500;
            word-break: break-word;
        }

        .edit-btn {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 12px;
            font-weight: 500;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(79, 70, 229, 0.3);
        }

        .edit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(79, 70, 229, 0.4);
            color: white;
        }

        .cancel-btn {
            background: #f1f5f9;
            color: #64748b;
            border: 1px solid #e2e8f0;
            padding: 12px 30px;
            border-radius: 12px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .cancel-btn:hover {
            background: #e2e8f0;
            color: #475569;
        }

        .save-btn {
            background: var(--success-color);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 12px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .save-btn:hover {
            background: #0ca678;
            transform: translateY(-2px);
        }

        .form-control,
        .form-select {
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 12px 15px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }

        .form-label {
            font-weight: 500;
            color: #475569;
            margin-bottom: 8px;
        }

        .badge-profile {
            background: rgba(79, 70, 229, 0.1);
            color: var(--primary-color);
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .section-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e2e8f0;
        }

        .image-upload-container {
            position: relative;
            display: inline-block;
        }

        .image-upload-input {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            cursor: pointer;
        }

        .profile-preview {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid var(--primary-color);
            padding: 3px;
            background: white;
            transition: all 0.3s ease;
        }

        .profile-preview:hover {
            opacity: 0.8;
            transform: scale(1.05);
        }

        @media (max-width: 768px) {
            .settings-header {
                padding: 30px 20px;
            }

            .settings-header h1 {
                font-size: 2rem;
            }

            .settings-card {
                padding: 20px;
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
                        <i class="bi bi-gear-fill d-none d-md-inline-block fs-4" style="color:#4f46e5;"></i>
                        <h5 class="mb-0 d-none d-md-block">Settings</h5>
                    </div>
                    <div class="user-profile">
                        <span class="d-none d-sm-inline">
                            <?php echo htmlspecialchars($profile['full_name'] ?? $profile['username']); ?>
                        </span>
                        <img src="<?php echo !empty($profile['profile_image']) ? htmlspecialchars(MAIN_URL . $profile['profile_image']) : 'https://i.pravatar.cc/120?u=' . $profile['id']; ?>"
                            alt="avatar" class="avatar" id="headerAvatar">
                    </div>
                </div>

                <!-- SCROLLABLE CONTENT -->
                <div class="scrollable-content">

                    <!-- Settings Header -->
                    <div class="settings-header">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <i class="bi bi-gear-wide-connected fs-1"></i>
                            <h1 class="mb-0">Account Settings</h1>
                        </div>
                        <p class="mb-0">Manage your profile information and account preferences</p>
                    </div>

                    <div class="settings-container">
                        <!-- Profile Overview Card -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="settings-card">
                                    <div class="row align-items-center">
                                        <div class="col-md-auto text-center text-md-start mb-3 mb-md-0">
                                            <img src="<?php echo !empty($profile['profile_image']) ? htmlspecialchars(MAIN_URL . $profile['profile_image']) : 'https://i.pravatar.cc/150?u=' . $profile['id']; ?>"
                                                alt="Profile" class="profile-avatar" id="profileAvatar">
                                        </div>
                                        <div class="col-md">
                                            <h3 class="mb-2"><?php echo htmlspecialchars($profile['full_name'] ?? $profile['username']); ?></h3>
                                            <p class="text-secondary mb-2">
                                                <i class="bi bi-envelope me-2"></i><?php echo htmlspecialchars($profile['email']); ?>
                                            </p>
                                            <p class="text-secondary mb-0">
                                                <i class="bi bi-calendar me-2"></i>Member since <?php echo date('F Y', strtotime($profile['created_at'])); ?>
                                            </p>
                                        </div>
                                        <div class="col-md-auto mt-3 mt-md-0">
                                            <span class="badge-profile">
                                                <i class="bi bi-shield-check me-1"></i>Verified Account
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Main Settings Content -->
                        <div class="row">
                            <!-- Profile Information -->
                            <div class="col-lg-6 mb-4">
                                <div class="settings-card">
                                    <h5 class="section-title">
                                        <i class="bi bi-person-badge me-2" style="color: var(--primary-color);"></i>
                                        Profile Information
                                    </h5>

                                    <div id="profileView">
                                        <div class="info-item">
                                            <div class="info-label">Full Name</div>
                                            <div class="info-value"><?php echo htmlspecialchars($profile['full_name'] ?? 'Not set'); ?></div>
                                        </div>
                                        <div class="info-item">
                                            <div class="info-label">Username</div>
                                            <div class="info-value"><?php echo htmlspecialchars($profile['username']); ?></div>
                                        </div>
                                        <div class="info-item">
                                            <div class="info-label">Email Address</div>
                                            <div class="info-value"><?php echo htmlspecialchars($profile['email']); ?></div>
                                        </div>
                                        <div class="info-item">
                                            <div class="info-label">Phone Number</div>
                                            <div class="info-value"><?php echo htmlspecialchars($profile['phone'] ?? 'Not set'); ?></div>
                                        </div>
                                        <div class="info-item">
                                            <div class="info-label">Address</div>
                                            <div class="info-value"><?php echo htmlspecialchars($profile['address'] ?? 'Not set'); ?></div>
                                        </div>
                                        <div class="info-item">
                                            <div class="info-label">Bio</div>
                                            <div class="info-value"><?php echo htmlspecialchars($profile['bio'] ?? 'No bio added yet'); ?></div>
                                        </div>

                                        <button class="edit-btn w-100 mt-3" onclick="toggleEdit('profile')">
                                            <i class="bi bi-pencil-square me-2"></i>Edit Profile
                                        </button>
                                    </div>

                                    <div id="profileEdit" style="display: none;">
                                        <form id="profileForm">
                                            <div class="mb-3">
                                                <label class="form-label">Full Name</label>
                                                <input type="text" class="form-control" name="full_name"
                                                    value="<?php echo htmlspecialchars($profile['full_name'] ?? ''); ?>">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Username</label>
                                                <input type="text" class="form-control" name="username"
                                                    value="<?php echo htmlspecialchars($profile['username']); ?>" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Email</label>
                                                <input type="email" class="form-control" name="email"
                                                    value="<?php echo htmlspecialchars($profile['email']); ?>" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Phone Number</label>
                                                <input type="tel" class="form-control" name="phone"
                                                    value="<?php echo htmlspecialchars($profile['phone'] ?? ''); ?>">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Address</label>
                                                <textarea class="form-control" name="address" rows="2"><?php echo htmlspecialchars($profile['address'] ?? ''); ?></textarea>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Bio</label>
                                                <textarea class="form-control" name="bio" rows="3"><?php echo htmlspecialchars($profile['bio'] ?? ''); ?></textarea>
                                            </div>
                                            <div class="d-flex gap-2">
                                                <button type="submit" class="save-btn">
                                                    <i class="bi bi-check-lg me-2"></i>Save Changes
                                                </button>
                                                <button type="button" class="cancel-btn" onclick="toggleEdit('profile')">
                                                    <i class="bi bi-x-lg me-2"></i>Cancel
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <!-- Profile Image Settings -->
                            <div class="col-lg-6 mb-4">
                                <div class="settings-card">
                                    <h5 class="section-title">
                                        <i class="bi bi-camera me-2" style="color: var(--primary-color);"></i>
                                        Profile Picture
                                    </h5>

                                    <div class="text-center mb-4">
                                        <div class="image-upload-container">
                                            <img src="<?php echo !empty($profile['profile_image']) ? htmlspecialchars(MAIN_URL . $profile['profile_image']) : 'https://i.pravatar.cc/150?u=' . $profile['id']; ?>"
                                                alt="Profile Preview" class="profile-preview" id="imagePreview">
                                            <input type="file" class="image-upload-input" id="profileImage" accept="image/*">
                                        </div>
                                        <p class="text-secondary mt-2 small">Click on image to change</p>
                                    </div>


                                    <div class="info-item">
                                        <div class="info-label">Image Size</div>
                                        <div class="info-value">Max 2MB (JPG, PNG, GIF)</div>
                                    </div>

                                    <button class="edit-btn w-100 mt-3" onclick="document.getElementById('profileImage').click();">
                                        <i class="bi bi-cloud-upload me-2"></i>Upload New Picture
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script src="<?= MAIN_URL ?>js/settings.js"></script>
</body>

</html>