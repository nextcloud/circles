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

	Circles.Navigation = {

		init: function () {

			var self = this;
			self.hideAllCircleHelp();
			self.showCircleHelp();
			$('#circles_new_type').hide();
			$('#circles_new_submit').hide();
			$('#circles_new_type_definition').hide();

			Circles.init();


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
				self.hideAllCircleHelp(300);
				self.showCircleHelp();
			});

			$('#circles_new_submit').on('click', function () {
				Circles.Navigation.createCircle($('#circles_new_name').val(),
					$('#circles_new_type').val());
			});

		},


		hideAllCircleHelp: function (delay) {
			if (!delay) delay = 0;
			$('#circles_new_type_personal').fadeOut(delay);
			$('#circles_new_type_hidden').fadeOut(delay);
			$('#circles_new_type_private').fadeOut(delay);
			$('#circles_new_type_public').fadeOut(delay);
		},

		showCircleHelp: function () {
			$('#circles_new_type_' + ($('#circles_new_type option:selected').val())).fadeIn(300);
		},


		createCircle: function (name, type) {

			console.log("creating: " + name + " " + type);
			Circles.createCircle(name, type, Circles.Navigation.createCircleResult);
		},


		createCircleResult: function (result) {
			console.log("result: " + result.status);
		}

	};

	Circles.Navigation.init();

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