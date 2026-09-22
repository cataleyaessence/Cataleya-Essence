// ===== PROFILE PHOTO UPLOAD =====
document.addEventListener('DOMContentLoaded', function() {
    const uploadPhotoBtn = document.getElementById('uploadPhotoBtn');
    const removePhotoBtn = document.getElementById('removePhotoBtn');
    const profilePhotoInput = document.getElementById('profilePhotoInput');
    const currentProfilePhoto = document.getElementById('currentProfilePhoto');

    // Upload button click - trigger file input
    if (uploadPhotoBtn) {
        uploadPhotoBtn.addEventListener('click', function() {
            profilePhotoInput.click();
        });
    }

    // File input change - handle upload
    if (profilePhotoInput) {
        profilePhotoInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (!file) return;

            // Validate file size (5MB)
            if (file.size > 5 * 1024 * 1024) {
                alert('File size exceeds 5MB limit');
                profilePhotoInput.value = '';
                return;
            }

            // Validate file type
            const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
            if (!allowedTypes.includes(file.type)) {
                alert('Invalid file type. Only JPG, PNG, GIF, and WebP are allowed.');
                profilePhotoInput.value = '';
                return;
            }

            // Create FormData and upload
            const formData = new FormData();
            formData.append('profile_photo', file);

            // Show loading state
            uploadPhotoBtn.disabled = true;
            uploadPhotoBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Uploading...';

            fetch('../api/upload_profile_photo.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update current photo display
                    if (currentProfilePhoto) {
                        currentProfilePhoto.src = data.photo_url;
                    }
                    
                    // Show remove button
                    if (removePhotoBtn) {
                        removePhotoBtn.style.display = 'inline-flex';
                    }
                    
                    // Update navbar avatar
                    updateNavbarAvatar(data.photo_url);
                    
                    alert('Profile photo updated successfully!');
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Upload error:', error);
                alert('Error uploading photo. Please try again.');
            })
            .finally(() => {
                uploadPhotoBtn.disabled = false;
                uploadPhotoBtn.innerHTML = '<i class="fas fa-upload"></i> Upload Photo';
                profilePhotoInput.value = '';
            });
        });
    }

    // Remove button click - handle removal
    if (removePhotoBtn) {
        removePhotoBtn.addEventListener('click', function() {
            if (!confirm('Are you sure you want to remove your profile photo?')) {
                return;
            }

            const formData = new FormData();
            formData.append('action', 'remove');

            removePhotoBtn.disabled = true;
            removePhotoBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Removing...';

            fetch('../api/upload_profile_photo.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Reload page to show placeholder
                    window.location.reload();
                } else {
                    alert('Error: ' + data.message);
                    removePhotoBtn.disabled = false;
                    removePhotoBtn.innerHTML = '<i class="fas fa-trash"></i> Remove Photo';
                }
            })
            .catch(error => {
                console.error('Remove error:', error);
                alert('Error removing photo. Please try again.');
                removePhotoBtn.disabled = false;
                removePhotoBtn.innerHTML = '<i class="fas fa-trash"></i> Remove Photo';
            });
        });
    }

    // Function to update navbar avatar
    function updateNavbarAvatar(photoUrl) {
        const profileAvatar = document.getElementById('profileAvatar');
        if (profileAvatar) {
            const avatarImage = profileAvatar.querySelector('.avatar-image');
            const avatarInitials = profileAvatar.querySelector('.avatar-initials');
            
            if (avatarImage) {
                avatarImage.src = photoUrl;
            } else if (avatarInitials) {
                // Replace initials with image
                avatarInitials.remove();
                const img = document.createElement('img');
                img.src = photoUrl;
                img.alt = 'Profile';
                img.className = 'avatar-image';
                profileAvatar.insertBefore(img, profileAvatar.firstChild);
            }
        }
    }

    // ===== NAVBAR DROPDOWN =====
    const profileWrapper = document.getElementById('profileWrapper');
    const profileDropdown = document.getElementById('profileDropdown');
    const dropdownOverlay = document.getElementById('dropdownOverlay');

    if (profileWrapper && profileDropdown) {
        profileWrapper.addEventListener('click', function(e) {
            e.stopPropagation();
            const isExpanded = profileWrapper.getAttribute('aria-expanded') === 'true';
            profileWrapper.setAttribute('aria-expanded', !isExpanded);
            profileDropdown.classList.toggle('active', !isExpanded);
            if (dropdownOverlay) {
                dropdownOverlay.classList.toggle('active', !isExpanded);
            }
        });
    }

    if (dropdownOverlay) {
        dropdownOverlay.addEventListener('click', function() {
            if (profileWrapper) {
                profileWrapper.setAttribute('aria-expanded', 'false');
            }
            if (profileDropdown) {
                profileDropdown.classList.remove('active');
            }
            dropdownOverlay.classList.remove('active');
        });
    }

    // ===== HAMBURGER MENU =====
    const hamburger = document.getElementById('hamburger');
    const navLinks = document.getElementById('nav-links');

    if (hamburger && navLinks) {
        hamburger.addEventListener('click', function() {
            const isExpanded = hamburger.getAttribute('aria-expanded') === 'true';
            hamburger.setAttribute('aria-expanded', !isExpanded);
            hamburger.classList.toggle('active', !isExpanded);
            navLinks.classList.toggle('active', !isExpanded);
        });
    }

    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        if (profileWrapper && !profileWrapper.contains(e.target)) {
            profileWrapper.setAttribute('aria-expanded', 'false');
            if (profileDropdown) {
                profileDropdown.classList.remove('active');
            }
            if (dropdownOverlay) {
                dropdownOverlay.classList.remove('active');
            }
        }
    });

    // ===== EMAIL CHANGE OTP FLOW =====
    const emailForm = document.getElementById('emailForm');
    const otpForm = document.getElementById('otpForm');
    const cancelOtpBtn = document.getElementById('cancelOtpBtn');
    const emailInput = document.getElementById('email');
    const otpHidden = document.getElementById('otpHidden');
    const otpBoxes = document.querySelectorAll('.otp-box');

    // Handle email form submission (send OTP)
    if (emailForm) {
        emailForm.addEventListener('submit', function(e) {
            const email = emailInput.value.trim();
            if (!email || !email.includes('@')) {
                e.preventDefault();
                alert('Please enter a valid email address');
                return;
            }
            // Form will submit normally to send OTP
        });
    }

    // Handle cancel button
    if (cancelOtpBtn) {
        cancelOtpBtn.addEventListener('click', function() {
            if (otpForm) {
                otpForm.style.display = 'none';
            }
            if (emailForm) {
                emailForm.style.display = 'block';
            }
            // Clear OTP boxes
            if (otpBoxes) {
                otpBoxes.forEach(box => box.value = '');
            }
        });
    }

    // Check if OTP was sent (from PHP success message)
    const successAlert = document.querySelector('.alert.alert-success');
    if (successAlert && successAlert.textContent.includes('OTP sent')) {
        if (emailForm) {
            emailForm.style.display = 'none';
        }
        if (otpForm) {
            otpForm.style.display = 'block';
        }
        // Focus first OTP box
        if (otpBoxes.length > 0) {
            otpBoxes[0].focus();
        }
    }

    // OTP box handling
    if (otpBoxes.length > 0) {
        otpBoxes.forEach((box, index) => {
            // Handle input
            box.addEventListener('input', function(e) {
                // Only allow numbers
                this.value = this.value.replace(/[^0-9]/g, '');
                
                // Auto-advance to next box
                if (this.value.length === 1 && index < otpBoxes.length - 1) {
                    otpBoxes[index + 1].focus();
                }
                
                // Update hidden input with combined OTP
                updateOtpHidden();
            });
            
            // Handle keydown (backspace, arrow keys)
            box.addEventListener('keydown', function(e) {
                // Backspace: move to previous box if current is empty
                if (e.key === 'Backspace' && this.value === '' && index > 0) {
                    otpBoxes[index - 1].focus();
                }
                // Left arrow: move to previous box
                if (e.key === 'ArrowLeft' && index > 0) {
                    otpBoxes[index - 1].focus();
                }
                // Right arrow: move to next box
                if (e.key === 'ArrowRight' && index < otpBoxes.length - 1) {
                    otpBoxes[index + 1].focus();
                }
            });
            
            // Handle paste
            box.addEventListener('paste', function(e) {
                e.preventDefault();
                const pasteData = (e.clipboardData || window.clipboardData).getData('text');
                const numbers = pasteData.replace(/[^0-9]/g, '').split('');
                
                // Fill boxes with pasted numbers
                numbers.forEach((num, i) => {
                    if (index + i < otpBoxes.length) {
                        otpBoxes[index + i].value = num;
                    }
                });
                
                // Focus the last filled box or next empty box
                const lastIndex = Math.min(index + numbers.length, otpBoxes.length - 1);
                otpBoxes[lastIndex].focus();
                
                // Update hidden input
                updateOtpHidden();
            });
        });
    }
    
    // Function to update hidden OTP input
    function updateOtpHidden() {
        if (otpHidden && otpBoxes.length > 0) {
            const otpValue = Array.from(otpBoxes).map(box => box.value).join('');
            otpHidden.value = otpValue;
        }
    }
    
    // Validate OTP form before submission
    if (otpForm) {
        otpForm.addEventListener('submit', function(e) {
            const otpValue = Array.from(otpBoxes).map(box => box.value).join('');
            if (otpValue.length !== 6) {
                e.preventDefault();
                alert('Please enter all 6 digits of the OTP code');
                return;
            }
        });
    }

    // ===== PASSWORD VISIBILITY TOGGLE =====
    const togglePasswordButtons = document.querySelectorAll('.toggle-password');
    togglePasswordButtons.forEach(button => {
        button.addEventListener('click', function() {
            const targetId = this.getAttribute('data-target');
            const passwordInput = document.getElementById(targetId);
            const icon = this.querySelector('i');
            
            if (passwordInput) {
                if (passwordInput.type === 'password') {
                    passwordInput.type = 'text';
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                } else {
                    passwordInput.type = 'password';
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                }
            }
        });
    });
});
