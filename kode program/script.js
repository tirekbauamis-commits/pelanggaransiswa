function confirmDelete() {
    return confirm('Yakin ingin menghapus data ini?');
}

function updatePoin() {
    const select = document.getElementById('jenisPelanggaran');
    const poin = document.getElementById('poinOtomatis');
    if (!select || !poin) return;
    const selected = select.options[select.selectedIndex];
    poin.value = selected.dataset.poin || '0';
}

document.addEventListener('DOMContentLoaded', () => {
    const select = document.getElementById('jenisPelanggaran');
    if (select) {
        select.addEventListener('change', updatePoin);
        updatePoin();
    }

    const roleSelect = document.getElementById('registerRole');
    if (roleSelect) {
        const syncRoleFields = () => {
            document.querySelectorAll('[data-role-extra]').forEach(panel => {
                panel.classList.toggle('hidden', panel.dataset.roleExtra !== roleSelect.value);
            });
        };
        roleSelect.addEventListener('change', syncRoleFields);
        syncRoleFields();
    }

    const modal = document.getElementById('confirmModal');
    const logout = document.querySelector('.js-logout');
    const cancel = document.querySelector('[data-modal-cancel]');
    const modalTitle = document.querySelector('[data-modal-title]');
    const modalMessage = document.querySelector('[data-modal-message]');
    const modalConfirm = document.querySelector('[data-modal-confirm]');
    let pendingForm = null;

    const openModal = (title, message, confirmText = 'Ya, lanjutkan', href = '#') => {
        if (!modal) return;
        if (modalTitle) modalTitle.textContent = title;
        if (modalMessage) modalMessage.textContent = message;
        if (modalConfirm) {
            modalConfirm.textContent = confirmText;
            modalConfirm.setAttribute('href', href);
        }
        modal.classList.add('show');
        modal.setAttribute('aria-hidden', 'false');
    };

    const closeModal = () => {
        if (!modal) return;
        modal.classList.remove('show');
        modal.setAttribute('aria-hidden', 'true');
        pendingForm = null;
    };

    if (modal && logout) {
        logout.addEventListener('click', event => {
            event.preventDefault();
            pendingForm = null;
            openModal('Keluar dari sistem?', 'Sesi kamu akan ditutup dari perangkat ini.', 'Logout', logout.getAttribute('href'));
        });
    }
    if (modal && cancel) {
        cancel.addEventListener('click', closeModal);
    }
    document.querySelectorAll('form[data-confirm-title]').forEach(form => {
        form.addEventListener('submit', event => {
            event.preventDefault();
            pendingForm = form;
            openModal(
                form.dataset.confirmTitle || 'Konfirmasi tindakan',
                form.dataset.confirmMessage || 'Apakah kamu yakin ingin melanjutkan?',
                'Ya, hapus'
            );
        });
    });
    if (modalConfirm) {
        modalConfirm.addEventListener('click', event => {
            if (pendingForm) {
                event.preventDefault();
                const form = pendingForm;
                pendingForm = null;
                form.submit();
            }
        });
    }
    if (modal) {
        modal.addEventListener('click', event => {
            if (event.target === modal) {
                closeModal();
            }
        });
    }
});
