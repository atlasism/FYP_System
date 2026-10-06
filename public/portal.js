document.addEventListener('DOMContentLoaded', () => {
    const toggle = document.querySelector('.sidebar-toggle');
    const sidebar = document.querySelector('.portal-sidebar');

    if (!toggle || !sidebar) return;

    const isMobile = () => window.matchMedia('(max-width: 760px)').matches;
    const setExpanded = (expanded) => toggle.setAttribute('aria-expanded', String(expanded));
    setExpanded(!isMobile() && !sidebar.classList.contains('collapsed'));

    toggle.addEventListener('click', () => {
        if (isMobile()) {
            const open = sidebar.classList.toggle('open');
            setExpanded(open);
            return;
        }

        const collapsed = sidebar.classList.toggle('collapsed');
        setExpanded(!collapsed);
    });

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') return;

        if (isMobile()) {
            sidebar.classList.remove('open');
            setExpanded(false);
        } else if (sidebar.classList.contains('collapsed')) {
            sidebar.classList.remove('collapsed');
            setExpanded(true);
        }
    });

    document.addEventListener('click', (event) => {
        if (isMobile() && sidebar.classList.contains('open')
            && !sidebar.contains(event.target) && !toggle.contains(event.target)) {
            sidebar.classList.remove('open');
            setExpanded(false);
        }
    });

    window.addEventListener('resize', () => {
        if (!isMobile()) {
            sidebar.classList.remove('open');
            setExpanded(!sidebar.classList.contains('collapsed'));
        } else {
            sidebar.classList.remove('collapsed');
            setExpanded(sidebar.classList.contains('open'));
        }
    });
});

document.addEventListener('DOMContentLoaded', () => {
    const modal = document.querySelector('.supervisor-project-modal');
    if (!modal) return;

    const fields = {
        title: modal.querySelector('[data-modal-title]'),
        description: modal.querySelector('[data-modal-description]'),
        category: modal.querySelector('[data-modal-category]'),
        session: modal.querySelector('[data-modal-session]'),
        supervisor: modal.querySelector('[data-modal-supervisor]'),
    };

    document.querySelectorAll('[data-project-details]').forEach((button) => {
        button.addEventListener('click', () => {
            fields.title.textContent = button.dataset.title || 'Project Details';
            fields.description.textContent = button.dataset.description || 'No project description available.';
            fields.category.textContent = button.dataset.category || '–';
            fields.session.textContent = button.dataset.session || '–';
            fields.supervisor.textContent = button.dataset.supervisor || '–';
            modal.showModal();
        });
    });

    modal.querySelector('[data-close-project-modal]').addEventListener('click', () => modal.close());
    modal.addEventListener('click', (event) => {
        if (event.target === modal) modal.close();
    });
});

document.addEventListener('DOMContentLoaded', () => {
    const modal = document.querySelector('.supervisor-date-modal');
    if (!modal) return;

    const form = modal.querySelector('[data-deadline-form]');
    const dateInput = modal.querySelector('[name="due_date"]');
    const deadlineName = modal.querySelector('[data-deadline-name]');

    document.querySelectorAll('[data-set-deadline]').forEach((button) => {
        button.addEventListener('click', () => {
            form.action = button.dataset.actionTemplate.replace('__deadline__', encodeURIComponent(button.dataset.deadlineId));
            deadlineName.textContent = button.dataset.deadlineTitle || 'Submission deadline';
            dateInput.value = button.dataset.deadlineDate || '';
            modal.showModal();
        });
    });

    modal.querySelectorAll('[data-close-date-modal]').forEach((button) => {
        button.addEventListener('click', () => modal.close());
    });
    modal.addEventListener('click', (event) => {
        if (event.target === modal) modal.close();
    });
});
