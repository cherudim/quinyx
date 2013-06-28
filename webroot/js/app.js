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
		}
	});

	var Employees = Backbone.Collection.extend({
		model: Employee,
		url: '/api.php?uri=employee',
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
		},
		render: function() {
			var tpl = _.template($('#employees-form').html(), {model: this.model});
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
			employee = new Employee();
			var values = this.getFormData();
			console.log(values);
			employee.set({
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
				'Unit': values.Unit
			});
			employee.save({
				success: function(item) {
					employeesCollection.add(employee);
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

			this.render();
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
				model: new Employee()
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

			this.render();
		},
		render: function() {
			var tpl = _.template($('#employee-template').html(), {model: this.model});
			this.$el.html(tpl);
		}
	});

	var Router = Backbone.Router.extend({
		routes: {
			'employees/': 'getEmployees',
			'employees/:id': 'getEmployee',
			'units': 'getUnits',
			'units/:id': 'getUnit',
			'*actions': 'getEmployees'
		},
		getEmployees: function() {
			this.employeesCollection = new Employees();

			this.employeesView = new EmployeesView({
				model: this.employeesCollection
			});
		},
		getEmployee: function(id) {
			var employee = new Employee({Id: id});
			employee.fetch({
				success: function(item) {
					this.employeeView = new EmployeeView({
						model: employee
					});
				}, 
				error: function(item) {
					this.navigate("/employees", true);
				}
			})
		},
		getUnits: function() {

		},
		getUnit: function() {

		}
	});

	var app = new Router();
	Backbone.history.start();
})(jQuery);