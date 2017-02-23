/*
 * Circles - bring cloud-users closer
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@pontapreta.net>
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


$(document).ready(function () {

	Navigation = {


		api: null,
		self: null,
		currCirclesType: '',
		currentCircle: 0,
		lastSearch: '',

		init: function () {

			self = this;
			api = OCA.Circles.api;

			$('#circles_new_type_definition div').fadeOut(0);
			$('#circles_new_type_' + ($('#circles_new_type option:selected').val())).fadeIn(0);

			$('#circles_new_type').hide();
			$('#circles_new_submit').hide();
			$('#circles_new_type_definition').hide();

			$('#circles_new_name').on('keyup', function (e) {
				self.currentCircle = 0;
				$('#app-navigation.circles').hide('slide', 800);
				$('#circles_list div').removeClass('selected');
				$('#emptycontent').show(800);
				$('#mainui').fadeOut(800);

				if ($('#circles_new_name').val() != '') {
					$('#circles_new_type').fadeIn(300);
					$('#circles_new_submit').fadeIn(500);
					$('#circles_new_type_definition').fadeIn(700);
				}
				else {
					$('#circles_new_type').fadeOut(700);
					$('#circles_new_submit').fadeOut(500);
					$('#circles_new_type_definition').fadeOut(300);
				}
			});

			$('#circles_new_type').on('change', function () {

				self.currentCircle = 0;
				$('#app-navigation.circles').hide('slide', 800);
				$('#circles_list div').removeClass('selected');
				$('#emptycontent').show(800);
				$('#mainui').fadeOut(800);

				$('#circles_new_type_definition div').fadeOut(300);
				$('#circles_new_type_' + ($('#circles_new_type option:selected').val())).fadeIn(
					300);
			});

			$('#circles_new_submit').on('click', function () {
				api.createCircle($('#circles_new_type').val(), $('#circles_new_name').val(),
					self.createCircleResult);
			});

			$('#circles_list div').on('click', function () {
				self.displayCirclesList($(this).attr('circle-type'));
			});

			$('#circles_search').on('input propertychange paste focus', function () {
				if (self.lastSearch == $(this).val().trim())
					return;

				self.lastSearch = $(this).val().trim();
				api.searchCircles(self.currCirclesType, $(this).val().trim(),
					self.listCirclesResult);

			});

			$('.icon-circles').css('background-image',
				'url(' + OC.imagePath('circles', 'colored') + ')');

			//$('#addmember').on('key')
			$('#addmember').on('input propertychange paste focus', function () {
				$.get(OC.linkToOCS('apps/files_sharing/api/v1') + 'sharees',
					{
						format: 'json',
						search: $(this).val().trim(),
						perPage: 200,
						itemType: 'principals'
					}, self.searchMembersResult);
			}).blur(function () {
				$('#members_search_result').fadeOut(400);
			});

			$('#members_search_result').hide();
		},


		createCircleResult: function (result) {
			var str = 'Circle';
			switch (result.type) {
				case '1':
					str = 'Personal circle';
					break;
				case '2':
					str = 'Hidden circle';
					break;
				case '4':
					str = 'Private circle';
					break;
				case '8':
					str = 'Public circle';
					break;
			}

			if (result.status == 1) {
				Notification.onSuccess(str + " '" + result.name + "' created");
				self.displayCirclesList(result.circle.type);
				self.selectCircle(result.circle.id);
			}
			else
				Notification.onFail(
					str + " '" + result.name + "' NOT created: " +
					((result.error) ? result.error.message : 'no error message'));
		},


		//
		//
		// Circles List
		displayCirclesList: function (type) {

			self.currCirclesType = type;
			self.lastSearch = '';

			self.currentCircle = 0;
			$('#app-navigation.circles').show('slide', 800);
			$('#emptycontent').show(800);
			$('#mainui').fadeOut(800);

			$('#circles_search').val('');

			$('#app-navigation.circles').addClass('selected');
			$('#circles_list div').removeClass('selected');

			$('#circles_list').children().each(function () {
				if ($(this).attr('circle-type') == type.toLowerCase())
					$(this).addClass('selected');
			});

			$('#app-navigation.circles').children().each(function () {
				if ($(this).attr('id') != 'circles_search')
					$(this).remove();
			});
			api.listCircles(type, self.listCirclesResult);
		},


		listCirclesResult: function (result) {

			if (result.status < 1) {
				Notification.onFail(
					'Issue while retreiving the list of the Circles: ' +
					((result.error) ? result.error.message : 'no error message'));
				return;
			}

			$('#app-navigation.circles').children().each(function () {
				if ($(this).attr('id') != 'circles_search')
					$(this).remove();
			});

			var data = result.data;
			for (var i = 0; i < data.length; i++) {

				//	var curr = self.getCurrentCircleTemplate(data[i].id);

				var tmpl = $('#tmpl_circle').html();

				tmpl = tmpl.replace(/%title%/, data[i].name);
				tmpl = tmpl.replace(/%type%/, data[i].type);
				tmpl = tmpl.replace(/%owner%/, data[i].owner.userid);
				tmpl = tmpl.replace(/%status%/, data[i].user.status);
				tmpl = tmpl.replace(/%count%/, data[i].count);
				tmpl = tmpl.replace(/%creation%/, data[i].creation);

				//	if (curr == null) {
				$('#app-navigation.circles').append(
					'<div class="circle" circle-id="' + data[i].id + '">' + tmpl + '</div>');
				//	} else {
				//		$(curr).html(tmpl);
				//	}
			}

			$('#app-navigation.circles').children('.circle').on('click', function () {
				self.selectCircle($(this).attr('circle-id'));
			});
		},


		selectCircle: function (circleid) {
			api.detailsCircle(circleid, this.selectCircleResult);
		},


		selectCircleResult: function (result) {

			$('#mainui #memberslist .table').children('tr').each(function () {
				if ($(this).attr('class') != 'header')
					$(this).remove();
			});


			if (result.status < 1) {
				Notification.onFail(
					'Issue while retreiving the details of a circle: ' +
					((result.error) ? result.error.message : 'no error message'));
				return;
			}

			$('#app-navigation.circles').children('.circle').each(function () {
				if ($(this).attr('circle-id') == result.circle_id)
					$(this).addClass('selected');
				else
					$(this).removeClass('selected');
			});
			$('#emptycontent').hide(800);
			$('#mainui').fadeIn(800);
			self.currentCircle = result.circle_id;

			var members = result.details.members;
			for (var i = 0; i < members.length; i++) {

				var tmpl = $('#tmpl_member').html();

				tmpl = tmpl.replace(/%username%/, members[i].userid);
				tmpl = tmpl.replace(/%level%/, members[i].level_string);
				tmpl = tmpl.replace(/%status%/, members[i].status);
				tmpl = tmpl.replace(/%joined%/, members[i].joined);
				tmpl = tmpl.replace(/%note%/,
					((members[i].note) ? members[i].note : ''));

				$('#mainui #memberslist .table').append(tmpl);
			}
		},


		searchMembersResult: function (response) {

			if (response == null ||
				(response.ocs.data.users == 0 && response.ocs.data.exact.users == 0))
				$('#members_search_result').fadeOut(300);

			else {
				var currSearch = $('#addmember').val().trim();
				$('#members_search_result').children().remove();

				$.each(response.ocs.data.exact.users, function (index, value) {
					$('#members_search_result').append(
						'<div class="members_search exact" searchresult="' +
						value.value.shareWith + '">' + value.label + '   (' +
						value.value.shareWith + ')</div>');
				});

				$.each(response.ocs.data.users, function (index, value) {
					var line = value.label + '   (' + value.value.shareWith + ')';
					if (currSearch.length > 0) line =
						line.replace(new RegExp('(' + currSearch + ')', 'gi'), '<b>$1</b>');

					$('#members_search_result').append(
						'<div class="members_search" searchresult="' + value.value.shareWith +
						'">' + line + '</div>');
				});

				$('#members_search_result').children().first().css('border-top-width', '0px');

				$('DIV.zendialog_result').on('click', function () {
					zenodoDialog.localCreator($(this).attr('searchresult'));
				});
				$('#members_search_result').fadeIn(300);
			}


		},

		// getCurrentCircleTemplate: function (id) {
		//
		// 	currdiv = null;
		// 	$('#app-navigation.circles').children().each(function () {
		// 		if ($(this).attr('circle-id') == id) {
		// 			currdiv = $(this);
		// 			return false;
		// 		}
		// 	});
		// 	return currdiv;
		// }

	};

	Notification = {

		notyf: null,

		init: function () {
			this.notyf = new Notyf({
				delay: 5000
			});
		},

		onSuccess: function (text) {
			this.notyf.confirm(text);
		},

		onFail: function (text) {
			this.notyf.alert(text);
		}


	};

	Notification.init();
	Navigation.init();

});

