var addon_hooks={};

function Addon_registerHook( type, hookfnstr )
{
	if (!addon_hooks.hasOwnProperty(type)) {
		addon_hooks[type]=[];
	}

	addon_hooks[type].push( hookfnstr );
}
