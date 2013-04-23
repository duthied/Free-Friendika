
function menuItem (data){
	if (!data) data = ['','','','']
	this.url = ko.observable(data[0]);
	this.text = ko.observable(data[1]);
	this.style = ko.observable(data[2]);
	this.title = ko.observable(data[3]);
}


function navModel(data) {
	this.nav = ko.observableArray([]);
	
	if (data) {
		for (k in data.nav) {
			var n = new menuItem(data.nav[k]);
			console.log(k, data.nav[k], n);
			this.nav.push(n);
		}
	}
	
}

function App() {
	var self = this;
	this.nav = ko.observable();
	
	$.getJSON(window.location, function(data) {
		for(k in data){
			//console.log(k);
			switch(k) {
				case 'nav':
					var n = new navModel(data[k][0]);
					self.nav(n);
					break;
			}
			
		}
	});
	
}

ko.applyBindings(new App());


/*var App = {

	menuItem : function(data){
		if (!data) data = ['','','','']
		this.url = ko.observable(data[0]);
		this.text = ko.observable(data[1]);
		this.style = ko.observable(data[2]);
		this.title = ko.observable(data[3]);
	},
	
	navModel : function() {
		
		
	},

}*/




// Activates knockout.js
//ko.applyBindings(new navModel());

/*
$(document).ready(function(){
	$.getJSON(window.location, function(data) {
		for(k in data){
			var model = k+"Model";
			if (model in App) {
				for (kk in data[k][0]) {
					console.log(kk);
				}
				
				
			} 				
		}
		
	}); 
})
*/