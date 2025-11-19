<?php
/**
 * NotionSetupWidget - Reusable component for managing Notion workspace tokens and configuration
 *
 * Provides UI and API for:
 * - Adding/updating Notion workspace tokens
 * - Selecting target databases and pages
 * - Storing workspace-specific configuration
 * - Managing multiple workspaces per app
 */

namespace Notioneers\Shared\Notion;

use PDO;

class NotionSetupWidget {
    private NotionDatabaseHelper $dbHelper;
    private NotionEncryption $encryption;
    private NotionServiceFactory $serviceFactory;
    private string $appName;
    private string $widgetId;

    public function __construct(
        PDO $pdo,
        NotionEncryption $encryption,
        string $appName,
        string $widgetId = 'notion-setup-widget'
    ) {
        $this->dbHelper = new NotionDatabaseHelper($pdo, $encryption);
        $this->encryption = $encryption;
        $this->serviceFactory = new NotionServiceFactory($pdo);
        $this->appName = $appName;
        $this->widgetId = $widgetId;

        // Ensure database is initialized
        $this->dbHelper->initializeDatabase();
    }

    /**
     * Render the setup widget HTML and JavaScript
     *
     * @return string HTML and JS for the widget
     */
    public function render(): string {
        return $this->renderHTML() . $this->renderJavaScript();
    }

    /**
     * Render the HTML structure for the widget
     *
     * @return string HTML markup
     */
    private function renderHTML(): string {
        $workspaces = $this->getWorkspaces();
        $workspacesJson = json_encode($workspaces);

        return <<<HTML
        <div id="$this->widgetId" class="notion-setup-widget">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-link-45deg"></i> Notion Workspace Configuration
                    </h5>
                </div>
                <div class="card-body">
                    <!-- Add New Workspace Section -->
                    <div class="mb-4">
                        <h6 class="text-secondary">Add New Workspace</h6>
                        <form id="add-workspace-form" class="row g-3">
                            <div class="col-md-6">
                                <label for="workspace-name" class="form-label">Workspace Name</label>
                                <input
                                    type="text"
                                    class="form-control"
                                    id="workspace-name"
                                    placeholder="e.g., Marketing Team"
                                    required
                                >
                                <small class="text-muted">Display name for this workspace</small>
                            </div>
                            <div class="col-md-6">
                                <label for="api-key" class="form-label">Notion API Key</label>
                                <input
                                    type="password"
                                    class="form-control"
                                    id="api-key"
                                    placeholder="secret_..."
                                    required
                                >
                                <small class="text-muted">From notion.so/my-integrations</small>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-plus-circle"></i> Connect Workspace
                                </button>
                                <span id="add-workspace-status" class="ms-2"></span>
                            </div>
                        </form>
                    </div>

                    <hr>

                    <!-- Workspaces List Section -->
                    <div class="mb-4">
                        <h6 class="text-secondary mb-3">Connected Workspaces</h6>
                        <div id="workspaces-list" class="workspaces-container">
                            <!-- Workspaces will be rendered here by JavaScript -->
                        </div>
                        <div id="no-workspaces" class="alert alert-info">
                            <i class="bi bi-info-circle"></i> No workspaces connected yet
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Database/Page Picker Modal -->
        <div class="modal fade" id="configModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Configure Workspace</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div id="config-status"></div>
                        <div id="databases-list" class="mb-3">
                            <!-- Databases will be loaded here -->
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="button" id="save-config-btn" class="btn btn-primary">Save Configuration</button>
                    </div>
                </div>
            </div>
        </div>

        <style>
        #$this->widgetId {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        }

        .notion-setup-widget .card {
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .workspaces-container {
            display: grid;
            gap: 1rem;
        }

        .workspace-card {
            border: 1px solid #dee2e6;
            border-radius: 0.5rem;
            padding: 1rem;
            background: #f8f9fa;
        }

        .workspace-card.active {
            border-color: #0d6efd;
            background: #e7f1ff;
        }

        .workspace-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }

        .workspace-name {
            font-weight: 600;
            color: #212529;
        }

        .workspace-actions {
            display: flex;
            gap: 0.5rem;
        }

        .workspace-actions button {
            padding: 0.25rem 0.75rem;
            font-size: 0.875rem;
        }

        .database-selector {
            margin-top: 1rem;
        }

        .alert-status {
            padding: 0.75rem 1rem;
            border-radius: 0.375rem;
            margin-bottom: 1rem;
        }

        .alert-status.success {
            background-color: #d1e7dd;
            color: #0f5132;
            border: 1px solid #badbcc;
        }

        .alert-status.error {
            background-color: #f8d7da;
            color: #842029;
            border: 1px solid #f5c2c7;
        }

        .alert-status.loading {
            background-color: #cfe2ff;
            color: #084298;
            border: 1px solid #b6d4fe;
        }
        </style>

        <script type="application/json" id="workspaces-data">
            $workspacesJson
        </script>
        HTML;
    }

