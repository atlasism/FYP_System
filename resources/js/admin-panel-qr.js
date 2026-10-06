import QRCode from 'qrcode';

document.querySelectorAll('canvas[data-qr-url]').forEach((canvas) => {
    QRCode.toCanvas(canvas, canvas.dataset.qrUrl, {
        width: 240,
        margin: 2,
        errorCorrectionLevel: 'M',
        color: { dark: '#112840', light: '#ffffff' },
    });
});

document.querySelectorAll('[data-copy-link]').forEach((button) => {
    button.addEventListener('click', async () => {
        const message = button.closest('.generated-qr-card').querySelector('.panel-qr-copy-status');
        try {
            await navigator.clipboard.writeText(button.dataset.copyLink);
            message.textContent = 'Link copied.';
        } catch {
            message.textContent = 'Copy was blocked. Select and copy the link above.';
        }
    });
});

const batchList = document.getElementById('qr-batch-list');
if (batchList) {
    const projectGroups = (batchList.dataset.groups || '').split(',').filter(Boolean).map(Number);
    const savedGroups = (batchList.dataset.savedGroups || '').split(',').filter(Boolean).map(Number);
    let savedPanels = (batchList.dataset.savedPanels || '').split(',').filter(Boolean).map(Number);
    const qrCount = document.getElementById('split-qr-count');
    const totalPanels = document.getElementById('split-total-panels');
    const groupsSummary = document.getElementById('group-count-summary');
    const panelsSummary = document.getElementById('panel-count-summary');
    const details = batchList.closest('details');

    function evenParts(total, count) {
        const base = Math.floor(total / count);
        const remainder = total % count;
        return Array.from({ length: count }, (_, index) => base + (index < remainder ? 1 : 0));
    }

    function updatePreview() {
        const groupInputs = Array.from(batchList.querySelectorAll('.batch-group-count'));
        const panelInputs = Array.from(batchList.querySelectorAll('.batch-panel-count'));
        let offset = 0;
        groupInputs.forEach((input, index) => {
            const size = Math.max(0, Number(input.value || 0));
            const numbers = projectGroups.slice(offset, offset + size);
            batchList.querySelectorAll('.batch-group-list')[index].textContent = numbers.length ? `Groups ${numbers.join(', ')}` : 'No groups';
            offset += size;
        });
        const groupTotal = groupInputs.reduce((sum, input) => sum + Number(input.value || 0), 0);
        const panelTotal = panelInputs.reduce((sum, input) => sum + Number(input.value || 0), 0);
        groupsSummary.textContent = `${groupTotal} / ${projectGroups.length} groups`;
        panelsSummary.textContent = `${totalPanels.value || 0} panel members · ${panelTotal} QR assignments`;
        groupsSummary.classList.toggle('is-invalid', groupTotal !== projectGroups.length);
        panelsSummary.classList.toggle('is-invalid', panelTotal < Number(totalPanels.value || 0));
    }

    function renderBatches() {
        const count = Math.max(1, Math.min(Number(qrCount.value || 1), projectGroups.length));
        qrCount.value = String(count);
        qrCount.max = String(projectGroups.length);
        document.getElementById('qr-count-hint').textContent = `Up to ${projectGroups.length} QR batches. Each QR needs at least 2 panel members; a panel member can use more than one QR.`;
        const groupSizes = savedGroups.length === count && savedGroups.reduce((a, b) => a + b, 0) === projectGroups.length ? savedGroups : evenParts(projectGroups.length, count);
        const panelSizes = savedPanels.length === count ? savedPanels : evenParts(Number(totalPanels.value || 6), count).map((value) => Math.max(2, value));
        savedPanels = [];
        batchList.replaceChildren();

        for (let index = 0; index < count; index += 1) {
            const row = document.createElement('div');
            row.className = 'qr-batch-row';
            const identity = document.createElement('div');
            identity.className = 'qr-batch-identity';
            identity.innerHTML = `<span class="qr-batch-index">${String(index + 1).padStart(2, '0')}</span><strong>QR batch ${index + 1}</strong>`;
            row.append(identity);
            [
                { name: 'group_sizes', className: 'batch-group-count', label: 'Groups', value: groupSizes[index], min: 1, max: projectGroups.length, prefix: 'group-count-' },
                { name: 'panel_counts', className: 'batch-panel-count', label: 'Panel members', value: panelSizes[index], min: 2, max: Number(totalPanels.value || 255), prefix: 'panel-count-' },
            ].forEach((field) => {
                const wrapper = document.createElement('div');
                wrapper.className = 'qr-batch-field';
                const label = document.createElement('label');
                label.htmlFor = field.prefix + index;
                label.textContent = field.label;
                const input = document.createElement('input');
                input.type = 'number';
                input.id = field.prefix + index;
                input.className = field.className;
                input.name = `${field.name}[${index}]`;
                input.min = String(field.min);
                input.max = String(field.max);
                input.value = String(field.value);
                input.required = true;
                input.addEventListener('input', updatePreview);
                wrapper.append(label, input);
                row.append(wrapper);
            });
            const preview = document.createElement('div');
            preview.className = 'qr-batch-preview';
            preview.innerHTML = '<span class="qr-batch-preview-label">Included groups</span><div class="batch-group-list"></div>';
            row.append(preview);
            batchList.append(row);
        }
        updatePreview();
    }

    qrCount.addEventListener('change', renderBatches);
    totalPanels.addEventListener('change', () => {
        batchList.querySelectorAll('.batch-panel-count').forEach((input) => { input.max = totalPanels.value; });
        renderBatches();
    });
    details.addEventListener('toggle', () => { if (details.open && !batchList.children.length) renderBatches(); });
    details.closest('.panel-qr-generator').querySelectorAll('form').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (form.id !== 'split-qr-form') return;
            const groupTotal = Array.from(batchList.querySelectorAll('.batch-group-count')).reduce((sum, input) => sum + Number(input.value || 0), 0);
            const panelTotal = Array.from(batchList.querySelectorAll('.batch-panel-count')).reduce((sum, input) => sum + Number(input.value || 0), 0);
            if (groupTotal !== projectGroups.length || panelTotal < Number(totalPanels.value || 0)) {
                event.preventDefault();
                window.alert('Make sure group counts include every group and all panel members are assigned to at least one QR.');
            }
        });
    });
    renderBatches();
}
