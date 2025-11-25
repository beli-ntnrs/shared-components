/**
 * Notion Setup Widget Logic
 */
function initNotionSetupWidget(config) {
    const API_BASE = config.apiBase;
    const APP_NAME = config.appName;
    const VALIDATION_DELAY = 500;

    // DOM Elements
    const tokenInput = document.getElementById('tokenInput');
    const tokenName = document.getElementById('tokenName');
    const saveButton = document.getElementById('saveButton');
    const validationStatus = document.getElementById('validationStatus');
    const statusMessage = document.getElementById('statusMessage');
    const resourcesGrid = document.getElementById('resourcesGrid');
    const databasesList = document.getElementById('databasesList');
    const pagesList = document.getElementById('pagesList');
    const tokenList = document.getElementById('tokenList');
    const tokenContainer = document.getElementById('tokenContainer');
    const emptyState = document.getElementById('emptyState');

    // State
    let validationTimeout = null;
    let currentValidToken = null;
    let currentResources = null;

    // Initialize
    function init() {
        loadTokens();
        setupEventListeners();
    }

    function setupEventListeners() {
        tokenInput.addEventListener('input', debounceValidation);
        tokenName.addEventListener('input', updateSaveButtonState);
        saveButton.addEventListener('click', saveToken);
    }

    function debounceValidation() {
        clearTimeout(validationTimeout);
        const token = tokenInput.value.trim();

        if (!token) {
            hideValidation();
            currentValidToken = null;
            updateSaveButtonState();
            return;
        }

        if (token.length < 15) {
            showValidationError('Token is too short. Notion tokens are typically 30+ characters.');
            currentValidToken = null;
            updateSaveButtonState();
            return;
        }

        showValidationLoading();

        validationTimeout = setTimeout(() => {
            validateToken(token);
        }, VALIDATION_DELAY);
    }

    async function validateToken(token) {
        try {
            const response = await fetch(`${API_BASE}/validate-token`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ token })
            });

            const data = await response.json();

            if (data.success) {
                currentValidToken = token;
                currentResources = data.resources || { pages: [], databases: [] };
                showValidationSuccess(data);
                displayResources(data.resources || { pages: [], databases: [] });
                tokenName.disabled = false;
                updateSaveButtonState();
            } else {
                currentValidToken = null;
                showValidationError(data.error || 'Token validation failed');
                tokenName.disabled = true;
                updateSaveButtonState();
            }
        } catch (error) {
            console.error('Validation error:', error);
            currentValidToken = null;
            showValidationError('Failed to validate token. Please check your connection.');
            tokenName.disabled = true;
            updateSaveButtonState();
        }
    }

    function displayResources(resources) {
        databasesList.innerHTML = '';
        pagesList.innerHTML = '';

        const databases = resources.databases || [];
        const pages = resources.pages || [];

        // Display Pages Section (Left Column)
        const pageTitle = document.createElement('div');
        pageTitle.className = 'resources-column-title';
        pageTitle.innerHTML = '<i class="bi bi-file-earmark"></i> Pages';
        pagesList.appendChild(pageTitle);

        if (pages.length > 0) {
            pages.forEach(page => {
                const item = document.createElement('div');
                item.className = 'resource-item';

                // Get icon - Notion page icon can be emoji or external
                let iconHtml = '<i class="bi bi-file-text"></i>';
                if (page.icon) {
                    if (page.icon.type === 'emoji') {
                        iconHtml = `<span class="resource-icon-emoji">${page.icon.emoji}</span>`;
                    } else if (page.icon.type === 'external' && page.icon.external?.url) {
                        iconHtml = `<img class="resource-icon-image" src="${page.icon.external.url}" alt="icon" />`;
                    } else if (page.icon.type === 'file' && page.icon.file?.url) {
                        iconHtml = `<img class="resource-icon-image" src="${page.icon.file.url}" alt="icon" />`;
                    }
                }

                item.innerHTML = `
                    ${iconHtml}
                    <strong>${htmlEscape(page.title)}</strong>
                    ${page.archived ? '<span class="badge bg-secondary ms-2">Archived</span>' : ''}
                    <div class="resource-actions">
                        <button class="resource-action-btn" title="Open in Notion" data-action="open" data-url="${page.url || ''}">
                            <i class="bi bi-box-arrow-up-right"></i>
                        </button>
                        <button class="resource-action-btn" title="Copy ID" data-action="copy" data-id="${page.id}">
                            <i class="bi bi-clipboard"></i>
                        </button>
                    </div>
                `;
                item.addEventListener('click', (e) => handleResourceAction(e, page));
                pagesList.appendChild(item);
            });
        } else {
            const emptyMsg = document.createElement('div');
            emptyMsg.style.color = '#999';
            emptyMsg.style.padding = '1rem';
            emptyMsg.style.textAlign = 'center';
            emptyMsg.textContent = 'No pages found';
            pagesList.appendChild(emptyMsg);
        }

        // Display Databases Section (Right Column)
        const dbTitle = document.createElement('div');
        dbTitle.className = 'resources-column-title';
        dbTitle.innerHTML = '<i class="bi bi-database-fill"></i> Databases';
        databasesList.appendChild(dbTitle);

        if (databases.length > 0) {
            databases.forEach(db => {
                const item = document.createElement('div');
                item.className = 'resource-item database';

                // Get icon - Notion database icon can be emoji or external
                let iconHtml = '<i class="bi bi-database"></i>';
                if (db.icon) {
                    if (db.icon.type === 'emoji') {
                        iconHtml = `<span class="resource-icon-emoji">${db.icon.emoji}</span>`;
                    } else if (db.icon.type === 'external' && db.icon.external?.url) {
                        iconHtml = `<img class="resource-icon-image" src="${db.icon.external.url}" alt="icon" />`;
                    } else if (db.icon.type === 'file' && db.icon.file?.url) {
                        iconHtml = `<img class="resource-icon-image" src="${db.icon.file.url}" alt="icon" />`;
                    }
                }

                item.innerHTML = `
                    ${iconHtml}
                    <strong>${htmlEscape(db.title)}</strong>
                    ${db.archived ? '<span class="badge bg-secondary ms-2">Archived</span>' : ''}
                    <div class="resource-actions">
                        <button class="resource-action-btn" title="Open in Notion" data-action="open" data-url="${db.url || ''}">
                            <i class="bi bi-box-arrow-up-right"></i>
                        </button>
                        <button class="resource-action-btn" title="Copy ID" data-action="copy" data-id="${db.id}">
                            <i class="bi bi-clipboard"></i>
                        </button>
                    </div>
                `;
                item.addEventListener('click', (e) => handleResourceAction(e, db));
                databasesList.appendChild(item);
            });
        } else {
            const emptyMsg = document.createElement('div');
            emptyMsg.style.color = '#999';
            emptyMsg.style.padding = '1rem';
            emptyMsg.style.textAlign = 'center';
            emptyMsg.textContent = 'No databases found';
            databasesList.appendChild(emptyMsg);
        }

        if (databases.length > 0 || pages.length > 0) {
            resourcesGrid.classList.add('show');
        }
    }

    function handleResourceAction(event, resource) {
        const button = event.target.closest('.resource-action-btn');
        if (!button) return;

        event.stopPropagation();

        const action = button.getAttribute('data-action');

        if (action === 'open') {
            const url = button.getAttribute('data-url');
            if (url) {
                window.open(url, '_blank');
            }
        } else if (action === 'copy') {
            const id = button.getAttribute('data-id');
            if (id) {
                navigator.clipboard.writeText(id).then(() => {
                    showTemporaryMessage('ID copied to clipboard: ' + id, 'success');
                }).catch(() => {
                    // Fallback
                    const textarea = document.createElement('textarea');
                    textarea.value = id;
                    document.body.appendChild(textarea);
                    textarea.select();
                    document.execCommand('copy');
                    document.body.removeChild(textarea);
                    showTemporaryMessage('ID copied to clipboard: ' + id, 'success');
                });
            }
        }
    }

    function showValidationLoading() {
        validationStatus.classList.remove('success', 'error');
        validationStatus.classList.add('loading');
        statusMessage.innerHTML = '<span class="spinner"></span> Validating token...';
        resourcesGrid.classList.remove('show');
    }

    function showValidationSuccess(data) {
        validationStatus.classList.remove('error', 'loading');
        validationStatus.classList.add('success');
        const stats = data.stats || { pages: 0, databases: 0, total: 0 };
        const pageText = stats.pages === 1 ? 'page' : 'pages';
        const dbText = stats.databases === 1 ? 'database' : 'databases';
        statusMessage.innerHTML = `
            <i class="bi bi-check-circle"></i>
            <strong>Token is valid!</strong> Found <strong>${stats.total}</strong> resource(s):
            <strong>${stats.databases}</strong> ${dbText}, <strong>${stats.pages}</strong> ${pageText}
        `;
    }

    function showValidationError(error) {
        validationStatus.classList.remove('success', 'loading');
        validationStatus.classList.add('error');
        statusMessage.innerHTML = `<i class="bi bi-exclamation-circle"></i> <strong>Error:</strong> ${htmlEscape(error)}`;
        resourcesGrid.classList.remove('show');
    }

    function hideValidation() {
        validationStatus.classList.remove('success', 'error', 'loading');
        statusMessage.innerHTML = '';
        resourcesGrid.classList.remove('show');
    }

    function updateSaveButtonState() {
        const hasToken = currentValidToken !== null;
        const hasName = tokenName.value.trim().length > 0;
        saveButton.disabled = !(hasToken && hasName);
    }

    async function saveToken() {
        if (!currentValidToken) return;

        const originalText = saveButton.innerHTML;
        saveButton.disabled = true;
        saveButton.innerHTML = '<span class="spinner"></span> Saving...';

        const workspaceId = 'ws_' + Math.random().toString(36).substr(2, 9);

        try {
            const response = await fetch(`${API_BASE}/credentials`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    api_key: currentValidToken,
                    workspace_name: tokenName.value.trim(),
                    app: APP_NAME,
                    workspace_id: workspaceId
                })
            });

            const data = await response.json();

            if (data.success) {
                tokenInput.value = '';
                tokenName.value = '';
                tokenName.disabled = true;
                hideValidation();
                currentValidToken = null;
                currentResources = null;
                await loadTokens();
                showTemporaryMessage('Token saved successfully!', 'success');
            } else {
                showTemporaryMessage('Error saving token: ' + (data.error || 'Unknown error'), 'error');
            }
        } catch (error) {
            console.error('Save error:', error);
            showTemporaryMessage('Failed to save token. Please try again.', 'error');
        } finally {
            saveButton.disabled = false;
            saveButton.innerHTML = originalText;
            updateSaveButtonState();
        }
    }

    async function loadTokens() {
        try {
            const response = await fetch(`${API_BASE}/credentials?app=${encodeURIComponent(APP_NAME)}`);
            const data = await response.json();
            const tokens = data.workspaces || [];

            if (data.success && tokens.length > 0) {
                tokenContainer.innerHTML = '';
                tokens.forEach(token => {
                    const item = document.createElement('div');
                    item.className = 'token-item';
                    item.innerHTML = `
                        <div class="token-info">
                            <h6>
                                ${htmlEscape(token.workspace_name)}
                            </h6>
                            <p>
                                <i class="bi bi-calendar3"></i>
                                Added: ${new Date(token.created_at).toLocaleDateString()}
                            </p>
                            <small class="text-muted">Click to load and re-validate</small>
                        </div>
                        <div class="token-actions">
                            <button class="btn btn-danger" onclick="deleteToken('${token.workspace_id}', '${htmlEscape(token.workspace_name)}')">
                                <i class="bi bi-trash"></i> Delete
                            </button>
                        </div>
                    `;

                    const tokenInfo = item.querySelector('.token-info');
                    tokenInfo.addEventListener('click', () => {
                        loadTokenForEdit(token.workspace_id, token.workspace_name);
                    });

                    tokenContainer.appendChild(item);
                });

                tokenList.classList.add('show');
                emptyState.style.display = 'none';
            } else {
                tokenList.classList.remove('show');
                emptyState.style.display = 'block';
            }
        } catch (error) {
            console.error('Load error:', error);
        }
    }

    // Expose deleteToken to window for onclick handler
    window.deleteToken = async function (workspaceId, tokenName) {
        if (!confirm(`Delete "${tokenName}"? This cannot be undone.`)) {
            return;
        }

        try {
            const response = await fetch(`${API_BASE}/credentials/${workspaceId}?app=${encodeURIComponent(APP_NAME)}`, {
                method: 'DELETE'
            });

            const data = await response.json();

            if (data.success) {
                showTemporaryMessage('Token deleted successfully', 'success');
                await loadTokens();
            } else {
                showTemporaryMessage('Error deleting token: ' + (data.error || 'Unknown error'), 'error');
            }
        } catch (error) {
            console.error('Delete error:', error);
            showTemporaryMessage('Failed to delete token', 'error');
        }
    };

    async function loadTokenForEdit(workspaceId, tokenName) {
        try {
            const response = await fetch(`${API_BASE}/credentials/${workspaceId}?app=${encodeURIComponent(APP_NAME)}`);
            const data = await response.json();

            if (data.success && data.configuration && data.configuration.api_key) {
                tokenInput.value = data.configuration.api_key;
                tokenName.value = '';
                tokenName.disabled = true;
                debounceValidation();
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
                tokenInput.focus();
                tokenInput.style.borderColor = '#063312';
                setTimeout(() => {
                    tokenInput.style.borderColor = '';
                }, 2000);
            } else {
                showTemporaryMessage('Error loading token: ' + (data.error || 'Unknown error'), 'error');
            }
        } catch (error) {
            console.error('Load token error:', error);
            showTemporaryMessage('Failed to load token. Please try again.', 'error');
        }
    }

    function showTemporaryMessage(message, type) {
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type === 'error' ? 'danger' : 'success'} alert-dismissible fade show`;
        alertDiv.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        document.querySelector('.container-widget').insertBefore(alertDiv, document.querySelector('.widget-card'));

        setTimeout(() => {
            alertDiv.remove();
        }, 4000);
    }

    function htmlEscape(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Start!
    init();
}
