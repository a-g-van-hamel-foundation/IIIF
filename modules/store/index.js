const { createStore } = require("vuex");
module.exports = exports = createStore({
	state () {
		return {
			count: 0,
			OSDViewer: {},
			OSDAnnotator: {},
			annotationList: {},
			currentCanvas: {}
		}
	},
	mutations: {
		// example
		increment (state) {
			state.count++
		},
		// call as store.commit("updateCurrentCanvas", {...} )
		// or store.commit({ type: 'updateCurrentCanvas', foo1: 10, foo2: "..." })
		updateCurrentCanvas (state, n) {
			//console.log( "store: updateCurrentCanvas, new index", n.index );
			state.currentCanvas = n;
		}
	},
	getters: {
		getCurrentCanvas (state) {
			return state.currentCanvas;			
		},
		doneTodos (state) {
			return state.todos.filter(todo => todo.done)
		}
	}
});
