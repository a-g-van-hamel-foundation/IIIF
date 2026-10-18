/**
 * Store for registering/syncing VisualEditor fields in forms.
 *
 * Each field registers a flush() function; each such function
 * returns a Promise; and this store 'awaits' all Promises.
 *
 */
const { defineStore } = require( "pinia" );

module.exports = defineStore( "veFields", () => {
	const flushers = new Set();

	function register( flush ) {
		flushers.add( flush );
		return () => flushers.delete( flush );
	}

	/** 
	 * Resolves once every registered field has synced.
	 * Rejects if any failed.
	 */
	async function flushAll() {
		const results = await Promise.allSettled(
			[ ...flushers ].map( ( flush ) => flush() )
		);
		if ( results.some( ( r ) => r.status === "rejected" ) ) {
			throw new Error( "One or more fields could not be converted to wikitext." );
		}
	}

	return {
		register,
		flushAll
	};

} );
