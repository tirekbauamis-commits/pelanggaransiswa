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

    const classSelect = document.querySelector('[data-student-filter]');
    const studentSelect = document.getElementById('siswaInput');
    const waliSelect = document.getElementById('waliInput');
    if (classSelect && studentSelect) {
        const allStudentOptions = Array.from(studentSelect.options).map(option => ({
            value: option.value,
            text: option.textContent,
            kelas: option.dataset.kelas || '',
            selected: option.selected,
        }));
        const syncStudents = () => {
            const selectedClass = classSelect.value;
            const currentValue = studentSelect.value;
            studentSelect.innerHTML = '';
            const placeholder = new Option(selectedClass ? 'Pilih siswa' : 'Pilih kelas dulu', '');
            placeholder.disabled = true;
            placeholder.selected = true;
            studentSelect.appendChild(placeholder);

            if (!selectedClass) {
                studentSelect.value = '';
            } else {
                const matchingStudents = allStudentOptions.filter(option => option.value !== '' && option.kelas === selectedClass);
                matchingStudents.forEach(option => {
                    const item = new Option(option.text, option.value);
                    item.dataset.kelas = option.kelas;
                    studentSelect.appendChild(item);
                });
                const selectedFromClass = matchingStudents.find(option => option.value === currentValue) || matchingStudents.find(option => option.selected) || matchingStudents[0];
                if (selectedFromClass) {
                    studentSelect.value = selectedFromClass.value;
                }
            }
            if (waliSelect && selectedClass) {
                const wali = Array.from(waliSelect.options).find(option => option.dataset.kelas === selectedClass);
                if (wali) {
                    waliSelect.value = wali.value;
                }
            }
        };
        classSelect.addEventListener('change', syncStudents);
        syncStudents();
    }

    const roleSelect = document.getElementById('registerRole');
    if (roleSelect) {
        const syncRoleFields = () => {
            document.querySelectorAll('[data-role-extra]').forEach(panel => {
                panel.classList.toggle('hidden', panel.dataset.roleExtra !== roleSelect.value);
                panel.querySelectorAll('input, select, textarea').forEach(field => {
                    field.disabled = panel.dataset.roleExtra !== roleSelect.value;
                });
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

    let pendingHref = '';

    const openModal = (title, message, confirmText = 'Ya, lanjutkan', href = '') => {
        if (!modal) return;
        if (modalTitle) modalTitle.textContent = title;
        if (modalMessage) modalMessage.textContent = message;
        if (modalConfirm) {
            modalConfirm.textContent = confirmText;
        }
        pendingHref = href;
        modal.classList.add('show');
        modal.setAttribute('aria-hidden', 'false');
    };

    const closeModal = () => {
        if (!modal) return;
        modal.classList.remove('show');
        modal.setAttribute('aria-hidden', 'true');
        pendingForm = null;
        pendingHref = '';
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
                return;
            }
            if (pendingHref) {
                window.location.href = pendingHref;
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
