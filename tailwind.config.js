module.exports = {
    prefix: 'bpm-',
    content: ['./**/*.php', './src/**/*.{js,jsx,ts,tsx}'],
    theme: {
        extend: {
            colors: {
                'primary': '#62748E',
            },
        },
    },
    plugins: [],
    safelist: [
        'bpm-input',
    ],
};