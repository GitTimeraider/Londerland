// AI Chat settings: "Test connection" asks the configured server for its models
$(document).on('click', '.aichatTestConnection', function () {
	const $button = $(this).prop('disabled', true);
	messageSingle(window.lang.translate('Testing connection...'), '', activeInfo.settings.notifications.position, '#FFF', 'info', '5000');
	londerlandAPI2('GET', 'api/v2/plugins/aichat/test')
		.done(function (data) {
			const models = data.response.data || [];
			const list = models.length ? '<br>' + models.slice(0, 15).map((m) => $('<div>').text(m).html()).join(', ') + (models.length > 15 ? ', ...' : '') : '';
			messageSingle(data.response.message, list, activeInfo.settings.notifications.position, '#FFF', 'success', '15000');
		})
		.fail(function (xhr) {
			LonderlandApiError(xhr, 'AI Chat');
		})
		.always(function () {
			$button.prop('disabled', false);
		});
});
// "Test search" runs one search with the saved provider
$(document).on('click', '.aichatTestSearch', function () {
	const $button = $(this).prop('disabled', true);
	londerlandAPI2('GET', 'api/v2/plugins/aichat/test/search')
		.done(function (data) {
			const results = data.response.data || [];
			const list = results.slice(0, 5).map((r) => $('<div>').text(r.title).html()).join('<br>');
			messageSingle(data.response.message, list, activeInfo.settings.notifications.position, '#FFF', 'success', '15000');
		})
		.fail(function (xhr) {
			LonderlandApiError(xhr, 'AI Chat');
		})
		.always(function () {
			$button.prop('disabled', false);
		});
});
// Chat button colour: colour picker with a few presets (light and dark purple first)
$('body').arrive('#AICHAT-settings-items input.aichat-color-picker', function () {
	initColorPickers(this, {
		swatches: ['#b39ddb', '#5e35b1', '#2cabe3', '#26a69a', '#66bb6a', '#ffa726', '#ef5350', '#ec407a', '#78909c', '#212121'],
	});
});
