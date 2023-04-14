var Hook = {
	hooks: [],

	register: function(name, callback) {
		if(typeof Hook.hooks[name] == 'undefined') {
			Hook.hooks[name] = [];
		}
		Hook.hooks[name].push(callback);
	},

	fire: function(name, result, arguments) {
		if(typeof arguments == 'undefined' || (typeof arguments != 'undefined' && !Array.isArray(arguments))) {
			arguments = [];
		}
		arguments.unshift(result);
		if(typeof Hook.hooks[name] != 'undefined') {
			for(i = 0; i < Hook.hooks[name].length; i++) {
				arguments[0] = result;
				result = Hook.hooks[name][i].apply(undefined, arguments);
			}
		}
		return result;
	}
}