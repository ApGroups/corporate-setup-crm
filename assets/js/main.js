/**
 * Corporate Setup Limited - Internal CRM
 * Main JavaScript
 */

// Sidebar Toggle
document.addEventListener('DOMContentLoaded', function() {
    const menuToggle = document.getElementById('menuToggle');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    const globalSearch = document.getElementById('globalSearch');
    const userMenuTrigger = document.getElementById('userMenuTrigger');

    if (menuToggle) {
        menuToggle.addEventListener('click', function() {
            sidebar.classList.toggle('active');
            overlay.classList.toggle('active');
        });
    }

    if (overlay) {
        overlay.addEventListener('click', function() {
            sidebar.classList.remove('active');
            overlay.classList.remove('active');
        });
    }

    if (globalSearch) {
        globalSearch.addEventListener('keyup', handleSearch);
    }

    if (userMenuTrigger) {
        userMenuTrigger.addEventListener('click', function(e) {
            e.stopPropagation();
            toggleDropdown();
        });
    }
});

// Dropdown Toggle
function toggleDropdown() {
    const dropdown = document.getElementById('userDropdown');
    dropdown.classList.toggle('active');
}

// Close dropdown when clicking outside
document.addEventListener('click', function(e) {
    const dropdown = document.getElementById('userDropdown');
    if (dropdown && !e.target.closest('.dropdown')) {
        dropdown.classList.remove('active');
    }
});

// Global Search
function handleSearch(e) {
    if (e.key === 'Enter') {
        const query = e.target.value.trim();
        if (query.length > 2) {
            window.location.href = 'search.php?q=' + encodeURIComponent(query);
        }
    }
}

// Modal Functions
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('active');
        document.body.style.overflow = '';
    }
}

// Close modal on overlay click
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal-overlay')) {
        e.target.classList.remove('active');
        document.body.style.overflow = '';
    }
});

// CSP-safe delegated click handlers (replaces inline onclick)
document.addEventListener('click', function(e) {
    const openModalBtn = e.target.closest('[data-open-modal]');
    if (openModalBtn) {
        openModal(openModalBtn.getAttribute('data-open-modal'));
        return;
    }

    const closeModalBtn = e.target.closest('[data-close-modal]');
    if (closeModalBtn) {
        closeModal(closeModalBtn.getAttribute('data-close-modal'));
        return;
    }

    const exportBtn = e.target.closest('[data-export-table-id]');
    if (exportBtn) {
        const tableId = exportBtn.getAttribute('data-export-table-id');
        const filename = exportBtn.getAttribute('data-export-filename') || 'export';
        exportTableToCSV(tableId, filename);
        return;
    }

    const deleteCompanyBtn = e.target.closest('[data-delete-company-id]');
    if (deleteCompanyBtn) {
        deleteCompany(deleteCompanyBtn.getAttribute('data-delete-company-id'), deleteCompanyBtn);
        return;
    }

    const deleteContactBtn = e.target.closest('[data-delete-contact-id]');
    if (deleteContactBtn) {
        deleteContact(deleteContactBtn.getAttribute('data-delete-contact-id'));
        return;
    }

    const deleteTaskBtn = e.target.closest('[data-delete-task-id]');
    if (deleteTaskBtn) {
        deleteTask(deleteTaskBtn.getAttribute('data-delete-task-id'));
        return;
    }

    const deleteMeetingBtn = e.target.closest('[data-delete-meeting-id]');
    if (deleteMeetingBtn) {
        deleteMeeting(deleteMeetingBtn.getAttribute('data-delete-meeting-id'), deleteMeetingBtn);
        return;
    }

    const deleteStaffBtn = e.target.closest('[data-delete-staff-id]');
    if (deleteStaffBtn) {
        deleteStaff(deleteStaffBtn.getAttribute('data-delete-staff-id'), deleteStaffBtn);
        return;
    }
});

// Close modal on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const activeModal = document.querySelector('.modal-overlay.active');
        if (activeModal) {
            activeModal.classList.remove('active');
            document.body.style.overflow = '';
        }
    }
});

