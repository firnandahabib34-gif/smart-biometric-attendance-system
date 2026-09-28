const form = document.querySelector('#student-filter-form');

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