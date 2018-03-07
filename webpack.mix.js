const { mix } = require('laravel-mix');

const tailwindcss = require('tailwindcss');

mix.setPublicPath('./');

mix
	.sass('resources/assets/sass/app.scss', 'public/assets/css/app.css')
	.js('resources/assets/js/app.js', 'public/assets/js/app.js')
	.browserSync({
		proxy: 'chairking.test',
		files: ["public/assets/css/*", "public/assets/js/**", "craft/templates/**"]
	})
	.version()
	.sourceMaps()
	.options({
		processCssUrls: false,
		postCss: [tailwindcss('tailwind.js')],
	});

