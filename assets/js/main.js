// =====================================================
// PERIODA - general small JS helpers used across pages
// =====================================================

// Ask for confirmation before any delete action
document.addEventListener('click', function (e) {
    const target = e.target.closest('.confirm-delete');
    if (target) {
        const msg = target.getAttribute('data-confirm') || 'Are you sure you want to delete this?';
        if (!confirm(msg)) {
            e.preventDefault();
        }
    }
});

// Auto-hide alert boxes after a few seconds
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.alert').forEach(function (box) {
        setTimeout(function () {
            box.style.transition = 'opacity .4s ease';
            box.style.opacity = '0';
            setTimeout(function () { box.style.display = 'none'; }, 400);
        }, 4000);
    });
});
