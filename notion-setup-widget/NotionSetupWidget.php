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
            <?php echo file_get_contents(__DIR__ . '/assets/css/widget.css'); ?>
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


            </div>
        </div>

        <script>
            <?php echo file_get_contents(__DIR__ . '/assets/js/widget.js'); ?>

            // Initialize the widget with PHP configuration
            initNotionSetupWidget({
                apiBase: '<?php echo $this->config['api_base']; ?>',
                appName: '<?php echo $this->config['app_name']; ?>'
            });
        </script>
        <?php
        return ob_get_clean();
    }
}
