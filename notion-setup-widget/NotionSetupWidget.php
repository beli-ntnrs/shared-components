<?php

namespace Notioneers\Shared\Notion;

class NotionSetupWidget
{
    private $config;

    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'api_base' => '/api/notion',
            'app_name' => 'test-app',
        ], $config);
    }

    public function render()
    {
        ob_start();
        ?>
        <style>
            .notion-widget-wrapper {
                font-family: 'TWK Lausanne', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
                color: #063312;
                /* text-depth */
            }

            .notion-widget-wrapper * {
                box-sizing: border-box;
            }

            .container-widget {
                max-width: 1200px;
                margin: 0 auto;
            }

            .header {
                text-align: center;
                color: #063312;
                /* text-depth */
                margin-bottom: 2rem;
            }

            .header h1 {
                font-size: 2.5rem;
                margin-bottom: 0.5rem;
                font-weight: 700;
            }

            .header p {
                font-size: 1.1rem;
                opacity: 0.8;
                color: #666;
                /* text-stone */
            }

            .widget-card {
                background: white;
                border-radius: 12px;
                padding: 2rem;
                box-shadow: 0 4px 20px rgba(6, 51, 18, 0.08);
                margin-bottom: 2rem;
                border: 1px solid rgba(6, 51, 18, 0.1);
            }

            .section-title {
                font-size: 1.3rem;
                font-weight: 600;
                color: #063312;
                /* text-depth */
                margin-bottom: 1.5rem;
                padding-bottom: 1rem;
                border-bottom: 2px solid #F2F4F2;
                /* bg-mist */
            }

            .form-group {
                margin-bottom: 1.5rem;
            }

            .form-label {
                font-weight: 600;
                color: #063312;
                /* text-depth */
                margin-bottom: 0.5rem;
                display: block;
            }

            .form-control {
                padding: 0.75rem;
                border: 1px solid #AFCAAF;
                /* border-success-subtle approx */
                border-radius: 6px;
                font-size: 1rem;
                transition: all 0.2s;
                width: 100%;
            }

            .form-control:focus {
                border-color: #063312;
                /* text-depth */
                box-shadow: 0 0 0 3px rgba(146, 239, 154, 0.25);
                /* bg-bloom with opacity */
                outline: none;
            }

            .btn-primary {
                background: #063312;
                /* bg-depth */
                border: none;
                padding: 0.75rem 1.5rem;
                border-radius: 6px;
                font-weight: 600;
                cursor: pointer;
                transition: all 0.2s;
                color: white;
            }

            .btn-primary:hover {
                background: #0a4f1c;
                transform: translateY(-2px);
                box-shadow: 0 4px 12px rgba(6, 51, 18, 0.2);
            }

            .btn-primary:disabled {
                opacity: 0.5;
                cursor: not-allowed;
                transform: none;
                box-shadow: none;
            }

            .btn-danger {
                background: #dc3545;
                border: none;
                padding: 0.5rem 1rem;
                border-radius: 4px;
                font-weight: 600;
                cursor: pointer;
                color: white;
                font-size: 0.9rem;
            }

            .btn-danger:hover {
                background: #c82333;
            }

            .validation-status {
                display: none;
                padding: 1rem;
                border-radius: 6px;
                margin-bottom: 1.5rem;
                font-weight: 500;
            }

            .validation-status.success {
                display: block;
                background: rgba(146, 239, 154, 0.1);
                /* bg-bloom 10% */
                color: #063312;
                /* text-depth */
                border: 1px solid #92EF9A;
                /* bg-bloom */
            }

            .validation-status.error {
                display: block;
                background: rgba(237, 103, 103, 0.1);
                color: #842029;
                border: 1px solid #ED6767;
            }

            .validation-status.loading {
                display: block;
                background: rgba(82, 180, 217, 0.1);
                color: #084298;
                border: 1px solid #52B4D9;
            }

            .spinner-border {
                width: 1rem;
                height: 1rem;
                margin-right: 0.5rem;
            }

            .resources-grid {
                display: none;
                margin: 1.5rem 0;
                background: #F2F4F2;
                /* bg-mist */
                padding: 1.5rem;
                border-radius: 8px;
                border: 1px solid #E5E5E5;
                grid-template-columns: 1fr 1fr;
                gap: 2rem;
            }

            .resources-grid.show {
                display: grid !important;
            }

            .resources-column {
                display: flex;
                flex-direction: column;
            }

            .resources-column-title {
                font-weight: 600;
                font-size: 1.1rem;
                color: #063312;
                /* text-depth */
                margin-bottom: 1rem;
                display: flex;
                align-items: center;
                gap: 0.5rem;
            }

            .resources-column-title i {
                font-size: 1.3rem;
            }

            @media (max-width: 768px) {
                .resources-grid {
                    grid-template-columns: 1fr;
                    gap: 1rem;
                }
            }

            .resource-item {
                background: white;
                padding: 0.875rem 1rem;
                border-radius: 6px;
                margin-bottom: 0.75rem;
                border-left: 3px solid #063312;
                /* bg-depth */
                display: flex;
                align-items: center;
                transition: all 0.2s;
                border: 1px solid #e8e8e8;
                border-left-width: 3px;
                position: relative;
            }

            .resource-item:hover {
                border-left-color: #92EF9A;
                /* bg-bloom */
                transform: translateY(-1px);
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            }

            .resource-actions {
                display: none;
                gap: 0.5rem;
                margin-left: auto;
            }

            .resource-item:hover .resource-actions {
                display: flex;
            }

            .resource-action-btn {
                background: none;
                border: none;
                padding: 0.4rem;
                cursor: pointer;
                color: #667eea;
                font-size: 1rem;
                display: flex;
                align-items: center;
                justify-content: center;
                border-radius: 4px;
                transition: all 0.2s;
            }

            .resource-action-btn:hover {
                background: #f0f0f0;
                color: #5568d3;
            }

            .resource-item.database .resource-action-btn {
                color: #764ba2;
            }

            .resource-item.database .resource-action-btn:hover {
                color: #6b3a9a;
                background: #f8f7fa;
            }

            .resource-item i,
            .resource-item .resource-icon-emoji,
            .resource-item .resource-icon-image {
                margin-right: 0.75rem;
                font-size: 1.1rem;
                min-width: 1.1rem;
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .resource-item i {
                color: #667eea;
            }

            .resource-item .resource-icon-emoji {
                font-size: 1.2rem;
                line-height: 1;
            }

            .resource-item .resource-icon-image {
                width: 1.1rem;
                height: 1.1rem;
                object-fit: contain;
                border-radius: 2px;
            }

            .resource-item strong {
                flex: 1;
                color: #333;
            }

            .resource-item.database {
                border-left-color: #764ba2;
            }

            .resource-item.database:hover {
                border-left-color: #6b3a9a;
            }

            .resource-item.database i {
                color: #764ba2;
            }

            .token-list {
                display: none;
            }

            .token-list.show {
                display: block;
            }

            .token-item {
                background: #f8f9fa;
                padding: 1rem;
                border-radius: 6px;
                margin-bottom: 0.75rem;
                display: flex;
                justify-content: space-between;
                align-items: center;
                border-left: 4px solid #28a745;
                cursor: pointer;
                transition: all 0.2s;
                border: 1px solid #e8e8e8;
            }

            .token-item:hover {
                background: white;
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
                border-left-color: #20c997;
            }

            .token-item.inactive {
                border-left-color: #6c757d;
                opacity: 0.7;
                cursor: not-allowed;
            }

            .token-item.inactive:hover {
                background: #f8f9fa;
                box-shadow: none;
            }

            .token-info {
                flex: 1;
                cursor: pointer;
            }

            .token-info h6 {
                margin: 0 0 0.25rem 0;
                font-weight: 600;
                color: #333;
            }

            .token-info p {
                margin: 0;
                font-size: 0.9rem;
                color: #666;
            }

            .token-actions {
                display: flex;
                gap: 0.5rem;
            }

            .empty-state {
                text-align: center;
                padding: 2rem;
                color: #666;
            }

            .empty-state i {
                font-size: 3rem;
                color: #ddd;
                margin-bottom: 1rem;
            }

            .status-badge {
                display: inline-block;
                padding: 0.25rem 0.75rem;
                border-radius: 12px;
                font-size: 0.85rem;
                font-weight: 600;
            }

            .status-badge.active {
                background: #d4edda;
                color: #155724;
            }

            .status-badge.inactive {
                background: #e2e3e5;
                color: #383d41;
            }

            .spinner {
                display: inline-block;
                width: 0.75rem;
                height: 0.75rem;
                border: 2px solid currentColor;
                border-right-color: transparent;
                border-radius: 50%;
                animation: spinner-rotate 0.6s linear infinite;
            }

            @keyframes spinner-rotate {
                to {
                    transform: rotate(360deg);
                }
            }
        </style>

        <div class="notion-widget-wrapper">
            <div class="container-widget">
                <!-- Header -->
                <div class="header">
                    <h1>🔗 Notion Token Setup</h1>
                    <p>Add and manage your Notion integration tokens</p>
                </div>

                <!-- Main Widget -->
                <div class="widget-card">
                    <div class="section-title">
                        <i class="bi bi-key"></i> Add New Token
                    </div>

                    <!-- Token Input Form -->
                    <div class="form-group">
                        <label class="form-label" for="tokenInput">Notion Integration Token</label>
                        <input type="text" id="tokenInput" class="form-control"
                            placeholder="Paste your Notion integration token (starts with ntn_ or secret_)" autofocus />
                        <small class="form-text text-muted">
                            Get your token from <a href="https://www.notion.so/my-integrations" target="_blank">My
                                Integrations</a>
                        </small>
                    </div>

                    <!-- Validation Status -->
                    <div id="validationStatus" class="validation-status">
                        <span id="statusMessage"></span>
                    </div>

                    <!-- Resources Display -->
                    <div id="resourcesGrid" class="resources-grid">
                        <div class="resources-column">
                            <div id="pagesList"></div>
                        </div>
                        <div class="resources-column">
                            <div id="databasesList"></div>
                        </div>
                    </div>

                    <!-- Token Name Input -->
                    <div class="form-group">
                        <label class="form-label" for="tokenName">Give this token a name</label>
                        <input type="text" id="tokenName" class="form-control" placeholder="e.g., My Project, Work Space, etc."
                            disabled />
                        <small class="form-text text-muted">Name something meaningful to identify this token</small>
                    </div>

                    <!-- Save Button -->
                    <div class="form-group">
                        <button id="saveButton" class="btn btn-primary w-100" disabled>
                            <i class="bi bi-floppy"></i> Save Token
                        </button>
                    </div>
                </div>

                <!-- Saved Tokens Section -->
                <div class="widget-card">
                    <div class="section-title">
                        <i class="bi bi-bookmark"></i> Saved Tokens
                    </div>

                    <div id="tokenList" class="token-list">
                        <div id="tokenContainer"></div>
                    </div>

                    <div id="emptyState" class="empty-state">
                        <i class="bi bi-inbox"></i>
                        <p>No tokens saved yet</p>
                    </div>
                </div>

                <!-- Info Box -->
                <div class="widget-card" style="background: #f8f9fa; border-left: 4px solid #063312;">
                    <strong>How it works:</strong>
                    <ol style="margin-bottom: 0; padding-left: 1.5rem;">
                        <li><strong>Paste your token</strong> - Get it from Notion's integration settings</li>
                        <li><strong>Wait for validation</strong> - We'll check if it's valid and load your databases/pages</li>
                        <li><strong>Name your token</strong> - Give it a memorable name</li>
                        <li><strong>Save it</strong> - Click Save Token to store it securely</li>
                        <li><strong>Use it</strong> - Your token is now available in your apps</li>
                    </ol>
                </div>
            </div>
        </div>

        <script>
            (function () {
                // Configuration
                const API_BASE = '<?php echo $this->config['api_base']; ?>';
                const APP_NAME = '<?php echo $this->config['app_name']; ?>';
                const VALIDATION_DELAY = 1000;

                // Elements
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

                async function handleSaveToken() {
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
            })();
        </script>
        <?php
        return ob_get_clean();
    }
}
