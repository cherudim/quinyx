(function($) {
	/*Backbone.sync = function(method, model, options) {
		var type = "GET";
		if(method == "create") { // post
			type = "POST";
		} else if(method == "update") { // put
			type = "PUT";
		} else if(method == "delete") { // delete
			type = "DELETE";
		}
		$.ajax({
			type: type,
			url: model.urlRoot + (model.get('id') ? '&id=' + model.get('id') : ''),
			data: model,
			success: function(data, status, xhr) {
				options.success(data.data, status, xhr);
			},
			error: function(data, status, xhr) {
				options.error(data, status, xhr);
			}
		});
	};*/

	var Unit = Backbone.Model.extend({
		urlRoot: '/api.php?uri=unit',
		idAttribute: 'Id',
		defaults: {
			"Id": null,
			"Address": {},
			"Name": "",
			"Description": "",
			"ChiefEmployee": {}
		}
	});

	var Employee = Backbone.Model.extend({
		urlRoot: '/api.php?uri=employee',
		idAttribute: 'Id',
		defaults: {
			"Id": null,
			"Name": "",
			"Address": {},
			"StartAt": null,
			"EndAt": null,
			"Email": "",
			"Phone": "",
			"BornAt": null,
			"Unit": null
		},
		getStartAt: function() {
			return new Date(this.get('StartAt') * 1000);
		},
		getEndAt: function() {
			return new Date(this.get('EndAt') * 1000);
		},
		getBornAt: function() {
			return new Date(this.get('BornAt') * 1000);
		},
		getAge: function() {
			var now = new Date();
			var then = this.getBornAt();
			console.log(now.getFullYear() + ' - ' + then.getFullYear());
			return Math.floor(now.getFullYear() - then.getFullYear());
		}
	});

	var Employees = Backbone.Collection.extend({
		model: Employee,
		url: '/api.php?uri=employee',
		initialize: function() {
			this.fetch();
		}
	});

	var Units = Backbone.Collection.extend({
		model: Unit,
		url: '/api.php?uri=unit',
		initialize: function() {
			this.fetch();
		}
	});

	var EmployeeListView = Backbone.View.extend({
		tagName: 'tr',
		events: {
			'click .edit': 'edit',
			'click .delete': 'remove'
		},
		initialize: function() {
			_.bindAll(this, 'render', 'unrender', 'remove', 'edit');

			this.model.bind('change', this.render);
			this.model.bind('remove', this.unrender);
		},
		render: function() {
			var tpl = _.template($('#employees-row-template').html(), {model: this.model});
			this.$el.html(tpl);
			return this;
		},
		unrender: function() {
			$(this.el).remove();
		},
		remove: function() {
			this.model.destroy({wait: true});
		},
		edit: function() {
			var formView = new EmployeeFormView({
				model: this.model
			});
			$('body').append(formView.render().el);
		}
	});

	var EmployeeFormView = Backbone.View.extend({
		tagName: 'div',
		events: {
			'click button.close': 'unrender',
			'click button.submit': 'saveEmployee'
		},
		initialize: function() {
			_.bindAll(this, 'render', 'unrender', 'saveEmployee');
			if(!(this.model instanceof Employee)) {
				this.model = new Employee();
			}
		},
		render: function() {
			var tpl = _.template($('#employees-form').html(), {model: this.model, units: unitCollection});
			this.$el.html(tpl);
			return this;
		},
		unrender: function() {
			$(this.el).remove();
		},
		getFormData: function() {
			var data = $('form', this.el).serializeArray();
			var form = {};
			$.each(data, function(k, v) {
				form[v.name] = v.value;
			});
			return form;
		},
		saveEmployee: function(e) {
			e.preventDefault();
			this.model;
			var values = this.getFormData();
			this.model.set({
				'Name': values.Name,
				'Address': {
					'Street': values.AddressStreet,
					'Zip': values.AddressZip,
					'City': values.AddressCity,
					'Country': values.AddressCountry
				},
				'StartAt': values.StartAt,
				'EndAt': values.EndAt,
				'Email': values.Email,
				'Phone': values.Phone,
				'BornAt': values.BornAt,
				'Unit': values.Unit
			});
			console.log(this.model);
			var self = this;
			var isNew = this.model.isNew();
			this.model.save(null, {
				success: function(item) {
					console.log('Success!');
					console.log(item);
					if(isNew) {
						employeeCollection.add(item);
					}
					self.unrender();
				},
				error: function(item) {
					console.log('Failure : (');
				}
			});
		}
	});

	var UnitListView = Backbone.View.extend({
		tagName: 'tr',
		events: {
			'click .edit': 'edit',
			'click .delete': 'remove'
		},
		initialize: function() {
			_.bindAll(this, 'render', 'unrender', 'remove', 'edit');

			this.model.bind('change', this.render);
			this.model.bind('remove', this.unrender);
		},
		render: function() {
			var tpl = _.template($('#units-row-template').html(), {model: this.model});
			this.$el.html(tpl);
			return this;
		},
		unrender: function() {
			$(this.el).remove();
		},
		remove: function() {
			this.model.destroy({wait: true});
		},
		edit: function() {
			var formView = new UnitFormView({
				model: this.model
			});
			$('body').append(formView.render().el);
		}
	});

	var UnitFormView = Backbone.View.extend({
		tagName: 'div',
		events: {
			'click button.close': 'unrender',
			'click button.submit': 'saveUnit'
		},
		initialize: function() {
			_.bindAll(this, 'render', 'unrender', 'saveUnit');
			if(!(this.model instanceof Unit)) {
				this.model = new Unit();
			}
		},
		render: function() {
			var tpl = _.template($('#units-form').html(), {model: this.model, employees: employeeCollection});
			this.$el.html(tpl);
			return this;
		},
		unrender: function() {
			$(this.el).remove();
		},
		getFormData: function() {
			var data = $('form', this.el).serializeArray();
			var form = {};
			$.each(data, function(k, v) {
				form[v.name] = v.value;
			});
			return form;
		},
		saveUnit: function(e) {
			e.preventDefault();
			this.model;
			var values = this.getFormData();
			this.model.set({
				'Name': values.Name,
				'Address': {
					'Street': values.AddressStreet,
					'Zip': values.AddressZip,
					'City': values.AddressCity,
					'Country': values.AddressCountry
				},
				'Description': values.Description,
				'ChiefEmployee': values.ChiefEmployee
			});
			console.log(this.model);
			var self = this;
			var isNew = this.model.isNew();
			this.model.save(null, {
				success: function(item) {
					console.log('Success!');
					console.log(item);
					if(isNew) {
						unitCollection.add(item);
					}
					self.unrender();
				},
				error: function(item) {
					console.log('Failure : (');
				}
			});
		}
	});

	var EmployeesView = Backbone.View.extend({
		el: $('#content'),
		events: {
			'click button#new': 'newEmployeeForm'
		},
		initialize: function() {
			_.bindAll(this, 'render', 'newEmployeeForm');

			this.model.bind('add', this.appendItem);
		},
		render: function() {
			var self = this;
			var tpl = _.template($('#employees-template').html());
			this.$el.html(tpl);
			_(this.model.models).each(function(item) {
				self.appendItem(item);
			}, this);
		},
		newEmployeeForm: function() {
			var formView = new EmployeeFormView({
				model: new Employee(),
			});
			$('body').append(formView.render().el);
		},
		appendItem: function(item) {
			var itemView = new EmployeeListView({
				model: item
			});
			$('table tbody', this.el).append(itemView.render().el);
		},
		notify: function(data) {

		}
	});

	var EmployeeView = Backbone.View.extend({
		el: $('#content'),
		initialize: function() {
			_.bindAll(this, 'render');
		},
		render: function() {
			var tpl = _.template($('#employee-template').html(), {model: this.model});
			this.$el.html(tpl);
		}
	});

	var UnitsView = Backbone.View.extend({
		el: $('#content'),
		events: {
			'click button#new': 'newUnitForm'
		},
		initialize: function() {
			_.bindAll(this, 'render', 'newUnitForm');

			this.model.bind('add', this.appendItem);
		},
		render: function() {
			var self = this;
			var tpl = _.template($('#units-template').html());
			this.$el.html(tpl);
			_(this.model.models).each(function(item) {
				self.appendItem(item);
			}, this);
		},
		newUnitForm: function() {
			var formView = new UnitFormView({
				model: new Unit(),
			});
			$('body').append(formView.render().el);
		},
		appendItem: function(item) {
			var itemView = new UnitListView({
				model: item
			});
			$('table tbody', this.el).append(itemView.render().el);
		},
		notify: function(data) {

		}
	});

	employeeCollection = new Employees();
	unitCollection = new Units();

	var Router = Backbone.Router.extend({
		routes: {
			'employees/': 'getEmployees',
			'employees/:id': 'getEmployee',
			'units': 'getUnits',
			'units/:id': 'getUnit',
			'*actions': 'getEmployees'
		},
		getEmployees: function() {
			var employeesView = new EmployeesView({
				model: employeeCollection
			});
			this.render(employeesView);
		},
		getEmployee: function(id) {
			var employee = new Employee({Id: id});
			var self = this;
			employee.fetch({
				success: function(item) {
					var employeeView = new EmployeeView({
						model: employee
					});
					self.render(employeeView);
				}, 
				error: function(item) {
					this.navigate("/employees", true);
				}
			});
		},
		getUnits: function() {
			var unitsView = new UnitsView({
				model: unitCollection
			});
			this.render(unitsView);
		},
		getUnit: function() {

		},
		render: function(view) {
			if(this.currentView) {
				this.currentView.remove();
				this.currentView.unbind();
			}

			view.render();

			this.currentView = view;

			return this;
		}
	});

	var app = new Router();
	Backbone.history.start();
})(jQuery);