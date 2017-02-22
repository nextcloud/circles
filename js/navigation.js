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
		currentCircle: 0,

		init: function () {

			self = this;
			api = OCA.Circles.api;


			$('#circles_new_type_definition div').fadeOut(0);
			$('#circles_new_type_' + ($('#circles_new_type option:selected').val())).fadeIn(0);

			$('#circles_new_type').hide();
			$('#circles_new_submit').hide();
			$('#circles_new_type_definition').hide();

			$('#circles_new_name').on('keyup', function (e) {
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
				$('#circles_new_type_definition div').fadeOut(300);
				$('#circles_new_type_' + ($('#circles_new_type option:selected').val())).fadeIn(
					300);
			});

			$('#circles_new_submit').on('click', function () {
				self.createCircle($('#circles_new_name').val(),
					$('#circles_new_type').val());
			});

			$('#circles_list div').on('click', function () {
				self.displayCirclesList($(this).attr('circle-type'))
				$('#app-navigation.circles').addClass('selected');
				$('#circles_list div').removeClass('selected');
				$(this).addClass('selected');
			});


			$('.icon-circles').css('background-image',
				'url(' + OC.imagePath('circles', 'colored') + ')');

			//$('#addmember').on('key')
			$('#addmember').on('input propertychange paste focus', function () {
				// if ($('#zendialog_creator_search').val().trim() == '')
				// 	zenodoDialog.searchCreatorResult(null);
				// else
				$.get(OC.linkToOCS('apps/files_sharing/api/v1') + 'sharees',
					{
						format: 'json',
						search: $(this).val().trim(),
						perPage: 200,
						itemType: 'principals'
					}, self.searchMembersResult);
			}).blur(function () {
				//$('#members_search_result').fadeOut(400);
			});

			$('#members_search_result').hide();
		},


		createCircle: function (name, type) {
			api.createCircle(name, type, this.createCircleResult);
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

			if (result.status == 1)
				Notification.onSuccess(str + " '" + result.name + "' created");
			else
				Notification.onFail(
					str + " '" + result.name + "' NOT created: " +
					((result.error) ? result.error.message : 'no error message'));
		},


		//
		//
		// Circles List
		displayCirclesList: function (type) {
			$('#app-navigation.circles').show('slide', 800);
			$('#emptycontent').show(800);
			$('#mainui').fadeOut(800);
			$('#app-navigation.circles').children().each(function () {
				if ($(this).attr('id') != 'circles_search')
					$(this).remove();
			});
			api.listCircles(type, this.listCirclesResult);
		},


		listCirclesResult: function (result) {

			if (result.status < 1) {
				Notification.onFail(
					'Issue while retreiving the list of the Circles: ' +
					((result.error) ? result.error.message : 'no error message'));
				return;
			}


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
			console.log(JSON.stringify(response));

			if (response == null ||
				(response.ocs.data.users == 0 && response.ocs.data.exact.users == 0))
				$('#members_search_result').fadeOut(300);

			else {
				$('#members_search_result').children().remove();

				$.each(response.ocs.data.exact.users, function (index, value) {
					$('#members_search_result').append(
						'<div class="members_search exact" searchresult="' +
						value.value.shareWith +
						'">' + value.label + '   (' +
						value.value.shareWith + ')</div>');
				});
				$.each(response.ocs.data.users, function (index, value) {
					$('#members_search_result').append(
						'<div class="members_search" searchresult="' + value.value.shareWith +
						'">' + value.label + '   (' +
						value.value.shareWith + ')</div>');
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


//
// $(document).ready(function() {
// 	var removeMember = function() {
// 		var teamId = $('#app-navigation').find('.active').first().data('navigation'),
// 			memberId = $(this).data('user_id');
//
// 		$.ajax({
// 			method: 'DELETE',
// 			url: OC.linkTo('teams', 'teams/' + teamId + '/members'),
// 			data: {
// 				userId: memberId
// 			}
// 		}).done(function() {
// 			// TODO re-render in JS
// 			location.reload();
// 		}).fail(function(){
// 			// TODO on failure
// 		});
// 	};
//
// 	var openTeam = function() {
// 		var teamId = $(this).data('navigation');
// 		$('#app-navigation').find('.active').removeClass('active');
// 		$(this).addClass('active');
//
// 		$('#emptycontent').addClass('hidden');
// 		$('#loading_members').removeClass('hidden');
// 		$('#container').addClass('hidden');
// 		$('#container').find('.memberList').empty();
//
// 		$.get(
// 			OC.linkTo('teams', 'teams/' + teamId + '/members'),
// 			[],
// 			function(result) {
// 				$('#loading_members').addClass('hidden');
//
// 				var $memberList = $('#container').find('.memberList');
//
// 				_.each(result.members, function(member){
// 					$memberList.append(
// 						$('<li>')
// 							.data('user_id', member.user_id)
// 							.text(member.user_id + ' (' + member.status + ')')
// 							.on('click', removeMember)
// 					);
// 				});
//
// 				$('#container').removeClass('hidden');
// 			}
// 		).fail(function(){
// 			// TODO on failure
// 			$('#loading_members').addClass('hidden');
// 			$('#emptycontent').removeClass('hidden');
// 		});
// 	};
//
// 	$('#app-navigation').find('a').on('click', openTeam);
//
// 	$.get(
// 		OC.linkTo('teams', 'teams'),
// 		[],
// 		function(result) {
// 			$navigation = $('#app-navigation');
// 			$teamsNavigation = $navigation.find('.teams');
//
// 			$teamsNavigation.append(
// 				$('<li>').addClass('header').text('My teams')
// 			);
//
// 			_.each(result.myTeams, function(team){
// 				$teamsNavigation.append(
// 					$('<li>').append(
// 						$('<a>').data('navigation', team.id).append(
// 							$('<span>').addClass('no-icon').text(team.name)
// 						).on('click', openTeam)
// 					)
// 				);
// 			});
//
//
// 			$teamsNavigation.append(
// 				$('<li>').addClass('header').text('Other teams')
// 			);
//
// 			_.each(result.otherTeams, function(team){
// 				$teamsNavigation.append(
// 					$('<li>').append(
// 						$('<a>').data('navigation', team.id).append(
// 							$('<span>').addClass('no-icon').text(team.name + ' by ' + team.owner
// + ' (' + team.status + ')') ).on('click', openTeam) ) ); }); } ).fail(function(){ // TODO on
// failure });  var createTeam = function(e){ if (e.keyCode === 13) { $.ajax({ method: 'PUT', url:
// OC.linkTo('teams', 'teams'), data: { name: $('#newTeam').val() } }).done(function() { // TODO
// re-render in JS location.reload(); }).fail(function(){ // TODO on failure }); } };
// $('#newTeam').on('keyup', createTeam);    var addMember = function(e){ if (e.keyCode === 13) {
// var teamId = $('#app-navigation').find('.active').first().data('navigation');  $.ajax({ method:
// 'PUT', url: OC.linkTo('teams', 'teams/' + teamId + '/members'), data: { userId:
// $('#addMember').val() } }).done(function() { // TODO re-render in JS location.reload();
// $('#addMember').val(''); }).fail(function(){ // TODO on failure }); } };
// $('#addMember').on('keyup', addMember);    });