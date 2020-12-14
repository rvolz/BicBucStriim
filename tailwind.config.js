module.exports = {
  purge: [
    './templates/*.html',
    './templates/*.twig'
  ],
  darkMode: false, // or 'media' or 'class'
  theme: {
    extend: {
      extend: {
        card: theme => ({
          maxWidth: '20rem'
        })
      },
    },
  },
  variants: {
    extend: {},
  },
  plugins: [
      require('@tailwindcss/forms'),
      require('./assets/style/plugins/simple-card')
  ],
}
