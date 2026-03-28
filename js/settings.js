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

function toggleEdit(section) {
    const viewDiv = document.getElementById(section + 'View');
    const editDiv = document.getElementById(section + 'Edit');

    if (viewDiv && editDiv) {
        viewDiv.style.display = viewDiv.style.display === 'none' ? 'block' : 'none';
        editDiv.style.display = editDiv.style.display === 'none' ? 'block' : 'none';
    }
}

// Handle profile form submission
document.getElementById('profileForm')?.addEventListener('submit', async function (e) {
    e.preventDefault();

    const formData = new FormData(this);

    try {
        const response = await fetch(MAIN_URL + 'ajax/settings.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: 'Profile updated successfully',
                timer: 2000,
                showConfirmButton: false
            }).then(() => {
                location.reload();
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: data.message || 'Failed to update profile'
            });
        }
    } catch (error) {
        console.error('Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'An error occurred. Please try again.'
        });
    }
});

// Handle image preview
document.getElementById('profileImage')?.addEventListener('change', function (e) {
    const file = this.files[0];
    if (file) {
        if (file.size > 2 * 1024 * 1024) {
            Swal.fire({
                icon: 'error',
                title: 'File Too Large',
                text: 'Please select an image under 2MB'
            });
            this.value = '';
            return;
        }

        const reader = new FileReader();
        reader.onload = function (e) {
            document.getElementById('imagePreview').src = e.target.result;
            document.getElementById('profileAvatar').src = e.target.result;
            document.getElementById('headerAvatar').src = e.target.result;
        };
        reader.readAsDataURL(file);

        // Upload image
        uploadProfileImage(file);
    }
});

async function uploadProfileImage(file) {
    const formData = new FormData();
    formData.append('profile_image', file);

    try {
        const response = await fetch(MAIN_URL + 'ajax/upload-image.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: 'Profile picture updated',
                timer: 1500,
                showConfirmButton: false
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: data.message || 'Failed to upload image'
            });
        }
    } catch (error) {
        console.error('Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'An error occurred while uploading'
        });
    }
}

// Close sidebar on resize
window.addEventListener('resize', function () {
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

// Close on escape key
document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
        const sidebar = document.getElementById('mainSidebar');
        if (sidebar.classList.contains('active')) {
            toggleSidebar();
        }
    }
});
