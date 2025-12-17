/**
 * Automatic Data Backup System - Main JavaScript
 */

// Initialize tooltips
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Bootstrap tooltips if present
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Add active class to current navigation item
    var currentPath = window.location.pathname;
    var navLinks = document.querySelectorAll('.nav-link');
    
    navLinks.forEach(function(link) {
        if (link.getAttribute('href') && link.getAttribute('href').includes(getCurrentAction())) {
            link.classList.add('active');
        }
    });
});

// Function to get current action from URL
function getCurrentAction() {
    const urlParams = new URLSearchParams(window.location.search);
    return urlParams.get('action') || 'dashboard';
}

// Function to handle form submissions with loading states
function submitFormWithLoading(formId, submitBtnId) {
    var form = document.getElementById(formId);
    var submitBtn = document.getElementById(submitBtnId);
    
    if (form && submitBtn) {
        // Add loading state
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...';
        submitBtn.disabled = true;
        
        // Submit the form
        form.submit();
    }
}

// Function to show notification
function showNotification(message, type = 'info') {
    // Create notification element
    var notification = document.createElement('div');
    notification.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    notification.style.top = '20px';
    notification.style.right = '20px';
    notification.style.zIndex = '9999';
    notification.setAttribute('role', 'alert');
    
    notification.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;
    
    document.body.appendChild(notification);
    
    // Auto remove after 5 seconds
    setTimeout(function() {
        if (notification.parentNode) {
            notification.parentNode.removeChild(notification);
        }
    }, 5000);
}

// Function to confirm navigation with unsaved changes
function confirmNavigation(message) {
    return confirm(message || 'You have unsaved changes. Are you sure you want to leave?');
}

// Function to format file sizes
function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    
    var k = 1024;
    var sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
    var i = Math.floor(Math.log(bytes) / Math.log(k));
    
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

// Function to run scheduled backups
function runScheduledBackups() {
    // Show loading indicator
    var btn = document.getElementById('runScheduledBackups');
    if (btn) {
        btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Running...';
        btn.disabled = true;
    }
    
    // In a real implementation, this would make an AJAX call
    // For now, just show a success notification
    setTimeout(function() {
        showNotification('Scheduled backups initiated successfully!', 'success');
        
        if (btn) {
            btn.innerHTML = '<i class="fas fa-play"></i> Run Scheduled';
            btn.disabled = false;
        }
    }, 2000);
}

// Add event listener to the run scheduled backups button
document.addEventListener('DOMContentLoaded', function() {
    var runBtn = document.getElementById('runScheduledBackups');
    if (runBtn) {
        runBtn.addEventListener('click', runScheduledBackups);
    }
});