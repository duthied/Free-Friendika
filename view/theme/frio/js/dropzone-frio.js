function isInteger(value) {
	return /^\d+$/.test(value);
}

function getMBytes(value) {
	var res;
	if (isInteger(value)) {
		res = value;
	} else {
		eh = value.slice(-1);
		am = Number(value.slice(0, -1));
		switch (eh) {
			case 'k':
				res = am * 1024;
				break;
			case 'm':
				res = am * 1024 * 1024;
				break;
			case 'g':
				res = am * 1024 * 1024 * 1024;
		}
	}
	return res / 1000000;
}
