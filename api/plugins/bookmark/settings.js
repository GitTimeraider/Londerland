// Bookmarks settings: "Set up" creates the Bookmarks tab and a first category when they are missing
$(document).on('click', '.bookmarkSetup', function () {
	const $button = $(this).prop('disabled', true);
	londerlandAPI2('POST', 'api/v2/plugins/bookmark/setup', {})
		.done(function (data) {
			messageSingle(data.response.message, '', activeInfo.settings.notifications.position, '#FFF', 'success', '8000');
		})
		.fail(function (xhr) {
			LonderlandApiError(xhr, 'Bookmarks');
		})
		.always(function () {
			$button.prop('disabled', false);
		});
});
