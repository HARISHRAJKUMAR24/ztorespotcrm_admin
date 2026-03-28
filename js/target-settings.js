// Target Settings JavaScript
let targetModal, achievementModal;

document.addEventListener('DOMContentLoaded', function() {
    // Initialize modals
    targetModal = new bootstrap.Modal(document.getElementById('targetModal'));
    achievementModal = new bootstrap.Modal(document.getElementById('achievementModal'));

    // Set default dates
    const today = new Date().toISOString().split('T')[0];
    const nextMonth = new Date();
    nextMonth.setMonth(nextMonth.getMonth() + 1);
    const nextMonthDate = nextMonth.toISOString().split('T')[0];

    const startDateInput = document.getElementById('start_date');
    const endDateInput = document.getElementById('end_date');
    
    if (startDateInput) startDateInput.value = today;
    if (endDateInput) endDateInput.value = nextMonthDate;

    // Initialize target type change handler
    const targetTypeSelect = document.getElementById('target_type');
    if (targetTypeSelect) {
        toggleUserSelection();
        targetTypeSelect.addEventListener('change', toggleUserSelection);
    }
});

// View history function
function viewHistory(userUid, type, userName) {
    if (typeof MAIN_URL !== 'undefined') {
        window.location.href = MAIN_URL + 'target-history.php?user_uid=' + userUid + '&type=' + type + '&name=' + encodeURIComponent(userName);
    } else {
        console.error('MAIN_URL is not defined');
        Swal.fire('Error', 'Configuration error', 'error');
    }
}

// Toggle user selection based on target type
function toggleUserSelection() {
    const targetType = document.getElementById('target_type').value;
    const userSelectDiv = document.getElementById('userSelectDiv');
    const userUidSelect = document.getElementById('user_uid');

    if (targetType === 'team') {
        userSelectDiv.style.display = 'none';
        if (userUidSelect) {
            userUidSelect.removeAttribute('required');
            userUidSelect.value = '';
        }
    } else {
        userSelectDiv.style.display = 'block';
        if (userUidSelect) {
            userUidSelect.setAttribute('required', 'required');
        }
    }
}

// Open add target modal
function openAddTargetModal() {
    document.getElementById('modalTitle').innerText = 'Add New Target';
    document.getElementById('targetForm').reset();
    document.getElementById('target_id').value = '';

    const today = new Date().toISOString().split('T')[0];
    const nextMonth = new Date();
    nextMonth.setMonth(nextMonth.getMonth() + 1);
    const nextMonthDate = nextMonth.toISOString().split('T')[0];

    document.getElementById('start_date').value = today;
    document.getElementById('end_date').value = nextMonthDate;
    document.getElementById('target_type').value = 'individual';

    toggleUserSelection();
    targetModal.show();
}

// Save target
function saveTarget() {
    const form = document.getElementById('targetForm');
    const formData = new FormData(form);
    const targetType = formData.get('target_type');

    // Validate form
    if (targetType === 'individual') {
        if (!formData.get('user_uid') || formData.get('user_uid') === '') {
            Swal.fire('Error', 'Please select a sales person', 'error');
            return;
        }
    }

    if (!formData.get('start_date') || !formData.get('end_date')) {
        Swal.fire('Error', 'Please select start and end dates', 'error');
        return;
    }

    if (!formData.get('target_amount') || parseFloat(formData.get('target_amount')) <= 0) {
        Swal.fire('Error', 'Please enter a valid target amount', 'error');
        return;
    }

    // For team target, remove user_uid from form data
    if (targetType === 'team') {
        formData.delete('user_uid');
    }

    Swal.fire({
        title: 'Saving...',
        text: 'Please wait',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    const ajaxUrl = (typeof MAIN_URL !== 'undefined' ? MAIN_URL : '') + 'ajax/save-target.php';
    
    fetch(ajaxUrl, {
        method: 'POST',
        body: formData
    })
    .then(async response => {
        const text = await response.text();
        console.log('Response:', text);
        
        try {
            return JSON.parse(text);
        } catch (e) {
            console.error('JSON Parse Error:', e);
            throw new Error('Server returned invalid response');
        }
    })
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
        console.error('Fetch Error:', error);
        Swal.fire('Error', 'An error occurred: ' + error.message, 'error');
    });
}

// Update achievement
function updateAchievement(targetId, userName, targetAmount, currentAchieved) {
    document.getElementById('achievement_target_id').value = targetId;
    document.getElementById('achievement_user_name').value = userName;
    document.getElementById('achievement_target_amount').value = '₹' + parseFloat(targetAmount).toLocaleString('en-IN', {
        minimumFractionDigits: 2
    });
    document.getElementById('achieved_amount').value = currentAchieved || 0;
    document.getElementById('achievement_notes').value = '';

    achievementModal.show();
}

// Save achievement
function saveAchievement() {
    const targetId = document.getElementById('achievement_target_id').value;
    const achievedAmount = document.getElementById('achieved_amount').value;
    const notes = document.getElementById('achievement_notes').value;

    if (!achievedAmount || parseFloat(achievedAmount) < 0) {
        Swal.fire('Error', 'Please enter a valid achieved amount', 'error');
        return;
    }

    Swal.fire({
        title: 'Updating...',
        text: 'Please wait',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    const formData = new FormData();
    formData.append('target_id', targetId);
    formData.append('achieved_amount', achievedAmount);
    formData.append('notes', notes);

    const ajaxUrl = (typeof MAIN_URL !== 'undefined' ? MAIN_URL : '') + 'ajax/update-achievement.php';
    
    fetch(ajaxUrl, {
        method: 'POST',
        body: formData
    })
    .then(async response => {
        const text = await response.text();
        console.log('Achievement Response:', text);
        
        try {
            return JSON.parse(text);
        } catch (e) {
            console.error('JSON Parse Error:', e);
            throw new Error('Server returned invalid response');
        }
    })
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
        console.error('Fetch Error:', error);
        Swal.fire('Error', 'An error occurred: ' + error.message, 'error');
    });
}

// Sidebar toggle function
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

// Close sidebar on resize
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

// Escape key handler
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const sidebar = document.getElementById('mainSidebar');
        if (sidebar && sidebar.classList.contains('active')) {
            toggleSidebar();
        }
    }
});