// Confirm Delete
function confirmDelete(message, callback) {
    if (confirm(message)) {
        callback();
    }
}

// Form Validation
function validateForm(formId) {
    const form = document.getElementById(formId);
    let isValid = true;

    form.querySelectorAll('[required]').forEach(function(field) {
        if (!field.value.trim()) {
            isValid = false;
            field.classList.add('error');
            showError(field, 'This field is required');
        } else {
            field.classList.remove('error');
            hideError(field);
        }
    });

    return isValid;
}

function showError(field, message) {
    let errorEl = field.parentElement.querySelector('.form-error');
    if (!errorEl) {
        errorEl = document.createElement('div');
        errorEl.className = 'form-error';
        field.parentElement.appendChild(errorEl);
    }
    errorEl.textContent = message;
}

function hideError(field) {
    const errorEl = field.parentElement.querySelector('.form-error');
    if (errorEl) {
        errorEl.remove();
    }
}

// File Upload Preview
function handleFileSelect(input, previewId) {
    const preview = document.getElementById(previewId);
    const file = input.files[0];

    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            if (file.type.startsWith('image/')) {
                preview.innerHTML = `<img src="${e.target.result}" alt="Preview" style="max-width: 200px; max-height: 200px;">`;
            } else {
                preview.innerHTML = `<div style="padding: 1rem; background: var(--light-grey); border-radius: var(--border-radius);">
                    <i class="fas fa-file"></i> ${escapeHtml(file.name)}
                </div>`;
            }
        };
        reader.readAsDataURL(file);
    }
}

// Date Formatting
function formatDate(dateStr) {
    if (!dateStr) return '-';
    const date = new Date(dateStr);
    return date.toLocaleDateString('en-GB');
}

function formatDateTime(dateStr) {
    if (!dateStr) return '-';
    const date = new Date(dateStr);
    return date.toLocaleDateString('en-GB') + ' ' + date.toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit' });
}

// AJAX Helper
function fetchData(url, options = {}) {
    return fetch(url, {
        headers: {
            'Accept': 'application/json',
        },
        credentials: 'same-origin',
        ...options
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    });
}

// Table Sorting
function sortTable(tableId, column, order = 'asc') {
    const tbody = document.querySelector(`#${tableId} tbody`);
    const rows = Array.from(tbody.querySelectorAll('tr'));

    rows.sort((a, b) => {
        const aVal = a.querySelector(`td:nth-child(${column})`).textContent.trim();
        const bVal = b.querySelector(`td:nth-child(${column})`).textContent.trim();

        if (order === 'asc') {
            return aVal.localeCompare(bVal, undefined, { numeric: true });
        } else {
            return bVal.localeCompare(aVal, undefined, { numeric: true });
        }
    });

    rows.forEach(row => tbody.appendChild(row));
}

// Export Table to CSV
function exportTableToCSV(tableId, filename) {
    const table = document.getElementById(tableId);
    const rows = table.querySelectorAll('tr');
    let csv = [];

    rows.forEach(row => {
        const cols = row.querySelectorAll('td, th');
        let rowData = [];
        cols.forEach(col => {
            rowData.push('"' + col.textContent.trim().replace(/"/g, '""') + '"');
        });
        csv.push(rowData.join(','));
    });

    const csvContent = csv.join('\n');
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = filename + '.csv';
    link.click();
}

// Filter Table
function filterTable(tableId, input) {
    const filter = input.value.toLowerCase();
    const table = document.getElementById(tableId);
    const rows = table.querySelectorAll('tbody tr');

    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(filter) ? '' : 'none';
    });
}

// Check All Checkbox
function toggleCheckAll(checkbox, name) {
    const checkboxes = document.querySelectorAll(`input[name="${name}"]`);
    checkboxes.forEach(cb => cb.checked = checkbox.checked);
}

// Delete Confirmation
function confirmDeletion(formId) {
    return confirm('Are you sure you want to delete this item? This action cannot be undone.');
}

// Auto-hide alerts
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.5s';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 500);
        }, 5000);
    });
});