    /**
     * Render the JavaScript for the widget
     *
     * @return string JavaScript code
     */
    private function renderJavaScript(): string {
        $appName = htmlspecialchars($this->appName, ENT_QUOTES, 'UTF-8');
        $widgetId = htmlspecialchars($this->widgetId, ENT_QUOTES, 'UTF-8');

        return <<<JS
        <script>
        (function() {
            const appName = '$appName';
            const widgetId = '$widgetId';
            const config = {
                addWorkspaceForm: '#add-workspace-form',
                apiKeyInput: '#api-key',
                workspaceNameInput: '#workspace-name',
                workspacesList: '#workspaces-list',
                noWorkspaces: '#no-workspaces',
                configModal: '#configModal',
                statusElement: '#add-workspace-status',
                configStatus: '#config-status',
                databasesList: '#databases-list',
                saveConfigBtn: '#save-config-btn',
            };

            // Initialize
            loadWorkspaces();
            setupEventListeners();

            function setupEventListeners() {
                // Add workspace form
                document.querySelector(config.addWorkspaceForm).addEventListener('submit', handleAddWorkspace);

                // Save config button
                document.querySelector(config.saveConfigBtn).addEventListener('click', handleSaveConfig);
            }

            async function loadWorkspaces() {
                try {
                    const response = await fetch(\`/api/notion/credentials?app=\${appName}\`);
                    const data = await response.json();

                    if (data.success && data.workspaces.length > 0) {
                        renderWorkspaces(data.workspaces);
                        document.querySelector(config.noWorkspaces).style.display = 'none';
                    } else {
                        document.querySelector(config.workspacesList).innerHTML = '';
                        document.querySelector(config.noWorkspaces).style.display = 'block';
                    }
                } catch (error) {
                    console.error('Failed to load workspaces:', error);
                    showStatus('error', 'Failed to load workspaces');
                }
            }

            function renderWorkspaces(workspaces) {
                const container = document.querySelector(config.workspacesList);
                container.innerHTML = workspaces.map(ws => \`
                    <div class="workspace-card" data-workspace-id="\${ws.workspace_id}">
                        <div class="workspace-header">
                            <div>
                                <div class="workspace-name">\${escapeHtml(ws.workspace_name || 'Unnamed')}</div>
                                <small class="text-muted">ID: \${ws.workspace_id}</small>
                            </div>
                            <div class="workspace-actions">
                                <button type="button" class="btn btn-sm btn-outline-primary config-btn" data-workspace-id="\${ws.workspace_id}">
                                    <i class="bi bi-gear"></i> Configure
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger delete-btn" data-workspace-id="\${ws.workspace_id}">
                                    <i class="bi bi-trash"></i> Remove
                                </button>
                            </div>
                        </div>
                        <div class="workspace-info">
                            <small>
                                <span class="badge bg-success">Connected</span>
                                \${ws.notion_database_id ? \`<span class="badge bg-info">Database: \${ws.notion_database_id.substring(0, 8)}...</span>\` : ''}
                            </small>
                        </div>
                    </div>
                \`).join('');

                // Setup event listeners for workspace actions
                container.querySelectorAll('.config-btn').forEach(btn => {
                    btn.addEventListener('click', () => openConfigModal(btn.dataset.workspaceId));
                });

                container.querySelectorAll('.delete-btn').forEach(btn => {
                    btn.addEventListener('click', () => deleteWorkspace(btn.dataset.workspaceId));
                });
            }

            async function handleAddWorkspace(e) {
                e.preventDefault();

                const workspaceName = document.querySelector(config.workspaceNameInput).value;
                const apiKey = document.querySelector(config.apiKeyInput).value;

                showStatus('loading', 'Connecting...');

                try {
                    const response = await fetch('/api/notion/credentials', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            workspace_name: workspaceName,
                            api_key: apiKey,
                        }),
                    });

                    const data = await response.json();

                    if (data.success) {
                        showStatus('success', 'Workspace connected successfully!');
                        document.querySelector(config.addWorkspaceForm).reset();
                        setTimeout(loadWorkspaces, 1000);
                    } else {
                        showStatus('error', data.error || 'Failed to connect workspace');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    showStatus('error', 'Connection failed');
                }
            }

            async function openConfigModal(workspaceId) {
                const modal = new bootstrap.Modal(document.querySelector(config.configModal));
                document.querySelector(config.configStatus).innerHTML = '';
                document.querySelector(config.databasesList).innerHTML = '';

                modal.show();

                // Load databases for this workspace
                try {
                    showConfigStatus('loading', 'Loading databases...');

                    const response = await fetch(\`/api/notion/databases?app=\${appName}&workspace=\${workspaceId}\`);
                    const data = await response.json();

                    if (data.success && data.databases) {
                        renderDatabasesList(data.databases, workspaceId);
                        document.querySelector(config.saveConfigBtn).dataset.workspaceId = workspaceId;
                        showConfigStatus('success', 'Databases loaded');
                    } else {
                        showConfigStatus('error', data.error || 'Failed to load databases');
                    }
                } catch (error) {
                    console.error('Error loading databases:', error);
                    showConfigStatus('error', 'Failed to load databases');
                }
            }

            function renderDatabasesList(databases, workspaceId) {
                const container = document.querySelector(config.databasesList);
                container.innerHTML = \`
                    <div class="database-selector">
                        <label class="form-label">Select Target Database</label>
                        <select id="database-select" class="form-select">
                            <option value="">-- None --</option>
                            \${databases.map(db => \`
                                <option value="\${db.id}">\${db.title || db.id}</option>
                            \`).join('')}
                        </select>
                        <small class="text-muted d-block mt-2">
                            Choose which Notion database to use for this app
                        </small>
                    </div>
                \`;
            }

            async function handleSaveConfig() {
                const workspaceId = document.querySelector(config.saveConfigBtn).dataset.workspaceId;
                const databaseId = document.querySelector('#database-select')?.value || null;

                try {
                    showConfigStatus('loading', 'Saving configuration...');

                    const response = await fetch(\`/api/notion/credentials/\${workspaceId}/config\`, {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            app: appName,
                            database_id: databaseId,
                        }),
                    });

                    const data = await response.json();

                    if (data.success) {
                        showConfigStatus('success', 'Configuration saved!');
                        setTimeout(() => {
                            bootstrap.Modal.getInstance(document.querySelector(config.configModal)).hide();
                            loadWorkspaces();
                        }, 1500);
                    } else {
                        showConfigStatus('error', data.error || 'Failed to save');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    showConfigStatus('error', 'Failed to save configuration');
                }
            }

            async function deleteWorkspace(workspaceId) {
                if (!confirm('Are you sure you want to remove this workspace?')) {
                    return;
                }

                try {
                    const response = await fetch(\`/api/notion/credentials/\${workspaceId}\`, {
                        method: 'DELETE',
                    });

                    const data = await response.json();

                    if (data.success) {
                        loadWorkspaces();
                    } else {
                        alert(data.error || 'Failed to delete workspace');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert('Failed to delete workspace');
                }
            }

            function showStatus(type, message) {
                const element = document.querySelector(config.statusElement);
                element.className = \`alert alert-status \${type}\`;
                element.textContent = message;
            }

            function showConfigStatus(type, message) {
                const element = document.querySelector(config.configStatus);
                element.className = \`alert alert-status \${type}\`;
                element.textContent = message;
            }

            function escapeHtml(text) {
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }
        })();
        </script>
        JS;
    }

    /**
     * Get all connected workspaces
     *
     * @return array List of workspaces
     */
    private function getWorkspaces(): array {
        return $this->dbHelper->listCredentials($this->appName);
    }

    /**
     * Get workspaces as JSON
     *
     * @return string JSON-encoded workspaces
     */
    public function getWorkspacesJson(): string {
        return json_encode($this->getWorkspaces());
    }

    /**
     * Get the app name
     *
     * @return string
     */
    public function getAppName(): string {
        return $this->appName;
    }

    /**
     * Get the widget ID
     *
     * @return string
     */
    public function getWidgetId(): string {
        return $this->widgetId;
    }
}
