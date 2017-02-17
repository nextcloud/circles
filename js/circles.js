//
//
//


var Circles = {


	init: function () {
		console.log("init Circles");


	},


	createCircle: function (name, callback) {

		var result = {status: -1};
		$.ajax({
			method: 'PUT',
			url: OC.generateUrl(OC.linkTo('circles', 'circles')),
			data: {
				name: name
			}
		}).done(function (res) {
			Circles.onCallback(callback, res);
		}).fail(function () {
			Circles.onCallback(callback, result);
		});
	},


	onCallback: function (callback, result) {

		if (callback && (typeof callback === "function")) {
			callback(result);
		}
	}


}


