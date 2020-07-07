/*
 * Copyright (c) 2017 Cooperativa EITA (eita.org.br)
 *
 * @author Vinicius Cubas Brand <vinicius@eita.org.br>
 * @author Daniel Tygel <dtygel@eita.org.br>
 *
 * This file is licensed under the Affero General Public License version 3
 * or later.
 *
 * See the COPYING-README file.
 *
 */

(function() {
	if (!OCA.Circles) {
		/**
		 * @namespace
		 */
		OCA.Circles = {};
	}

	OCA.Circles.App = {

		initFileList: function($el) {
			if (this._fileList) {
				return this._fileList;
			}

			this._fileList = new OCA.Circles.FileList(
				$el,
				{
					id: 'circles',
					scrollContainer: $('#app-content'),
					fileActions: this._createFileActions(),
					config: OCA.Files.App.getFilesConfig()
				}
			);

			this._fileList.appName = t('circles', 'Circles');
			return this._fileList;
		},

		removeFileList: function() {
			if (this._fileList) {
				this._fileList.$fileList.empty();
			}
		},

		_createFileActions: function() {
			// inherit file actions from the files app
			var fileActions = new OCA.Files.FileActions();
			// note: not merging the legacy actions because legacy apps are not
			// compatible with the sharing overview and need to be adapted first
			fileActions.registerDefaultActions();
			fileActions.merge(OCA.Files.fileActions);

			if (!this._globalActionsInitialized) {
				// in case actions are registered later
				this._onActionsUpdated = _.bind(this._onActionsUpdated, this);
				OCA.Files.fileActions.on('setDefault.app-circles', this._onActionsUpdated);
				OCA.Files.fileActions.on('registerAction.app-circles', this._onActionsUpdated);
				this._globalActionsInitialized = true;
			}

			// when the user clicks on a folder, redirect to the corresponding
			// folder in the files app instead of opening it directly
			fileActions.register('dir', 'Open', OC.PERMISSION_READ, '', function (filename, context) {
				OCA.Files.App.setActiveView('files', {silent: true});
				OCA.Files.App.fileList.changeDirectory(OC.joinPaths(context.$file.attr('data-path'), filename), true, true);
			});
			fileActions.setDefault('dir', 'Open');
			return fileActions;
		},

		_onActionsUpdated: function(ev) {
			if (!this._fileList) {
				return;
			}

			if (ev.action) {
				this._fileList.fileActions.registerAction(ev.action);
			} else if (ev.defaultAction) {
				this._fileList.fileActions.setDefault(
					ev.defaultAction.mime,
					ev.defaultAction.name
				);
			}
		},

		/**
		 * Destroy the app
		 */
		destroy: function() {
			OCA.Files.fileActions.off('setDefault.app-circles', this._onActionsUpdated);
			OCA.Files.fileActions.off('registerAction.app-circles', this._onActionsUpdated);
			this.removeFileList();
			this._fileList = null;
			delete this._globalActionsInitialized;
		}
	};

})();

$(document).ready(function() {
	$('#app-content-circlesfilter').on('show', function(e) {
		OCA.Circles.App.initFileList($(e.target));
	});
	$('#app-content-circlesfilter').on('hide', function() {
		OCA.Circles.App.removeFileList();
	});
});
