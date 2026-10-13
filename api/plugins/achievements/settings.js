// Achievements settings: "Reset everything" removes all badges and statistics of all users
$(document).on('click', '.achievementsResetAll', function () {
	if (!confirm(window.lang.translate('Remove all badges and statistics of all users? This cannot be undone.'))) {
		return;
	}
	const $button = $(this).prop('disabled', true);
	londerlandAPI2('DELETE', 'api/v2/plugins/achievements/reset')
		.done(function (data) {
			messageSingle(data.response.message, '', activeInfo.settings.notifications.position, '#FFF', 'success', '5000');
		})
		.fail(function (xhr) {
			LonderlandApiError(xhr, 'Achievements');
		})
		.always(function () {
			$button.prop('disabled', false);
		});
});
