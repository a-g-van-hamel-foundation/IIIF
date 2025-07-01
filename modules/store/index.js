const { createStore } = require("vuex");
module.exports = exports = createStore({
	state () {
		return {
			OSDViewer: {},
			OSDAnnotator: {},
			annotationList: {},
			currentCanvas: {}
		}
	},
	mutations: {
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
		}
	}
});