// Initialize Tooltips
function initTooltips() {
    document.querySelectorAll('[title]').forEach(el => {
        el.setAttribute('data-tooltip', el.getAttribute('title'));
        el.removeAttribute('title');
    });
}

// Print functionality
function printContent(elementId) {
    const content = document.getElementById(elementId);
    const printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <html>
        <head>
            <title>Print</title>
            <link rel="stylesheet" href="assets/css/style.css">
            <style>
                body { padding: 20px; }
                @media print {
                    .no-print { display: none !important; }
                }
            </style>
        </head>
        <body>
            ${content.innerHTML}
        </body>
        </html>
    `);
    printWindow.document.close();
    printWindow.print();
}

// Charts Color Palette
const chartColors = {
    primary: '#0f2942',
    secondary: '#c9a227',
    success: '#059669',
    warning: '#d97706',
    danger: '#dc2626',
    info: '#0284c7',
    light: '#e2e8f0'
};

// Create Doughnut Chart
function createDoughnutChart(canvasId, data, labels) {
    const ctx = document.getElementById(canvasId);
    if (!ctx) return null;

    return new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: data,
                backgroundColor: [
                    chartColors.primary,
                    chartColors.secondary,
                    chartColors.success,
                    chartColors.warning,
                    chartColors.danger,
                    chartColors.info
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
}

// Create Bar Chart
function createBarChart(canvasId, data, labels) {
    const ctx = document.getElementById(canvasId);
    if (!ctx) return null;

    return new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Count',
                data: data,
                backgroundColor: chartColors.primary
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });
}

// Create Line Chart
function createLineChart(canvasId, data, labels) {
    const ctx = document.getElementById(canvasId);
    if (!ctx) return null;

    return new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Count',
                data: data,
                borderColor: chartColors.primary,
                backgroundColor: chartColors.primary + '20',
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}

// ============================================
// AJAX Form Submission Handlers
// ============================================

/**
 * Create a lightweight request trace id for correlating browser/server logs.
 * @param {string} prefix
 * @returns {string}
 */
function createTraceId(prefix = 'req') {
    return `${prefix}-${Date.now()}-${Math.random().toString(16).slice(2, 8)}`;
}

/**
 * Structured client logger.
 * @param {'log'|'warn'|'error'} level
 * @param {string} event
 * @param {object} details
 */
function logClient(level, event, details = {}) {
    const payload = {
        time: new Date().toISOString(),
        event,
        ...details
    };
    console[level]('[CRM]', payload);
}

/**
 * Handle form submission via AJAX
 * @param {Event} e - Form submit event
 */
function handleFormSubmit(e) {
    e.preventDefault();
    
    const form = e.target;
    const url = form.getAttribute('action') || window.location.pathname;
    const traceId = createTraceId('form');
    
    logClient('log', 'form.submit.intercepted', {
        traceId,
        formId: form.id || '(no-id)',
        url,
        method: (form.method || 'POST').toUpperCase()
    });
    
    // Get form data
    const formData = new FormData(form);
    const csrfToken = getCsrfToken();
    if (!formData.get('csrf_token') && csrfToken) {
        formData.append('csrf_token', csrfToken);
    }

    logClient('log', 'form.submit.payload.ready', {
        traceId,
        action: formData.get('action') || '(none)',
        id: formData.get('id') || '(none)',
        hasCsrfToken: Boolean(formData.get('csrf_token'))
    });
    
    // Show loading state if button exists
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn ? submitBtn.textContent : null;
    if (submitBtn) submitBtn.disabled = true;
    if (submitBtn) submitBtn.textContent = 'Processing...';
    
    logClient('log', 'form.submit.request.sending', { traceId, url });
    
    // Send AJAX request
    fetch(url, {
        method: 'POST',
        credentials: 'same-origin',
        body: formData,
        headers: {
            'Accept': 'application/json',
            'X-CSRF-Token': csrfToken,
            'X-Request-ID': traceId
        }
    })
    .then(async response => {
        const contentType = response.headers.get('content-type') || '';
        logClient('log', 'form.submit.response.received', {
            traceId,
            status: response.status,
            contentType
        });
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        // If backend returns HTML, surface a clear error instead of failing silently
        if (!contentType.includes('application/json')) {
            const raw = await response.text();
            logClient('error', 'form.submit.response.non_json', {
                traceId,
                preview: raw.slice(0, 300)
            });
            throw new Error('Server returned HTML instead of JSON');
        }

        return response.json();
    })
    .then(data => {
        logClient('log', 'form.submit.response.parsed', {
            traceId,
            success: Boolean(data.success),
            message: data.message || data.error || '(none)'
        });
        // Reset button
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        }
        
        // Handle success response
        if (data.success) {
            showToast(data.message || 'Operation successful', 'success');
            
            // Reload page after short delay if requested
            if (data.reload) {
                setTimeout(() => window.location.reload(), 1500);
            } else if (data.redirect) {
                // Redirect to specified URL
                setTimeout(() => window.location.href = data.redirect, 1500);
            } else {
                // Update form or DOM if necessary
                if (data.callback && typeof window[data.callback] === 'function') {
                    window[data.callback](data);
                }
            }
        } else {
            showToast(data.error || 'Operation failed', 'error');
        }
    })
    .catch(error => {
        logClient('error', 'form.submit.error', {
            traceId,
            message: error.message,
            stack: error.stack || '(no-stack)'
        });
        
        // Reset button
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        }
        
        showToast('An error occurred. Please try again.', 'error');
    });
}

/**
 * Check whether a form should be submitted via AJAX POST.
 * Handles method casing inconsistencies safely.
 * @param {HTMLFormElement} form
 * @returns {boolean}
 */
function isAjaxPostForm(form) {
    if (!form || form.tagName !== 'FORM') return false;
    if (form.hasAttribute('data-no-ajax')) return false;
    return (form.method || '').toLowerCase() === 'post';
}

/**
 * Show toast notification
 * @param {string} message - Message to display
 * @param {string} type - Toast type: 'success', 'error', 'info', 'warning'
 * @param {number} duration - Duration in milliseconds (default: 3000)
 */
function showToast(message, type = 'info', duration = 3000) {
    const toastContainer = document.getElementById('toastContainer');
    
    // Create container if it doesn't exist
    if (!toastContainer) {
        const container = document.createElement('div');
        container.id = 'toastContainer';
        container.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 9999;';
        document.body.appendChild(container);
    }
    
    // Create toast element
    const toast = document.createElement('div');
    const bgColor = {
        'success': '#28a745',
        'error': '#dc3545',
        'info': '#17a2b8',
        'warning': '#ffc107'
    }[type] || '#17a2b8';
    
    const textColor = type === 'warning' ? '#000' : '#fff';
    
    toast.style.cssText = `
        background-color: ${bgColor};
        color: ${textColor};
        padding: 15px 20px;
        margin-bottom: 10px;
        border-radius: 4px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        animation: slideIn 0.3s ease-in-out;
        min-width: 250px;
        max-width: 400px;
    `;
    
    toast.textContent = message;
    
    const container = document.getElementById('toastContainer');
    container.appendChild(toast);
    
    // Remove after duration
    setTimeout(() => {
        toast.style.animation = 'slideOut 0.3s ease-in-out';
        setTimeout(() => toast.remove(), 300);
    }, duration);
}

/**
 * Initialize form submission handlers
 */
function initFormHandlers() {
    try {
        // Find all forms and attach AJAX handler
        const forms = Array.from(document.querySelectorAll('form'))
            .filter(form => isAjaxPostForm(form));
        
        if (forms.length === 0) {
            console.warn('No POST forms found on this page');
            return false;
        }
        
        let attachedCount = 0;
        forms.forEach(form => {
            // Don't override if form has specific handler already set or already has listener
            if (!form.hasAttribute('data-ajax-bound')) {
                form.addEventListener('submit', handleFormSubmit);
                form.setAttribute('data-ajax-bound', 'true');
                attachedCount++;
            }
        });
        
        console.log(`AJAX form handlers initialized: ${attachedCount} forms`);
        return attachedCount > 0;
    } catch (e) {
        console.error('Error initializing form handlers:', e);
        return false;
    }
}

/**
 * Get CSRF token from meta tag or form
 * @returns {string} CSRF token
 */
function getCsrfToken() {
    const metaToken = document.querySelector('meta[name="csrf-token"]');
    if (metaToken) return metaToken.getAttribute('content');
    
    const formToken = document.querySelector('input[name="csrf_token"]');
    if (formToken) return formToken.value;
    
    return '';
}

/**
 * Delete record with confirmation via AJAX
 * @param {string} action - Action name (e.g., 'delete')
 * @param {string} id - Record ID
 * @param {string} type - Record type (for confirmation message)
 */
function deleteRecord(action, id, type = 'item', sourceElement = null) {
    if (!confirm(`Are you sure you want to delete this ${type}?`)) {
        return;
    }

    if (sourceElement) {
        sourceElement.disabled = true;
    }
    
    const url = window.location.pathname;
    const traceId = createTraceId('delete');
    const formData = new FormData();
    formData.append('action', action);
    formData.append('id', id);
    const csrfToken = getCsrfToken();
    formData.append('csrf_token', csrfToken);

    logClient('log', 'delete.request.prepared', {
        traceId,
        url,
        action,
        id,
        type,
        hasCsrfToken: Boolean(csrfToken)
    });
    
    fetch(url, {
        method: 'POST',
        credentials: 'same-origin',
        body: formData,
        headers: {
            'Accept': 'application/json',
            'X-CSRF-Token': csrfToken,
            'X-Request-ID': traceId
        }
    })
    .then(async response => {
        const contentType = response.headers.get('content-type') || '';
        logClient('log', 'delete.response.received', {
            traceId,
            status: response.status,
            contentType,
            action,
            id
        });
        if (!contentType.includes('application/json')) {
            const raw = await response.text();
            logClient('error', 'delete.response.non_json', {
                traceId,
                action,
                id,
                preview: raw.slice(0, 300)
            });
            throw new Error('Server returned HTML instead of JSON for delete');
        }

        const data = await response.json();

        if (!response.ok) {
            logClient('warn', 'delete.response.http_error_json', {
                traceId,
                action,
                id,
                status: response.status,
                code: data.code || '(none)',
                message: data.error || data.message || '(none)'
            });
        }

        return data;
    })
    .then(data => {
        logClient('log', 'delete.response.parsed', {
            traceId,
            action,
            id,
            success: Boolean(data.success),
            message: data.message || data.error || '(none)',
            code: data.code || '(none)',
            responseTraceId: data.traceId || '(none)',
            affectedRows: Number(data.affectedRows || 0)
        });
        if (data.success) {
            showToast(data.message || 'Successfully deleted', 'success');

            const row = sourceElement ? sourceElement.closest('tr') : null;
            if (row) {
                row.remove();
            }

            setTimeout(() => window.location.reload(), 800);
        } else {
            const errorMessage = data.error || 'Delete failed';
            const debugCode = data.code ? ` (${data.code})` : '';
            showToast(errorMessage + debugCode, 'error');
        }
    })
    .catch(error => {
        logClient('error', 'delete.error', {
            traceId,
            action,
            id,
            message: error.message,
            stack: error.stack || '(no-stack)'
        });
        showToast('An error occurred. Please try again.', 'error');
    })
    .finally(() => {
        if (sourceElement) {
            sourceElement.disabled = false;
        }
    });
}

/**
 * Delete company by ID
 * @param {string} id - Company ID
 */
function deleteCompany(id, sourceElement = null) {
    deleteRecord('delete', id, 'company', sourceElement);
}

/**
 * Delete document by ID
 * @param {string} id - Document ID
 */
function deleteDoc(id) {
    deleteRecord('delete', id, 'document');
}

/**
 * Delete contact by ID
 * @param {string} id - Contact ID
 */
function deleteContact(id) {
    if (!confirm('Are you sure you want to delete this contact?')) {
        return;
    }
    
    const url = window.location.pathname;
    const traceId = createTraceId('delete-contact');
    const formData = new FormData();
    formData.append('action', 'delete_contact');
    formData.append('id', id);
    const csrfToken = getCsrfToken();
    formData.append('csrf_token', csrfToken);

    logClient('log', 'delete_contact.request.prepared', {
        traceId,
        url,
        id,
        hasCsrfToken: Boolean(csrfToken)
    });
    
    fetch(url, {
        method: 'POST',
        credentials: 'same-origin',
        body: formData,
        headers: {
            'Accept': 'application/json',
            'X-CSRF-Token': csrfToken,
            'X-Request-ID': traceId
        }
    })
    .then(async response => {
        const contentType = response.headers.get('content-type') || '';
        logClient('log', 'delete_contact.response.received', {
            traceId,
            status: response.status,
            contentType,
            id
        });
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        if (!contentType.includes('application/json')) {
            const raw = await response.text();
            logClient('error', 'delete_contact.response.non_json', {
                traceId,
                id,
                preview: raw.slice(0, 300)
            });
            throw new Error('Server returned HTML instead of JSON for contact delete');
        }

        return response.json();
    })
    .then(data => {
        logClient('log', 'delete_contact.response.parsed', {
            traceId,
            id,
            success: Boolean(data.success),
            message: data.message || data.error || '(none)'
        });
        if (data.success) {
            showToast(data.message || 'Successfully deleted', 'success');
            setTimeout(() => window.location.reload(), 1500);
        } else {
            showToast(data.error || 'Delete failed', 'error');
        }
    })
    .catch(error => {
        logClient('error', 'delete_contact.error', {
            traceId,
            id,
            message: error.message,
            stack: error.stack || '(no-stack)'
        });
        showToast('An error occurred. Please try again.', 'error');
    });
}

/**
 * Delete task by ID
 * @param {string} id - Task ID
 */
function deleteTask(id) {
    deleteRecord('delete', id, 'task');
}

/**
 * Delete meeting by ID
 * @param {string} id - Meeting ID
 */
function deleteMeeting(id, sourceElement = null) {
    deleteRecord('delete', id, 'meeting', sourceElement);
}

/**
 * Deactivate staff by ID
 * @param {string} id - Staff ID
 */
function deleteStaff(id, sourceElement = null) {
    deleteRecord('delete', id, 'staff account', sourceElement);
}

// Initialize form handlers on page load
document.addEventListener('DOMContentLoaded', initFormHandlers);

// Also initialize immediately in case DOM is already ready (script loaded late)
if (document.readyState === 'loading') {
    console.log('DOM still loading, will initialize on DOMContentLoaded');
} else {
    console.log('DOM already loaded, initializing form handlers immediately');
    initFormHandlers();
}

// Add MutationObserver to handle dynamically added forms
try {
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.addedNodes.length) {
                mutation.addedNodes.forEach(function(node) {
                    // Check if any added nodes are forms or contain forms
                    if (node.nodeType === 1) { // Element node
                        if (node.tagName === 'FORM' && isAjaxPostForm(node)) {
                            if (!node.hasAttribute('data-ajax-bound')) {
                                node.addEventListener('submit', handleFormSubmit);
                                node.setAttribute('data-ajax-bound', 'true');
                                console.log('AJAX handler attached to dynamically added form');
                            }
                        } else if (node.querySelectorAll) {
                            const innerForms = Array.from(node.querySelectorAll('form:not([data-ajax-bound])'))
                                .filter(form => isAjaxPostForm(form));
                            innerForms.forEach(form => {
                                form.addEventListener('submit', handleFormSubmit);
                                form.setAttribute('data-ajax-bound', 'true');
                            });
                            if (innerForms.length > 0) {
                                console.log(`AJAX handlers attached to ${innerForms.length} dynamically added forms`);
                            }
                        }
                    }
                });
            }
        });
    });

    observer.observe(document.body, {
        childList: true,
        subtree: true
    });
    
    console.log('MutationObserver enabled for dynamic form detection');
} catch (e) {
    console.warn('MutationObserver not supported, dynamic forms may not be detected:', e);
}

// Add animation styles
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from {
            transform: translateX(400px);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOut {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(400px);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);
