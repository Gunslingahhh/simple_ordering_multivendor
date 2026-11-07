// General frontend JavaScript helpers
document.addEventListener('DOMContentLoaded', function () {
    // Confirmation dialogs for delete actions
    const deleteButtons = document.querySelectorAll('.btn-delete');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function (e) {
            if (!confirm('Are you sure you want to delete this item?')) {
                e.preventDefault();
            }
        });
    });
});
