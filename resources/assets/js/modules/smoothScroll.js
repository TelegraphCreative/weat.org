//
// Example Exports for simple functions:
//
export default function smooth() {
	console.log('hi')
	var SmoothScroll = require("../vendor/smooth-scroll.js");
	const scroll = new SmoothScroll('a[href*="#"]', {
			speed: 750,
			offset: 64,
			easing: 'easeInOutCubic',
	});

}