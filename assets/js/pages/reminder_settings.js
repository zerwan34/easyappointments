App.Pages.ReminderSettings = (function () {
    const $saveSettings = $('#save-settings');

    /**
     * Remplit le formulaire avec les données existantes.
     * @param {Array} reminderSettings
     */
    function deserialize(reminderSettings) {
        reminderSettings.forEach((setting) => {
            const $field = $('[data-field="' + setting.name + '"]');
            $field.is(':checkbox')
                ? $field.prop('checked', Boolean(Number(setting.value)))
                : $field.val(setting.value);
        });
    }

    /**
     * Récupère les valeurs du formulaire.
     * @return {Array}
     */
    function serialize() {
        const reminderSettings = [];
        $('[data-field]').each((index, field) => {
            const $field = $(field);
            reminderSettings.push({
                name: $field.data('field'),
                value: $field.is(':checkbox') ? Number($field.prop('checked')) : $field.val()
            });
        });
        return reminderSettings;
    }

    /**
     * Callback lors du clic sur "Enregistrer".
     */
    function onSaveSettingsClick() {
        const reminderSettings = serialize();
        App.Http.ReminderSettings.save(reminderSettings)
            .done(() => {
                App.Layouts.Backend.displayNotification(lang('settings_saved'));
            })
            .fail((error) => {
                console.error(error);
                App.Layouts.Backend.displayNotification(lang('error_saving_settings'));
            });
    }

    /**
     * Initialise la page.
     */
    function initialize() {
        $saveSettings.on('click', onSaveSettingsClick);
        const reminderSettings = vars('reminder_settings');
        deserialize(reminderSettings);
    }

    document.addEventListener('DOMContentLoaded', initialize);

    return {};
})();
