//
// WEAT, Built by Telegraph
//


// ## Lazysizes (Images)
require("./vendor/lazysizes.js");

//
// # On Call Imports (called with `data-module` attr. as needed)
//
import './modules'

//
// # Global Imports/Requires (need all the time)
//

// ## Smooth Scroll
import smooth from './modules/smoothScroll';
smooth();

// import fieldFormats from './modules/fieldFormats';
// fieldFormats();

//
// # Examples:
//
// // ## Simple function export example:
// // import {smooth} from './modules/example-simple-function-export.js'

// // ## Export default function: 
// // import smooth from './modules/smoothScroll';

// // ## Require an entire file:


// SVG 4 Everybody
require("./vendor/svg4everybody.min.js");


//
// If IE 11
//
var isIE11 = !!window.MSInputMethodContext && !!document.documentMode;
if(isIE11){
	
	document.querySelector('html').classList.add('is-ie11');

}


console.log('WEAT, Built by Telegraph');