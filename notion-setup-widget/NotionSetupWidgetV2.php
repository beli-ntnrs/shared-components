<?php
/**
 * Notion Setup Widget V2 - Reusable Multi-Tenant Token Management
 *
 * Based on the working CSV importer implementation
 * Features token validation, workspace discovery, and persistent storage
 */

namespace Notioneers\Shared\Notion;

use PDO;

class NotionSetupWidgetV2 {
    private PDO $pdo;
    private NotionEncryption $encryption;
    private string $appName;
    private string $widgetId;

    public function __construct(
        PDO $pdo,
        NotionEncryption $encryption,
        string $appName,
        string $widgetId = 'notion-setup-widget'
    ) {
        $this->pdo = $pdo;
        $this->encryption = $encryption;
        $this->appName = htmlspecialchars($appName, ENT_QUOTES, 'UTF-8');
        $this->widgetId = htmlspecialchars($widgetId, ENT_QUOTES, 'UTF-8');
    }

    public function render(): string {
        return $this->renderHTML() . $this->renderJavaScript();
    }

    private function renderHTML(): string {
        $workspaces = $this->getWorkspaces();
        $workspacesJson = json_encode($workspaces);

        return <<<'HTML'
<!-- Notioneers Design System CSS -->
<link rel="stylesheet" href="/shared/components/design-system/css/theme.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">

<div id="notion-setup-widget-v2" class="notion-widget">
    <!-- Workspace Configuration Card -->
    <div class="card border-0 shadow-sm">
        <!-- Header with Notioneers Brand Color -->
        <div class="card-header bg-depth text-white border-0 py-4">
            <h5 class="mb-0 d-flex align-items-center gap-2">
                <i class="bi bi-link-45deg"></i>
                Notion Workspace Configuration
            </h5>
        </div>

        <div class="card-body p-4">
            <!-- Step 1: Add Token Section -->
            <div class="mb-5">
                <h6 class="text-depth fw-600 mb-3">
                    <i class="bi bi-key me-2"></i>Add Notion Integration Token
                </h6>

                <div class="row g-3">
                    <!-- Token Input -->
                    <div class="col-12">
                        <label for="notion-token" class="form-label fw-500">Integration Token</label>
                        <input
                            type="password"
                            class="form-control"
                            id="notion-token"
                            placeholder="ntn_... or secret_..."
                        >
                        <small class="text-stone d-block mt-2">
                            <a href="https://www.notion.so/my-integrations" target="_blank" class="text-depth">
                                Create integration token →
                            </a>
                        </small>
                    </div>

                    <!-- Validation Status -->
                    <div class="col-12">
                        <div id="token-validation-status"></div>
                    </div>

                    <!-- Workspace Name Input -->
                    <div class="col-lg-6">
                        <label for="workspace-name" class="form-label fw-500">Workspace Name</label>
                        <input
                            type="text"
                            class="form-control"
                            id="workspace-name"
                            placeholder="My Notion Workspace"
                            disabled
                        >
                        <small class="text-stone">Display name for this workspace</small>
                    </div>

                    <!-- Connect Button -->
                    <div class="col-lg-6">
                        <label class="form-label d-block">&nbsp;</label>
                        <button
                            type="button"
                            class="btn btn-dark-green w-100"
                            id="connect-btn"
                            disabled
                        >
                            <i class="bi bi-plus-circle"></i> Connect Workspace
                        </button>
                    </div>
                </div>
            </div>

            <hr class="my-4">

            <!-- Step 2: Connected Workspaces Section -->
            <div>
                <h6 class="text-depth fw-600 mb-3">
                    <i class="bi bi-bookmark me-2"></i>Connected Workspaces
                </h6>

                <div id="workspaces-list" class="workspaces-container">
                    <!-- Rendered by JavaScript -->
                </div>

                <div id="no-workspaces-message" class="alert alert-info border-0 bg-mist text-depth">
                    <i class="bi bi-info-circle me-2"></i>No workspaces connected yet
                </div>
            </div>
        </div>
    </div>
</div>

<style>
#notion-setup-widget-v2 {
    /* Uses Notioneers Design System via Design System CSS */
}

/* Workspace Container - Responsive Grid */
.workspaces-container {
    display: grid;
    gap: 1rem;
    grid-template-columns: 1fr;
}

@media (min-width: 768px) {
    .workspaces-container {
        grid-template-columns: 1fr 1fr;
    }
}

@media (min-width: 1200px) {
    .workspaces-container {
        grid-template-columns: 1fr 1fr 1fr;
    }
}

/* Workspace Card - Notioneers Brand Styling */
.workspace-card {
    border: 1px solid #AFCAAF;
    border-radius: 0.375rem;
    padding: 1.25rem;
    transition: all 0.2s ease;
    background: white;
}

.workspace-card:hover {
    box-shadow: 0 4px 12px rgba(6, 51, 18, 0.1);
    border-color: #92EF9A;
    transform: translateY(-2px);
}

.workspace-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 0.75rem;
    gap: 1rem;
}

