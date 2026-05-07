document.addEventListener('DOMContentLoaded', () => {
    const resumesList = document.getElementById('resumes-list');
    const loadingResumes = document.getElementById('loading-resumes');
    const emptyState = document.getElementById('empty-state');

    async function loadResumes() {
        try {
            const response = await fetch('php_actions/cv_actions.php?action=get_my_cvs');
            const data = await response.json();

            if (!data.success) {
                console.error('Failed to load resumes:', data.message);
                return;
            }

            const cvs = data.cvs;
            loadingResumes.style.display = 'none';

            if (cvs.length === 0) {
                resumesList.style.display = 'none';
                emptyState.style.display = 'block';
                return;
            }

            resumesList.innerHTML = '';
            cvs.forEach(cv => {
                const card = createCvCard(cv);
                resumesList.appendChild(card);
            });
        } catch (error) {
            console.error('Error fetching resumes:', error);
        }
    }

    function createCvCard(cv) {
        const div = document.createElement('div');
        div.className = 'card animate-fade-in';
        div.style.display = 'flex';
        div.style.flexDirection = 'column';
        div.style.justifyContent = 'space-between';

        const statusClass = `badge--${cv.status}`;
        
        div.innerHTML = `
            <div>
                <div class="card-header" style="margin-bottom: var(--space-md);">
                    <h3 class="card-title" style="font-size: 1.1rem;">${cv.title}</h3>
                    <span class="badge ${statusClass}">${cv.status_label}</span>
                </div>
                <div style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: var(--space-lg);">
                    <i class="fas fa-clock" style="margin-right: 5px;"></i> Last updated: ${new Date(cv.updated_at).toLocaleDateString()}
                </div>
            </div>
            <div style="display: flex; gap: 8px; margin-top: auto; padding-top: var(--space-md); border-top: 1px solid var(--surface-border);">
                <a href="cv-preview.html?id=${cv.id}" class="btn btn-ghost btn-sm" title="Preview"><i class="fas fa-eye"></i></a>
                <a href="create-cv.html?id=${cv.id}" class="btn btn-ghost btn-sm" title="Edit"><i class="fas fa-pen"></i></a>
                <a href="qr-code.html?id=${cv.id}" class="btn btn-ghost btn-sm" title="QR Code"><i class="fas fa-qrcode"></i></a>
                <button class="btn btn-ghost btn-sm text-danger delete-btn" data-id="${cv.id}" title="Delete"><i class="fas fa-trash-can"></i></button>
            </div>
        `;

        const deleteBtn = div.querySelector('.delete-btn');
        deleteBtn.addEventListener('click', () => deleteCv(cv.id, cv.title));

        return div;
    }

    async function deleteCv(id, title) {
        if (!confirm(`Are you sure you want to delete "${title}"? This action cannot be undone.`)) {
            return;
        }

        try {
            const response = await fetch('php_actions/cv_actions.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'delete_cv', id: id })
            });
            const data = await response.json();

            if (data.success) {
                loadResumes(); // Reload the list
            } else {
                alert('Error: ' + data.message);
            }
        } catch (error) {
            console.error('Error deleting CV:', error);
            alert('A server error occurred while deleting the CV.');
        }
    }

    loadResumes();
});
