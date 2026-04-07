/**
 * FeastFlow Pro - Main JavaScript
 * Common utilities and functions
 */

// Global utility functions
window.FeastFlow = window.FeastFlow || {};

/**
 * Show loading spinner
 */
FeastFlow.showLoading = function(message) {
    const overlay = $('<div class="spinner-overlay">' +
        '<div class="text-center">' +
        '<div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">' +
        '<span class="visually-hidden">Loading...</span>' +
        '</div>' +
        '<p class="mt-3 mb-0">' + (message || 'Loading...') + '</p>' +
        '</div></div>');
    $('body').append(overlay);
};

/**
 * Hide loading spinner
 */
FeastFlow.hideLoading = function() {
    $('.spinner-overlay').remove();
};

/**
 * AJAX helper with error handling
 */
FeastFlow.ajax = function(options) {
    const defaultOptions = {
        method: 'POST',
        dataType: 'json',
        beforeSend: function() {
            if (options.showLoading !== false) {
                FeastFlow.showLoading(options.loadingMessage || 'Processing...');
            }
        },
        complete: function() {
            if (options.showLoading !== false) {
                FeastFlow.hideLoading();
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', status, error);
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: 'An error occurred. Please try again.'
            });
            if (options.onError) {
                options.onError(xhr, status, error);
            }
        }
    };
    
    const mergedOptions = $.extend({}, defaultOptions, options);
    return $.ajax(mergedOptions);
};

/**
 * Format date to readable format
 */
FeastFlow.formatDate = function(dateString, format = 'short') {
    const date = new Date(dateString);
    const options = format === 'short' ? 
        { month: 'short', day: 'numeric', year: 'numeric' } :
        { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' };
    return date.toLocaleDateString('en-US', options);
};

/**
 * Format number as currency
 */
FeastFlow.formatMoney = function(amount, currency = 'PKR') {
    return new Intl.NumberFormat('en-PK', {
        style: 'currency',
        currency: currency
    }).format(amount);
};

/**
 * Debounce function for search inputs
 */
FeastFlow.debounce = function(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
};

/**
 * Validate phone number (Pakistan format)
 */
FeastFlow.validatePhone = function(phone) {
    const pattern = /^(\+92|0)?3[0-9]{9}$/;
    return pattern.test(phone.replace(/[\s-]/g, ''));
};

/**
 * Generate unique ID
 */
FeastFlow.generateId = function() {
    return 'id_' + Math.random().toString(36).substr(2, 9);
};

/**
 * Download file from blob
 */
FeastFlow.downloadFile = function(blob, filename) {
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    window.URL.revokeObjectURL(url);
    document.body.removeChild(a);
};

/**
 * Copy text to clipboard
 */
FeastFlow.copyToClipboard = function(text) {
    navigator.clipboard.writeText(text).then(function() {
        Swal.fire({
            icon: 'success',
            title: 'Copied!',
            text: 'Text copied to clipboard',
            timer: 1500,
            showConfirmButton: false
        });
    }).catch(function(err) {
        console.error('Failed to copy:', err);
        // Fallback for older browsers
        const textarea = document.createElement('textarea');
        textarea.value = text;
        document.body.appendChild(textarea);
        textarea.select();
        document.execCommand('copy');
        document.body.removeChild(textarea);
        Swal.fire({
            icon: 'success',
            title: 'Copied!',
            text: 'Text copied to clipboard',
            timer: 1500,
            showConfirmButton: false
        });
    });
};

/**
 * Initialize DataTable (if using DataTables plugin)
 */
FeastFlow.initDataTable = function(selector, options = {}) {
    if ($.fn.DataTable) {
        const defaultOptions = {
            responsive: true,
            pageLength: 10,
            order: [[0, 'desc']],
            language: {
                search: '_INPUT_',
                searchPlaceholder: 'Search...'
            }
        };
        const mergedOptions = $.extend({}, defaultOptions, options);
        return $(selector).DataTable(mergedOptions);
    }
};

/**
 * Auto-save form data to localStorage
 */
FeastFlow.autoSaveForm = function(formSelector, storageKey) {
    const $form = $(formSelector);
    
    // Load saved data
    const savedData = localStorage.getItem(storageKey);
    if (savedData) {
        try {
            const data = JSON.parse(savedData);
            Object.keys(data).forEach(key => {
                const $field = $form.find(`[name="${key}"]`);
                if ($field.length) {
                    if ($field.attr('type') === 'checkbox') {
                        $field.prop('checked', data[key]);
                    } else {
                        $field.val(data[key]);
                    }
                }
            });
        } catch(e) {
            console.error('Error loading saved form data:', e);
        }
    }
    
    // Save on input change
    $form.on('input change', FeastFlow.debounce(function() {
        const formData = {};
        $form.serializeArray().forEach(item => {
            formData[item.name] = item.value;
        });
        $form.find('input[type="checkbox"]').each(function() {
            formData[this.name] = this.checked;
        });
        localStorage.setItem(storageKey, JSON.stringify(formData));
    }, 500));
    
    // Clear on submit
    $form.on('submit', function() {
        localStorage.removeItem(storageKey);
    });
};

/**
 * Confirm before leaving page with unsaved changes
 */
FeastFlow.confirmUnsavedChanges = function(formSelector) {
    let hasChanges = false;
    const $form = $(formSelector);
    
    $form.on('input change', function() {
        hasChanges = true;
    });
    
    $form.on('submit', function() {
        hasChanges = false;
    });
    
    window.addEventListener('beforeunload', function(e) {
        if (hasChanges) {
            e.preventDefault();
            e.returnValue = '';
            return '';
        }
    });
};

/**
 * Initialize tooltips and popovers dynamically loaded content
 */
FeastFlow.initDynamicContent = function(container) {
    // Initialize tooltips
    $(container).find('[data-bs-toggle="tooltip"]').each(function() {
        new bootstrap.Tooltip(this);
    });
    
    // Initialize popovers
    $(container).find('[data-bs-toggle="popover"]').each(function() {
        new bootstrap.Popover(this);
    });
};

/**
 * Handle image preview
 */
FeastFlow.imagePreview = function(inputSelector, previewSelector) {
    $(inputSelector).on('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                $(previewSelector).attr('src', e.target.result).show();
            };
            reader.readAsDataURL(file);
        }
    });
};

