(() => {
    'use strict';

    const config = window.serviceData || {};
    let services = Array.isArray(config.services) ? config.services : [];
    const subCategories = config.subCategories || {};
    let editingId = null;
    let editImagePreviewUrl = '';

    const $ = (id) => document.getElementById(id);
    const addForm = $('addServiceForm');
    const editForm = $('editServiceForm');
    const editModal = $('editModal');
    const searchInput = $('tableSearch');

    function escapeHtml(value) {
        return String(value ?? '').replace(/[&<>'"]/g, (character) => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            "'": '&#039;',
            '"': '&quot;'
        })[character]);
    }

    function formatPrice(price) {
        return new Intl.NumberFormat('en-PH', {
            style: 'currency',
            currency: 'PHP',
            minimumFractionDigits: 2
        }).format(Number(price) || 0);
    }

    function formatDuration(minutes) {
        const value = Number(minutes);
        if (!Number.isInteger(value) || value < 1) {
            return '—';
        }

        const hours = Math.floor(value / 60);
        const remainingMinutes = value % 60;
        const parts = [];
        if (hours > 0) parts.push(`${hours} hr${hours === 1 ? '' : 's'}`);
        if (remainingMinutes > 0) parts.push(`${remainingMinutes} min`);
        return parts.join(' ');
    }

    function showMessage(message, type = 'success') {
        const toast = $('toast');
        if (!toast) {
            window.alert(message);
            return;
        }

        toast.textContent = message;
        toast.style.cssText = [
            'position: fixed',
            'right: 24px',
            'bottom: 24px',
            'z-index: 1000',
            'max-width: 360px',
            'padding: 12px 16px',
            'border-radius: 10px',
            'box-shadow: 0 8px 24px rgba(0,0,0,.15)',
            'color: #fff',
            `background: ${type === 'error' ? '#b33c3c' : '#3f7c5b'}`,
            'font-size: 14px'
        ].join(';');

        window.clearTimeout(showMessage.timeoutId);
        showMessage.timeoutId = window.setTimeout(() => {
            toast.removeAttribute('style');
            toast.textContent = '';
        }, 3500);
    }

    function populateSubCategories(selectId, categoryValue, selectedValue = '') {
        const select = $(selectId);
        if (!select) return;

        const options = subCategories[categoryValue] || [];
        select.replaceChildren();
        options.forEach((optionValue) => {
            const option = document.createElement('option');
            option.value = optionValue;
            option.textContent = optionValue;
            option.selected = optionValue === selectedValue;
            select.appendChild(option);
        });
    }

    function renderTable(filter = '') {
        const query = filter.trim().toLowerCase();
        const filteredServices = services.filter((service) => {
            const searchable = [service.name, service.category, service.subCategory].join(' ').toLowerCase();
            return searchable.includes(query);
        });
        const body = $('serviceTableBody');

        if (filteredServices.length === 0) {
            body.innerHTML = '<tr><td colspan="7" style="text-align:center; padding:24px;">No services found.</td></tr>';
        } else {
            body.innerHTML = filteredServices.map((service, index) => `
                <tr>
                    <td>${index + 1}</td>
                    <td><strong>${escapeHtml(service.name)}</strong></td>
                    <td>${escapeHtml(service.category)}</td>
                    <td>${escapeHtml(service.subCategory)}</td>
                    <td>${formatPrice(service.price)}</td>
                    <td>${formatDuration(service.durationMinutes)}</td>
                    <td>
                        <button class="btn-edit" type="button" data-id="${Number(service.id)}"><i class="fas fa-edit"></i> Edit</button>
                        <button class="btn-delete" type="button" data-id="${Number(service.id)}"><i class="fas fa-trash-alt"></i> Remove</button>
                    </td>
                </tr>
            `).join('');
        }

        $('rowCount').textContent = `${filteredServices.length} service${filteredServices.length === 1 ? '' : 's'}`;
    }

    async function sendRequest(action, payload) {
        const formData = payload instanceof FormData ? payload : objectToFormData(payload);
        let response;
        try {
            response = await fetch(`${config.apiUrl}?action=${encodeURIComponent(action)}`, {
                method: 'POST',
                headers: {
                    'X-CSRF-Token': config.csrfToken || ''
                },
                body: formData
            });
        } catch (error) {
            throw new Error('Unable to reach the server. Please try again.');
        }

        const data = await response.json().catch(() => ({}));
        if (!response.ok || !data.success) {
            throw new Error(data.error || 'Unable to save the service.');
        }

        return data;
    }

    function serviceFieldId(prefix, fieldName) {
        return prefix ? `${prefix}Service${fieldName}` : `service${fieldName}`;
    }

    function getServicePayload(prefix = '') {
        const formData = new FormData();
        formData.append('name', $(serviceFieldId(prefix, 'Name')).value.trim());
        formData.append('category', $(serviceFieldId(prefix, 'Category')).value);
        formData.append('subCategory', $(serviceFieldId(prefix, 'SubCategory')).value);
        formData.append('price', $(serviceFieldId(prefix, 'Price')).value);
        formData.append('durationMinutes', String(getDurationMinutes(prefix)));
        formData.append('description', $(serviceFieldId(prefix, 'Description')).value.trim());

        const imageFile = $(serviceFieldId(prefix, 'Image')).files[0];
        if (imageFile) {
            validateImageFile(imageFile);
            formData.append('image', imageFile);
        }

        return formData;
    }

    function objectToFormData(payload) {
        const formData = new FormData();
        Object.entries(payload).forEach(([key, value]) => formData.append(key, String(value)));
        return formData;
    }

    function validateImageFile(file) {
        const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!allowedTypes.includes(file.type)) {
            throw new Error('Upload a JPG, PNG, GIF, or WebP image.');
        }
        if (file.size > 5 * 1024 * 1024) {
            throw new Error('Image size must not exceed 5 MB.');
        }
    }

    function getDurationMinutes(prefix = '') {
        const hours = Number($(serviceFieldId(prefix, 'Hours')).value || 0);
        const minutes = Number($(serviceFieldId(prefix, 'Minutes')).value || 0);
        return (hours * 60) + minutes;
    }

    function setDurationInputs(prefix, durationMinutes) {
        const totalMinutes = Number(durationMinutes) || 0;
        $(serviceFieldId(prefix, 'Hours')).value = totalMinutes > 0 ? Math.floor(totalMinutes / 60) : '';
        $(serviceFieldId(prefix, 'Minutes')).value = totalMinutes > 0 ? totalMinutes % 60 : '';
    }

    function setSubmitting(form, isSubmitting) {
        const button = form.querySelector('button[type="submit"]');
        if (!button) return;
        if (!button.dataset.defaultText) button.dataset.defaultText = button.innerHTML;
        button.disabled = isSubmitting;
        button.innerHTML = isSubmitting ? '<i class="fas fa-spinner fa-spin"></i> Saving...' : button.dataset.defaultText;
    }

    function clearEditImagePreviewUrl() {
        if (editImagePreviewUrl) {
            URL.revokeObjectURL(editImagePreviewUrl);
            editImagePreviewUrl = '';
        }
    }

    function showEditImagePreview({ image = '', serviceName = '', file = null } = {}) {
        const preview = $('editImagePreview');
        const fileName = $('editImageFileName');
        if (!preview || !fileName) return;

        clearEditImagePreviewUrl();

        if (file) {
            editImagePreviewUrl = URL.createObjectURL(file);
            fileName.textContent = file.name;
            preview.innerHTML = `<img src="${editImagePreviewUrl}" alt="New image preview for ${escapeHtml(serviceName)}" /><span>New image selected</span>`;
            return;
        }

        fileName.textContent = 'Choose a replacement image';
        preview.innerHTML = image
            ? `<img src="${escapeHtml(image)}" alt="Current image for ${escapeHtml(serviceName)}" /><span>Current image</span>`
            : '<small>No image uploaded yet.</small>';
    }

    function updateFilePickerName(inputId, nameId, emptyText) {
        const input = $(inputId);
        const name = $(nameId);
        if (!input || !name) return;
        name.textContent = input.files[0]?.name || emptyText;
    }

    function openEditModal(id) {
        const service = services.find((item) => Number(item.id) === Number(id));
        if (!service) return;

        editingId = Number(service.id);
        $('editServiceId').value = editingId;
        $('editServiceName').value = service.name || '';
        $('editServiceCategory').value = service.category || '';
        populateSubCategories('editServiceSubCategory', service.category, service.subCategory);
        $('editServicePrice').value = service.price ?? '';
        setDurationInputs('edit', service.durationMinutes);
        $('editServiceImage').value = '';
        showEditImagePreview({ image: service.image, serviceName: service.name });
        $('editServiceDescription').value = service.description || '';
        editModal.classList.add('active');
    }

    function closeEditModal() {
        editModal.classList.remove('active');
        editingId = null;
        editForm.reset();
        clearEditImagePreviewUrl();
    }

    $('serviceCategory').addEventListener('change', (event) => {
        populateSubCategories('serviceSubCategory', event.target.value);
    });
    $('editServiceCategory').addEventListener('change', (event) => {
        populateSubCategories('editServiceSubCategory', event.target.value);
    });
    $('editServiceImage').addEventListener('change', (event) => {
        const file = event.target.files[0];
        const service = services.find((item) => Number(item.id) === editingId);

        if (!file) {
            showEditImagePreview({ image: service?.image, serviceName: service?.name });
            return;
        }

        try {
            validateImageFile(file);
            showEditImagePreview({ serviceName: $('editServiceName').value.trim() || service?.name, file });
        } catch (error) {
            event.target.value = '';
            showEditImagePreview({ image: service?.image, serviceName: service?.name });
            showMessage(error.message, 'error');
        }
    });
    $('serviceImage').addEventListener('change', (event) => {
        const file = event.target.files[0];
        if (!file) {
            updateFilePickerName('serviceImage', 'serviceImageFileName', 'Choose a service image');
            return;
        }

        try {
            validateImageFile(file);
            updateFilePickerName('serviceImage', 'serviceImageFileName', 'Choose a service image');
        } catch (error) {
            event.target.value = '';
            updateFilePickerName('serviceImage', 'serviceImageFileName', 'Choose a service image');
            showMessage(error.message, 'error');
        }
    });

    addForm.addEventListener('reset', () => {
        window.setTimeout(() => {
            populateSubCategories('serviceSubCategory', $('serviceCategory').value);
            updateFilePickerName('serviceImage', 'serviceImageFileName', 'Choose a service image');
        }, 0);
    });

    addForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        setSubmitting(addForm, true);

        try {
            const payload = getServicePayload();
            const data = await sendRequest('create', payload);
            services.push(data.service);
            addForm.reset();
            populateSubCategories('serviceSubCategory', $('serviceCategory').value);
            renderTable(searchInput.value);
            showMessage(`“${data.service.name}” was added to the catalog.`);
        } catch (error) {
            showMessage(error.message, 'error');
        } finally {
            setSubmitting(addForm, false);
        }
    });

    editForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (!editingId) return;

        setSubmitting(editForm, true);

        try {
            const payload = getServicePayload('edit');
            payload.append('id', String(editingId));
            const data = await sendRequest('update', payload);
            const index = services.findIndex((item) => Number(item.id) === editingId);
            if (index !== -1) services[index] = data.service;
            closeEditModal();
            renderTable(searchInput.value);
            showMessage(`“${data.service.name}” was updated.`);
        } catch (error) {
            showMessage(error.message, 'error');
        } finally {
            setSubmitting(editForm, false);
        }
    });

    $('serviceTableBody').addEventListener('click', async (event) => {
        const button = event.target.closest('button[data-id]');
        if (!button) return;

        const id = Number(button.dataset.id);
        if (button.classList.contains('btn-edit')) {
            openEditModal(id);
            return;
        }

        if (!button.classList.contains('btn-delete')) return;
        const service = services.find((item) => Number(item.id) === id);
        if (!service || !window.confirm(`Remove “${service.name}” from the booking catalog? Existing bookings will be kept.`)) {
            return;
        }

        button.disabled = true;
        try {
            await sendRequest('delete', { id });
            services = services.filter((item) => Number(item.id) !== id);
            renderTable(searchInput.value);
            showMessage(`“${service.name}” was removed from the catalog.`);
        } catch (error) {
            button.disabled = false;
            showMessage(error.message, 'error');
        }
    });

    $('editModalClose').addEventListener('click', closeEditModal);
    $('editModalCancel').addEventListener('click', closeEditModal);
    editModal.addEventListener('click', (event) => {
        if (event.target === editModal) closeEditModal();
    });
    searchInput.addEventListener('input', (event) => renderTable(event.target.value));

    populateSubCategories('serviceSubCategory', $('serviceCategory').value);
    renderTable();
})();
