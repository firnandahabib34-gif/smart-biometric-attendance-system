const form = document.querySelector('#student-filter-form');

const editModal = document.querySelector('#student-edit-modal');
const editForm = document.querySelector('#student-edit-form');
const deleteModal = document.querySelector('#student-delete-modal');
const deleteForm = document.querySelector('#student-delete-form');

const openEditModal = (button, values = null) => {
    if (!editModal || !editForm) return;

    const student = values || button.dataset;
    editForm.action = student.url || button.dataset.url;
    editForm.elements.student_id.value = student.id || button.dataset.id;
    editForm.elements.student_number.value = student.student_number || button.dataset.number || '';
    editForm.elements.name.value = student.name || button.dataset.name || '';
    editForm.elements.email.value = student.email || button.dataset.email || '';
    editForm.elements.phone.value = student.phone || button.dataset.phone || '';
    editForm.elements.class_name.value = student.class_name || button.dataset.class || '';
    editForm.elements.is_active.value = String(student.is_active ?? button.dataset.active ?? '1');
    editForm.elements.department.value = 'Teknik Informatika';
    editForm.elements.program.value = 'Informatika';
    editModal.showModal();
};

document.addEventListener('click', (event) => {
    const editButton = event.target.closest('[data-edit-student]');
    const deleteButton = event.target.closest('[data-delete-student]');
    const closeButton = event.target.closest('[data-close-modal]');

    if (editButton) {
        openEditModal(editButton);
    } else if (deleteButton && deleteModal && deleteForm) {
        deleteForm.action = deleteButton.dataset.url;
        document.querySelector('#student-delete-description').textContent = deleteButton.dataset.name;
        deleteModal.showModal();
    } else if (closeButton) {
        closeButton.closest('dialog')?.close();
    }
});

for (const modal of [editModal, deleteModal]) {
    modal?.addEventListener('click', (event) => {
        if (event.target === modal) modal.close();
    });
}

const modalState = document.querySelector('#student-modal-state');
if (modalState && editModal && editForm) {
    const state = JSON.parse(modalState.textContent);
    if (state.reopenId) {
        const button = document.querySelector(`[data-edit-student][data-id="${CSS.escape(String(state.reopenId))}"]`);
        if (button) {
            openEditModal(button, {
                ...button.dataset,
                ...state.old,
                id: state.reopenId,
                class_name: state.old.class_name || button.dataset.class,
                is_active: state.old.is_active ?? button.dataset.active,
            });
        }
    }
}

if (form) {
    const search = form.querySelector('[name="q"]');
    const department = form.querySelector('[name="department"]');
    const program = form.querySelector('[name="program"]');
    const className = form.querySelector('[name="class_name"]');
    const status = form.querySelector('[name="status"]');
    const results = document.querySelector('#student-results');
    const pagination = document.querySelector('#student-pagination');
    const count = document.querySelector('#student-count');
    let debounceTimer;
    let activeRequest;
    const programsByDepartment = JSON.parse(department.dataset.programsByDepartment);

    const updateProgramOptions = (selectedProgram = '') => {
        const availablePrograms = department.value
            ? (programsByDepartment[department.value] || [])
            : [...new Set(Object.values(programsByDepartment).flat())].sort();

        program.replaceChildren(new Option('Semua program studi', ''));
        availablePrograms.forEach((name) => program.add(new Option(name, name)));
        program.value = availablePrograms.includes(selectedProgram) ? selectedProgram : '';
    };

    updateProgramOptions(program.value);

    const updateResults = async (targetUrl = null) => {
        const url = new URL(targetUrl || form.action, window.location.origin);

        if (!targetUrl) {
            url.search = new URLSearchParams(new FormData(form)).toString();
        }

        activeRequest?.abort();
        activeRequest = new AbortController();
        results.setAttribute('aria-busy', 'true');

        try {
            const response = await fetch(url, {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                signal: activeRequest.signal,
            });

            if (!response.ok) {
                throw new Error('Pencarian mahasiswa gagal.');
            }

            const data = await response.json();
            results.innerHTML = data.rows;
            pagination.innerHTML = data.pagination;
            count.textContent = `${data.total} data mahasiswa`;
            window.history.replaceState({}, '', `${url.pathname}${url.search}`);
        } catch (error) {
            if (error.name !== 'AbortError') {
                window.location.assign(url);
            }
        } finally {
            results.removeAttribute('aria-busy');
        }
    };

    search.addEventListener('input', () => {
        window.clearTimeout(debounceTimer);
        debounceTimer = window.setTimeout(() => updateResults(), 250);
    });

    status.addEventListener('change', () => updateResults());
    department.addEventListener('change', () => {
        updateProgramOptions();
        updateResults();
    });
    program.addEventListener('change', () => updateResults());
    className.addEventListener('change', () => updateResults());
    form.addEventListener('submit', (event) => {
        event.preventDefault();
        window.clearTimeout(debounceTimer);
        updateResults();
    });

    document.addEventListener('click', (event) => {
        const pageLink = event.target.closest('#student-pagination a');
        const resetLink = event.target.closest('[data-student-reset]');

        if (pageLink) {
            event.preventDefault();
            updateResults(pageLink.href);
        } else if (resetLink) {
            event.preventDefault();
            search.value = '';
            department.value = '';
            updateProgramOptions();
            program.value = '';
            className.value = '';
            status.value = '';
            updateResults(resetLink.href);
        }
    });
}