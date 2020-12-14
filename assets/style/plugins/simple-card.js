// see https://github.com/NathanHeffley/tailwindcss-card
const plugin = require('tailwindcss/plugin')
module.exports = plugin(function({ addComponents, e, theme }) {
    const defaultTheme = {
        maxWidth: theme('maxWidth.sm', '24rem'),
        borderRadius: theme('borderRadius.default', '.25rem'),
        boxShadow: theme('boxShadow.lg', '0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05)'),
        padding: `${theme('padding.4', '1rem')} ${theme('padding.6', '1.5rem')}`,
    }

    const userTheme = theme('card', {})

    const cardTheme = {...defaultTheme, ...userTheme}

    addComponents({
        '.card': {
            maxWidth: cardTheme.maxWidth,
            borderRadius: cardTheme.borderRadius,
            boxShadow: cardTheme.boxShadow,
            overflow: 'hidden',
        },

        '.card-image': {
            display: 'block',
            width: '100%',
        },

        '.card-content': {
            padding: cardTheme.padding,
        },
    })
})