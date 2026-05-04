const mix = require('laravel-mix');
const webpack = require('webpack');

mix.js('resources/js/app.js', 'public/js')
    .react()
    .postCss('resources/css/app.css', 'public/css');

mix.webpackConfig({
    plugins: [
        // On force la désactivation du plugin de progression par défaut de Mix
        // pour éviter l'erreur sur les propriétés 'name', 'color', etc.
    ],
    stats: {
        children: true,
    }
});

mix.options({
    // On coupe absolument toutes les notifications et barres d'état
    notifications: false,
    processCssUrls: false,
    showStepNumber: false
});