.workspace-name {
    font-weight: 600;
    color: #063312;
    font-size: 1.1rem;
}

.workspace-actions {
    display: flex;
    gap: 0.5rem;
}

.workspace-actions button {
    padding: 0.375rem 0.75rem;
    font-size: 0.875rem;
}

.workspace-status {
    display: flex;
    gap: 0.5rem;
    margin-top: 0.75rem;
    flex-wrap: wrap;
}

.workspace-status .badge {
    font-size: 0.75rem;
    font-weight: 600;
}

/* Validation Status - Using Notioneers Colors */
.alert-status {
    padding: 1rem;
    border-radius: 0.375rem;
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    border: 1px solid;
    font-weight: 500;
}

.alert-status.success {
    background-color: rgba(146, 239, 154, 0.1);
    border-color: #92EF9A;
    color: #063312;
}

.alert-status.error {
    background-color: rgba(237, 103, 103, 0.1);
    border-color: #ED6767;
    color: #842029;
}

.alert-status.loading {
    background-color: rgba(82, 180, 217, 0.1);
    border-color: #52B4D9;
    color: #084298;
}

/* Spinner Animation */
.spinner-border-sm {
    width: 1rem;
    height: 1rem;
    border-width: 0.15em;
    margin-right: 0.5rem;
}

/* Custom Badge Styling for Notioneers Colors */
.badge.bg-bloom {
    background-color: #92EF9A !important;
    color: #063312 !important;
    font-weight: 600;
}

.badge.bg-depth {
    background-color: #063312 !important;
    color: white !important;
    font-weight: 600;
}

/* Code Style */
code {
    background-color: #F2F4F2;
    color: #063312;
    padding: 0.125rem 0.375rem;
    border-radius: 0.25rem;
    font-size: 0.875em;
}

/* Responsive Adjustments */
@media (max-width: 576px) {
    .workspace-card {
        padding: 1rem;
    }

    .workspace-header {
        flex-direction: column;
    }

    .workspace-actions {
        width: 100%;
        margin-top: 1rem;
    }

    .workspace-actions button {
        flex: 1;
    }

    .card-header {
        padding: 1.5rem 1rem !important;
    }

    .card-body {
        padding: 1.5rem !important;
    }
}

/* Ensure proper spacing for the widget */
#notion-setup-widget-v2 {
    padding: 1rem;
    margin: 1rem 0;
}