/**
 * Calculate age from date of birth
 */
FeastFlow.calculateAge = function(birthDate) {
    const today = new Date();
    const birth = new Date(birthDate);
    let age = today.getFullYear() - birth.getFullYear();
    const monthDiff = today.getMonth() - birth.getMonth();
    
    if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birth.getDate())) {
        age--;
    }
    
    return age;
};

/**
 * Get URL parameter
 */
FeastFlow.getUrlParam = function(param) {
    const urlParams = new URLSearchParams(window.location.search);
    return urlParams.get(param);
};

/**
 * Set URL parameter without reload
 */
FeastFlow.setUrlParam = function(param, value) {
    const url = new URL(window.location);
    url.searchParams.set(param, value);
    window.history.pushState({}, '', url);
};

/**
 * Export table to CSV
 */
FeastFlow.exportTableToCSV = function(tableSelector, filename = 'export.csv') {
    const rows = $(tableSelector).find('tr');
    let csv = [];
    
    rows.each(function() {
        const row = [];
        $(this).find('th, td').each(function() {
            row.push('"' + $(this).text().replace(/"/g, '""') + '"');
        });
        csv.push(row.join(','));
    });
    
    const csvContent = csv.join('\n');
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    FeastFlow.downloadFile(blob, filename);
};

// Document ready initialization
$(document).ready(function() {
    // Initialize any global event listeners or components here
    
    // Auto-dismiss alerts
    $('.alert-dismissible').each(function() {
        const alert = $(this);
        setTimeout(function() {
            alert.fadeOut('slow', function() {
                alert.alert('close');
            });
        }, 5000);
    });
});
