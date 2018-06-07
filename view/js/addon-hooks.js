var addon_hooks={};

function Addon_registerHook( type, hookfnstr )
{
	if (!addon_hooks.hasOwnProperty(type)) {
		addon_hooks[type]=[];
	}

	addon_hooks[type].push( hookfnstr );
	
	console.log("addon_hooks type "+type+" has "+addon_hooks[type].length+" hooks registered");
}