#notion-setup-widget-v2 .card {
    margin: 0;
}
</style>
HTML;
    }

    private function renderJavaScript(): string {
        $appName = $this->appName;

        $js = <<<'JAVASCRIPT'
<script>
(function() {
    const appName = "APP_NAME_PLACEHOLDER";
    const state = {
        token: '',
        validatedWorkspaceName: '',
        workspaces: []
    };

    // Initialize on DOM ready
    document.addEventListener('DOMContentLoaded', initializeWidget);

    function initializeWidget() {
        setupTokenValidation();
        setupConnectButton();
        loadWorkspaces();
    }

    function setupTokenValidation() {
        const tokenInput = document.getElementById('notion-token');
        const connectBtn = document.getElementById('connect-btn');
        const statusDiv = document.getElementById('token-validation-status');
        const workspaceNameInput = document.getElementById('workspace-name');

        tokenInput.addEventListener('input', debounce(async function() {
            const token = this.value.trim();

            if (!token || token.length < 10) {
                statusDiv.innerHTML = '';
                connectBtn.disabled = true;
                workspaceNameInput.value = '';
                return;
            }

            statusDiv.innerHTML = '<div class="alert-status loading"><i class="bi bi-hourglass-split"></i> Validating token...</div>';

            try {
                const response = await fetch('/api/notion/validate-token', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ token: token })
                });

                const result = await response.json();

                if (result.success) {
                    state.token = token;
                    state.validatedWorkspaceName = result.workspace_name || 'Notion Workspace';
                    workspaceNameInput.value = state.validatedWorkspaceName;

                    const html = '<div class="alert-status success"><i class="bi bi-check-circle"></i> Token valid! Access to ' + result.stats.total_resources + ' resources</div>';
                    statusDiv.innerHTML = html;
                    connectBtn.disabled = false;
                } else {
                    statusDiv.innerHTML = '<div class="alert-status error"><i class="bi bi-exclamation-circle"></i> ' + result.error + '</div>';
                    connectBtn.disabled = true;
                    state.token = '';
                }
            } catch (error) {
                statusDiv.innerHTML = '<div class="alert-status error"><i class="bi bi-exclamation-circle"></i> Validation failed</div>';
                connectBtn.disabled = true;
                state.token = '';
            }
        }, 1000));
    }

    function setupConnectButton() {
        const connectBtn = document.getElementById('connect-btn');
        const tokenInput = document.getElementById('notion-token');
        const workspaceNameInput = document.getElementById('workspace-name');

        connectBtn.addEventListener('click', async function() {
            if (!state.token) {
                alert('Please validate a token first');
                return;
            }

            const workspaceName = workspaceNameInput.value || state.validatedWorkspaceName;
            const workspaceId = 'ws_' + Math.random().toString(36).substr(2, 9);

            connectBtn.disabled = true;
            connectBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Connecting...';

            try {
                const response = await fetch('/api/notion/credentials', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        app: appName,
                        workspace_name: workspaceName,
                        workspace_id: workspaceId,
                        api_key: state.token
                    })
                });

                const result = await response.json();

                if (result.success) {
                    tokenInput.value = '';
                    workspaceNameInput.value = '';
                    document.getElementById('token-validation-status').innerHTML = '';
                    state.token = '';
                    loadWorkspaces();
                } else {
                    alert('Failed to connect: ' + result.error);
                }
            } catch (error) {
                alert('Connection failed: ' + error.message);
            } finally {
                connectBtn.disabled = true;
                connectBtn.innerHTML = '<i class="bi bi-plus-circle"></i> Connect Workspace';
            }
        });
    }

    async function loadWorkspaces() {
        try {
            const response = await fetch('/api/notion/credentials?app=' + appName);
            const result = await response.json();

            if (result.success && result.workspaces.length > 0) {
                state.workspaces = result.workspaces;
                renderWorkspaces(result.workspaces);
                document.getElementById('no-workspaces-message').style.display = 'none';
            } else {
                document.getElementById('workspaces-list').innerHTML = '';
                document.getElementById('no-workspaces-message').style.display = 'block';
            }
        } catch (error) {
            console.error('Failed to load workspaces:', error);
        }
    }

    function renderWorkspaces(workspaces) {
        const container = document.getElementById('workspaces-list');
        const html = workspaces.map(function(ws) {
            const name = escapeHtml(ws.workspace_name);
            const id = ws.workspace_id;
            const date = ws.created_at ? formatDate(ws.created_at) : '';
            const dateBadge = date ? '<span class="badge bg-depth ms-2">' + date + '</span>' : '';
            return '<div class="workspace-card">' +
                '<div class="workspace-header">' +
                '<div class="flex-grow-1">' +
                '<div class="workspace-name">' + name + '</div>' +
                '<small class="text-stone d-block mt-1">ID: <code>' + escapeHtml(id) + '</code></small>' +
                '</div>' +
                '<div class="workspace-actions">' +
                '<button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteWorkspace(\'' + escapeHtml(id) + '\')" title="Remove workspace">' +
                '<i class="bi bi-trash"></i>' +
                '</button>' +
                '</div>' +
                '</div>' +
                '<div class="workspace-status">' +
                '<span class="badge bg-bloom text-dark">Connected</span>' +
                dateBadge +
                '</div>' +
                '</div>';
        }).join('');
        container.innerHTML = html;
    }

    async function deleteWorkspace(workspaceId) {
        if (!confirm('Remove this workspace?')) return;

        try {
            const response = await fetch('/api/notion/credentials/' + workspaceId + '?app=' + appName, {
                method: 'DELETE'
            });

            const result = await response.json();
            if (result.success) {
                loadWorkspaces();
            } else {
                alert('Failed to remove workspace');
            }
        } catch (error) {
            alert('Error: ' + error.message);
        }
    }

    // Utility functions
    function debounce(func, delay) {
        let timeoutId;
        return function() {
            var args = arguments;
            var self = this;
            clearTimeout(timeoutId);
            timeoutId = setTimeout(function() { func.apply(self, args); }, delay);
        };
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function formatDate(dateStr) {
        try {
            return new Date(dateStr).toLocaleDateString();
        } catch (e) {
            return dateStr;
        }
    }

    // Expose for onclick handlers
    window.deleteWorkspace = deleteWorkspace;
})();
</script>
JAVASCRIPT;
        // Replace placeholder with actual app name
        return str_replace('APP_NAME_PLACEHOLDER', $appName, $js);
    }

    private function getWorkspaces(): array {
        $query = "SELECT * FROM notion_credentials WHERE app_name = ? AND is_active = 1 ORDER BY created_at DESC";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([$this->appName]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
