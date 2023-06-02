/*
 * Circles - Bring cloud-users closer together.
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @copyright 2017
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */
(function() {
	/**
	 * @class OCA.Circles.FileList
	 * @augments OCA.Files.FileList
	 *
	 * @classdesc Circles file list.
	 * Contains a list of files filtered by circles
	 *
	 * @param $el container element with existing markup for the .files-controls
	 * and a table
	 * @param [options] map of options, see other parameters
	 * @param {Array.<string>} [options.circlesIds] array of system tag ids to
	 * filter by
	 */
	var FileList = function($el, options) {
		this.initialize($el, options);
	};
	FileList.prototype = _.extend({}, OCA.Files.FileList.prototype,
		/** @lends OCA.Circles.FileList.prototype */ {
			id: 'circlesfilter',
			appName: t('circles', 'Circles\' files'),

			/**
			 * Array of system tag ids to filter by
			 *
			 * @type Array.<string>
			 */
			_circlesIds: [],
			_lastUsedTags: [],

			_clientSideSort: true,
			_allowSelection: false,

			_filterField: null,

			/**
			 * @private
			 */
			initialize: function($el, options) {
				OCA.Files.FileList.prototype.initialize.apply(this, arguments);
				if (this.initialized) {
					return;
				}

				if (options && options.circlesIds) {
					this._circlesIds = options.circlesIds;
				}

				OC.Plugins.attach('OCA.Circles.FileList', this);

				var $controls = this.$el.find('.files-controls').empty();

				this._initFilterField($controls);
			},

			destroy: function() {
				this.$filterField.remove();

				OCA.Files.FileList.prototype.destroy.apply(this, arguments);
			},

			_initFilterField: function($container) {
				var self = this;
				this.$filterField = $('<input type="hidden" name="circles"/>');
				$container.append(this.$filterField);
				this.$filterField.select2({
					placeholder: t('circles', 'Select circles to filter by'),
					allowClear: false,
					multiple: true,
					toggleSelect: true,
					separator: ',',
					query: _.bind(this._queryCirclesAutocomplete, this),

					id: function(circle) {
						return circle.id;
					},

					initSelection: function(element, callback) {
						var val = $(element).val().trim();
						if (val) {
							var circleIds = val.split(','),
								circles = [];

							this.search('', function(result) {
								_.each(circleIds, function(circleId) {
									var circle = _.find(result.data, function(circleData) {
										return circleData.id == circleId;
									});
									if (!_.isUndefined(circle)) {
										circles.push(circle);
									}
								});

								callback(circles);
							});

						} else {
							callback([]);
						}
					},

					formatResult: function(circle) {
						return circle.name;
					},

					formatSelection: function(circle) {
						return circle.name;
					},

					sortResults: function(results) {
						return results;
					},

					escapeMarkup: function(m) {
						// prevent double markup escape
						return m;
					},
					formatNoMatches: function() {
						return t('circles', 'No circles found');
					}
				});
				this.$filterField.on('change', _.bind(this._onTagsChanged, this));
				return this.$filterField;
			},

			/**
			 * Autocomplete function for dropdown results
			 *
			 * @param {Object} query select2 query object
			 */
			_queryCirclesAutocomplete: function(query) {
				this.search(query.term, function(result) {
					query.callback({
						results: result.data
					});
				});
			},

			/**
			 * Event handler for when the URL changed
			 */
			_onUrlChanged: function(e) {
				if (e.dir) {
					var circles = _.filter(e.dir.split('/'), function(val) {
						return val.trim() !== '';
					});
					this.$filterField.select2('val', circles || []);
					this._circlesIds = circles;
					this.reload();
				}
			},

			_onTagsChanged: function(ev) {
				var val = $(ev.target).val().trim();
				if (val !== '') {
					this._circlesIds = val.split(',');
				} else {
					this._circlesIds = [];
				}

				this.$el.trigger(jQuery.Event('changeDirectory', {
					dir: this._circlesIds.join('/')
				}));
				this.reload();
			},

			updateEmptyContent: function() {
				var dir = this.getCurrentDirectory();
				if (dir === '/') {
					// root has special permissions
					if (!this._circlesIds.length) {
						// no tags selected
						this.$el.find('.emptyfilelist.emptycontent').html(
							'<div class="icon-systemtags"></div>' +
							'<h2>' + t('circles', 'Please select circles to filter by') + '</h2>');
					} else {
						// tags selected but no results
						this.$el.find('.emptyfilelist.emptycontent').html(
							'<div class="icon-systemtags"></div>' +
							'<h2>' + t('circles', 'No files found for the selected circles') + '</h2>');
					}
					this.$el.find('.emptyfilelist.emptycontent').toggleClass('hidden', !this.isEmpty);
					this.$el.find('.files-filestable thead th').toggleClass('hidden', this.isEmpty);
				} else {
					OCA.Files.FileList.prototype.updateEmptyContent.apply(this, arguments);
				}
			},

			getDirectoryPermissions: function() {
				return OC.PERMISSION_READ | OC.PERMISSION_DELETE;
			},

			updateStorageStatistics: function() {
				// no op because it doesn't have
				// storage info like free space / used space
			},

			reload: function() {
				if (!this._circlesIds.length) {
					// don't reload
					this.updateEmptyContent();
					this.setFiles([]);
					return $.Deferred().resolve();
				}

				this._selectedFiles = {};
				this._selectionSummary.clear();
				if (this._currentFileModel) {
					this._currentFileModel.off();
				}
				this._currentFileModel = null;
				this.$el.find('.select-all').prop('checked', false);
				this.showMask();
				this._reloadCall = this.filesClient.getFilteredFiles(
					{
						circlesIds: this._circlesIds
					},
					{
						properties: this._getWebdavProperties()
					}
				);
				if (this._detailsView) {
					// close sidebar
					this._updateDetailsView(null);
				}
				var callBack = this.reloadCallback.bind(this);
				return this._reloadCall.then(callBack, callBack);
			},

			reloadCallback: function(status, result) {
				if (result) {
					// prepend empty dir info because original handler
					result.unshift({});
				}

				return OCA.Files.FileList.prototype.reloadCallback.call(this, status, result);
			},

			search: function(term, callback) {
				this.request({
					method: 'GET',
					url: OC.generateUrl('/apps/circles/listing'),
					data: {
						term: term
					}
				}, callback);
			},

			request: function(options, callback) {
				var result = {status: -1};
				var self = this;
				$.ajax(options)
					.done(function(res) {
						self.onCallback(callback, res);
					})
					.fail(function() {
						self.onCallback(callback, result);
					});
			},

			onCallback: function(callback, result) {
				if (callback && (typeof callback === 'function')) {
					if (typeof result === 'object') {
						callback(result);
					} else {
						callback({status: -1});
					}
				}
			}
		});

	OCA.Circles.FileList = FileList;
})();

