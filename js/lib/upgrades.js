/**
 * Provides functions for site upgrades performed through AJAX
 */

elgg.provide('elgg.upgrades');

elgg.upgrades.init = function () {
	$('#comment-upgrade-run').click(elgg.upgrades.upgradeComments);
};

elgg.upgrades.config = {
	minLimit: 10, //minimum limit value for single chunk of data being processed
	maxLimit: 300, //maximum limit value for single chunk of data being processed
	timeThreshold: 20000, //target conversion time in microseconds used for limit regulation
	changeFactor: 1.5 //multiplier or divider used for limit regulation
};

/**
 * Initializes the comment upgrade feature
 *
 * @param {Object} e Event object
 */
elgg.upgrades.upgradeComments = function(e) {
	e.preventDefault();

	// The total amount of comments to be upgraded
	var total = $('#comment-upgrade-total').text();

	// Initialize progressbar
	$('.elgg-progressbar').progressbar({
		value: 0,
		max: total
	});

	// Replace button with spinner when upgrade starts
	$('#comment-upgrade-run').addClass('hidden');
	$('#comment-upgrade-spinner').removeClass('hidden');

	// Start comment upgrade from offset 0 and limit 10
	elgg.upgrades.upgradeCommentBatch(0, 10);
};

/**
 * Fires the ajax action to upgrade a batch of comments.
 *
 * @param {Number} offset  The next upgrade offset
 */
elgg.upgrades.upgradeCommentBatch = function(offset, limit) {
	var options = {
			data: {
				offset: offset,
				limit: limit
			},
			dataType: 'json'
		},
		$upgradeCount = $('#comment-upgrade-count');

	options.data = elgg.security.addToken(options.data);

	var startTime = new Date().getTime();
	var self = this;

	options.success = function(json) {
		// Append possible errors after the progressbar
		if (json.system_messages.error.length) {
			var msg = '<li class="elgg-message elgg-state-error">' + json.system_messages.error + '</li>';
			$('#comment-upgrade-messages').append(msg);
		}

		// Increase success statistics
		var numSuccess = $('#comment-upgrade-success-count');
		var successCount = parseInt(numSuccess.text()) + json.output.numSuccess;
		numSuccess.text(successCount);

		// Increase error statistics
		var numErrors = $('#comment-upgrade-error-count');
		var newOffset = parseInt(numErrors.text()) + json.output.numErrors;
		numErrors.text(newOffset);

		// Increase total amount of processed comments
		var numProcessed = parseInt($upgradeCount.text()) + json.output.numSuccess + json.output.numErrors;
		$upgradeCount.text(numProcessed);

		// Increase percentage
		var total = $('#comment-upgrade-total').text();
		var percent = parseInt(numProcessed * 100 / total);

		// Increase the progress bar
		$('.elgg-progressbar').progressbar({ value: numProcessed });

		if (numProcessed < total) {
			//adjust limit based on last run time
			var rTime = new Date().getTime() - startTime;
			$('#comment-upgrade-speed').text(elgg.echo('upgrade:comments:speed', [(limit / rTime * 1000).toFixed(1)]));
			if (rTime < self.config.timeThreshold) {
				limit *= self.config.changeFactor;
			} else {
				limit /= self.config.changeFactor;
			}
			limit = Math.round(limit);
			//clip to acceptable bounds
			if (limit < self.config.minLimit) {
				limit = self.config.minLimit;
			} else if (limit > self.config.maxLimit) {
				limit = self.config.maxLimit;
			}
			/**
			 * Start next upgrade call. Offset is the total amount of erros so far.
			 * This prevents faulty comments from causing the same error again.
			 */
			elgg.upgrades.upgradeCommentBatch(newOffset, limit);
		} else {
			// Upgrade is finished
			elgg.system_message(elgg.echo('upgrade:comments:finished'));

			$('#comment-upgrade-spinner').addClass('hidden');

			percent = '100';
		}

		$('#comment-upgrade-counter').text(percent + '%');
	};

	// We use post() instead of action() to get better control over error messages
	return elgg.post('action/admin/site/comment_upgrade', options);
};

elgg.register_hook_handler('init', 'system', elgg.upgrades.init);
