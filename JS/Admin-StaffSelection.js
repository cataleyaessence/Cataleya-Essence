(() => {
    'use strict';

    const config = window.staffAdminConfig || {};
    const $ = (id) => document.getElementById(id);
    const toggle = $('brandToggle');
    const staffGrid = $('staffGrid');
    const scheduleModal = $('modalOverlay');
    const addStaffModal = $('addStaffModal');
    const addStaffForm = $('addStaffForm');
    let activeBrand = 'cataleya';
    let staffPhotoPreviewUrl = '';

    function clearStaffPhotoPreviewUrl() {
        if (staffPhotoPreviewUrl) {
            URL.revokeObjectURL(staffPhotoPreviewUrl);
            staffPhotoPreviewUrl = '';
        }
    }

    function resetStaffPhotoField() {
        clearStaffPhotoPreviewUrl();
        $('staffPhoto').value = '';
        $('staffPhotoFileName').textContent = 'Choose a staff photo';
        $('staffPhotoPreview').innerHTML = '<span>No photo selected</span>';
    }

    function validateStaffPhoto(file) {
        const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!allowedTypes.includes(file.type)) {
            throw new Error('Upload a JPG, PNG, GIF, or WebP photo.');
        }
        if (file.size > 5 * 1024 * 1024) {
            throw new Error('Staff photo must not exceed 5 MB.');
        }
    }

    function showStaffPhotoPreview(file) {
        clearStaffPhotoPreviewUrl();
        staffPhotoPreviewUrl = URL.createObjectURL(file);
        $('staffPhotoFileName').textContent = file.name;
        $('staffPhotoPreview').innerHTML = `<img src="${staffPhotoPreviewUrl}" alt="New staff photo preview" /><span>Photo selected</span>`;
    }

    function brandName(brand) {
        return brand === 'serenity' ? 'Serenity' : 'Cataleya';
    }

    function staffCards() {
        return Array.from(staffGrid.querySelectorAll('.staff-card'));
    }

    function updateSummary() {
        const visibleCards = staffCards().filter((card) => card.dataset.brand === activeBrand);
        const availableCount = visibleCards.filter((card) => card.dataset.available === '1').length;
        const unavailableCount = visibleCards.length - availableCount;
        const label = brandName(activeBrand);

        $('totalStaff').textContent = String(visibleCards.length);
        $('availableStaff').textContent = String(availableCount);
        $('totalStaffLabel').textContent = `${label} Staff`;
        $('staffCardCount').textContent = `${visibleCards.length} staff`;
        $('staffAvailabilityTrend').textContent = availableCount > 0
            ? `${availableCount} available for bookings`
            : 'No one available';
        $('unavailableStaffTrend').textContent = `${unavailableCount} unavailable`;
        $('staffEmptyState').hidden = visibleCards.length !== 0;
    }

    function switchBrand(brand) {
        activeBrand = brand === 'serenity' ? 'serenity' : 'cataleya';
        const label = brandName(activeBrand);
        toggle.checked = activeBrand === 'serenity';
        $('toggleLabelLeft').classList.toggle('active', activeBrand === 'cataleya');
        $('toggleLabelRight').classList.toggle('active', activeBrand === 'serenity');
        $('pageTitle').textContent = `${label} Staff Management`;
        $('pageSub').textContent = `Manage ${label} staff, availability, and schedules`;
        $('staffGridTitle').textContent = `${label} Staff`;

        staffCards().forEach((card) => {
            card.hidden = card.dataset.brand !== activeBrand;
        });
        updateSummary();
    }

    function showScheduleModal(title, message) {
        $('modalTitle').textContent = title;
        $('modalMessage').textContent = message;
        scheduleModal.classList.add('open');
        scheduleModal.setAttribute('aria-hidden', 'false');
    }

    function closeScheduleModal() {
        scheduleModal.classList.remove('open');
        scheduleModal.setAttribute('aria-hidden', 'true');
    }

    function closeAddStaffModal() {
        addStaffModal.classList.remove('open');
        addStaffModal.setAttribute('aria-hidden', 'true');
        addStaffForm.reset();
        resetStaffPhotoField();
        $('addStaffFeedback').textContent = '';
        $('addStaffFeedback').className = 'form-feedback';
    }

    function openAddStaffModal() {
        addStaffForm.reset();
        resetStaffPhotoField();
        $('staffBranch').value = activeBrand;
        $('addStaffFeedback').textContent = '';
        $('addStaffFeedback').className = 'form-feedback';
        addStaffModal.classList.add('open');
        addStaffModal.setAttribute('aria-hidden', 'false');
        window.setTimeout(() => addStaffForm.elements.name.focus(), 0);
    }

    function setAddStaffSubmitting(isSubmitting) {
        const button = $('addStaffSubmit');
        button.disabled = isSubmitting;
        button.innerHTML = isSubmitting
            ? '<i class="fas fa-spinner fa-spin"></i> Saving...'
            : '<i class="fa-solid fa-plus"></i> Add Staff';
    }

    toggle.addEventListener('change', () => switchBrand(toggle.checked ? 'serenity' : 'cataleya'));

    staffGrid.addEventListener('click', (event) => {
        const fireButton = event.target.closest('.fire-btn');
        if (fireButton) {
            const card = fireButton.closest('.staff-card');
            const name = card.querySelector('.staff-name')?.textContent || 'this staff member';
            if (!window.confirm(`Mark ${name} as fired? They will no longer be available for new bookings.`)) {
                return;
            }

            fireButton.disabled = true;
            fireButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Removing...';
            const payload = new FormData();
            payload.append('id', fireButton.dataset.id);

            fetch(`${config.apiUrl}?action=fire`, {
                method: 'POST',
                headers: { 'X-CSRF-Token': config.csrfToken || '' },
                body: payload
            })
                .then((response) => response.json().then((data) => ({ response, data })))
                .then(({ response, data }) => {
                    if (!response.ok || !data.success) {
                        throw new Error(data.error || 'Unable to remove the staff member.');
                    }
                    card.remove();
                    updateSummary();
                    showScheduleModal('Staff Removed', `${name} has been marked as fired and removed from active staff lists.`);
                })
                .catch((error) => {
                    fireButton.disabled = false;
                    fireButton.innerHTML = '<i class="fas fa-user-slash"></i> Fired Staff';
                    showScheduleModal('Unable to Remove Staff', error.message);
                });
            return;
        }

        const button = event.target.closest('.view-btn');
        if (!button) return;

        const card = button.closest('.staff-card');
        const name = card.querySelector('.staff-name')?.textContent || 'Staff member';
        const role = card.querySelector('.staff-role')?.textContent || 'Staff';
        const branch = brandName(card.dataset.brand);
        showScheduleModal(
            `${name}'s Schedule`,
            `View and manage the schedule for ${name}, ${role} at the ${branch} branch.`
        );
    });

    $('modalCloseBtn').addEventListener('click', closeScheduleModal);
    scheduleModal.addEventListener('click', (event) => {
        if (event.target === scheduleModal) closeScheduleModal();
    });

    $('addStaffBtn').addEventListener('click', openAddStaffModal);
    $('addStaffClose').addEventListener('click', closeAddStaffModal);
    $('addStaffCancel').addEventListener('click', closeAddStaffModal);
    addStaffModal.addEventListener('click', (event) => {
        if (event.target === addStaffModal) closeAddStaffModal();
    });
    $('staffPhoto').addEventListener('change', (event) => {
        const file = event.target.files[0];
        if (!file) {
            resetStaffPhotoField();
            return;
        }

        try {
            validateStaffPhoto(file);
            showStaffPhotoPreview(file);
        } catch (error) {
            resetStaffPhotoField();
            $('addStaffFeedback').textContent = error.message;
            $('addStaffFeedback').className = 'form-feedback error';
        }
    });

    addStaffForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        const feedback = $('addStaffFeedback');
        feedback.textContent = '';
        feedback.className = 'form-feedback';
        setAddStaffSubmitting(true);

        try {
            const response = await fetch(config.apiUrl, {
                method: 'POST',
                headers: { 'X-CSRF-Token': config.csrfToken || '' },
                body: new FormData(addStaffForm)
            });
            const data = await response.json().catch(() => ({}));
            if (!response.ok || !data.success) {
                throw new Error(data.error || 'Unable to add the staff member.');
            }

            feedback.textContent = `${data.staff.full_name} was added successfully.`;
            feedback.classList.add('success');
            window.setTimeout(() => window.location.reload(), 450);
        } catch (error) {
            feedback.textContent = error.message;
            feedback.classList.add('error');
            setAddStaffSubmitting(false);
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') return;
        if (addStaffModal.classList.contains('open')) closeAddStaffModal();
        if (scheduleModal.classList.contains('open')) closeScheduleModal();
    });

    switchBrand('cataleya');
})();
