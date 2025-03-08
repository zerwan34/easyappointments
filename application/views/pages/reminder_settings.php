<?php extend('layouts/backend_layout'); ?>

<?php section('content'); ?>

<div id="reminder-settings-page" class="container backend-page">
    <div class="row">
        <!-- Barre latérale des paramètres -->
        <div class="col-sm-3 offset-sm-1">
            <?php component('settings_nav'); ?>
        </div>

        <!-- Contenu principal -->
        <div id="reminder-settings" class="col-sm-6">
            <form>
                <fieldset>
                    <div class="d-flex justify-content-between align-items-center border-bottom mb-4 py-2">
                        <h4 class="text-black-50 mb-0 fw-light">
                            <?= lang('reminder_settings') ?>
                        </h4>

                        <div>
                            <!-- Bouton retour -->
                            <a href="<?= site_url('integrations') ?>" class="btn btn-outline-primary me-2">
                                <i class="fas fa-chevron-left me-2"></i>
                                <?= lang('back') ?>
                            </a>

                            <!-- Bouton enregistrer -->
                            <button type="button" id="save-settings" class="btn btn-primary">
                                <i class="fas fa-check-square me-2"></i>
                                <?= lang('save') ?>
                            </button>
                        </div>
                    </div>

                    <!-- Case à cocher pour activer/désactiver le service -->
                    <div class="row">
                        <div class="col-12">
                            <div class="mb-3 form-check">
                                <input 
                                    type="checkbox" 
                                    class="form-check-input" 
                                    id="enable-reminder" 
                                    data-field="reminder_enable"
                                >
                                <label class="form-check-label" for="enable-reminder">
                                    <?= lang('enable_reminder_service') ?>
                                </label>
                            </div>
                        </div>
                    </div>
                </fieldset>
            </form>
        </div>
    </div>
</div>

<?php end_section('content'); ?>

<?php section('scripts'); ?>
    <!-- Injection des variables pour le JS -->
    <script>
        var script_vars = <?= json_encode([
            'reminder_settings' => $reminder_settings,
            'csrf_token'        => $csrf_token,
        ]); ?>;
        function vars(key) {
            return script_vars[key] || null;
        }
    </script>
    <!-- Inclusion des scripts spécifiques -->
    <script src="<?= asset_url('assets/js/http/reminder_settings_http_client.js') ?>"></script>
    <script src="<?= asset_url('assets/js/pages/reminder_settings.js') ?>"></script>
<?php end_section('scripts'); ?>
