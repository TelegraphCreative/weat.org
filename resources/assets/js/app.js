//
// WEAT, Built by Telegraph
//

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

// ## Lazysizes (Images)
require("./vendor/lazysizes.js");

//
// # Examples:
//
// // ## Simple function export example:
// // import {smooth} from './modules/example-simple-function-export.js'

// // ## Export default function: 
// // import smooth from './modules/smoothScroll';

// // ## Require an entire file:
// // require("./vendor/lazysizes.js");

console.log('WEAT, Built by Telegraph');
