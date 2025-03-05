App.Http.ReminderSettings = (function () {
    /**
     * Sauvegarde les paramètres de reminder.
     * @param {Array} reminderSettings
     * @return {Object} (jQuery Deferred / Promise)
     */
    function save(reminderSettings) {
        const url = App.Utils.Url.siteUrl('reminder_settings/save');
        const data = {
            csrf_token: vars('csrf_token'),
            reminder_settings: reminderSettings
        };
        return $.post(url, data);
    }

    return {
        save,
    };
})();
