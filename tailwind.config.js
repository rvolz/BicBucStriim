module.exports = {
  purge: [
    './templates/*.html',
    './templates/*.twig'
  ],
  darkMode: 'media', // or 'media' or 'class'
  theme: {
    container: {
      center: true,
      padding: '2rem',
    },
    truncate: {
      lines: {
        2: '2'
      }
    },
    extend: {
        // generated with https://tailwind.ink/ for brand color #8494AD aka .bg-manatee-500
        colors: theme => ({
          bgl: '#f6f9f9', /* background light: manatee-50 */
          bgd: '#293241', /* background dark:  manatee-900 */
          txtl: '#000000', /* text */
          txtd: '#ffffff', /* text: dark */
          hover1: '#e96d51', /* text hover: coral-500 */
          ring1: '#e96d51', /* ring: coral-500 */
          hbgl: '#dde8ea', /* header background: manatee-200 */
          hbgd: '#3f516a', /* header background dark: manatee-700 */
          htxtl: '#000000', /* header text */
          htxtd: '#ffffff', /* header text: dark */
          btn1: '#233257', /* button primary: steel-900 */
          btn1d: '#f5f9fa', /* button primary dark: steel-50 */
          btn2: '#c3d5dc', /* button secondary: manatee-300 */
          btn2d: '#4b6887', /* button secondary dark: manatee-600 */

          steel: {
            '50':  '#f5f9fa',
            '100': '#eaf4f8',
            '200': '#d1e7f1',
            '300': '#b1d3ea',
            '400': '#7cb1e1',
            '500': '#4c89d4',
            '600': '#3865bc',
            '700': '#324f98',
            '800': '#2b3d70',
            '900': '#233257',
          },
          royalblue: {
            '50':  '#f6f8fb',
            '100': '#eef2f9',
            '200': '#dbe0f4',
            '300': '#c4c7f0',
            '400': '#a29feb',
            '500': '#7d73e5',
            '600': '#5f50d4',
            '700': '#4b3eb1',
            '800': '#3a3184',
            '900': '#2f2965',
          },
          orchid: {
            '50':  '#f7f8fa',
            '100': '#f2f1f8',
            '200': '#e3def3',
            '300': '#d2c4ee',
            '400': '#bb99e6',
            '500': '#9f6ddd',
            '600': '#7d4ac9',
            '700': '#5f3aa5',
            '800': '#472e7a',
            '900': '#38265d',
          },
          hotpink: {
            '50':  '#faf9f9',
            '100': '#f8f1f5',
            '200': '#f1dceb',
            '300': '#ebbfde',
            '400': '#e48fc8',
            '500': '#dd63ad',
            '600': '#c2418a',
            '700': '#92326d',
            '800': '#6a2852',
            '900': '#512140',
          },
          blush: {
            '50':  '#fbf9f8',
            '100': '#f9f2f3',
            '200': '#f4dfe4',
            '300': '#eec2cf',
            '400': '#e994aa',
            '500': '#e36882',
            '600': '#ca455d',
            '700': '#9b344c',
            '800': '#70293d',
            '900': '#552232',
          },
          coral: {
            '50':  '#fbf9f7',
            '100': '#faf3ef',
            '200': '#f6e1d9',
            '300': '#f2c6b7',
            '400': '#ed9881',
            '500': '#e96d51',
            '600': '#d34935',
            '700': '#a53730',
            '800': '#792b2b',
            '900': '#5c2325',
          },
          chocolate: {
            '50':  '#fbf9f7',
            '100': '#faf4ed',
            '200': '#f4e5d4',
            '300': '#eecdae',
            '400': '#e6a372',
            '500': '#dd7942',
            '600': '#c0542a',
            '700': '#923f28',
            '800': '#6b3126',
            '900': '#512822',
          },
          tangerine: {
            '50':  '#faf9f7',
            '100': '#f9f5ed',
            '200': '#f3e7d3',
            '300': '#ebd0ac',
            '400': '#dfa970',
            '500': '#d28040',
            '600': '#b15a29',
            '700': '#854427',
            '800': '#613525',
            '900': '#4a2a21',
          },
          shadow: {
            '50':  '#f9f9f8',
            '100': '#f5f5f1',
            '200': '#e9e9de',
            '300': '#dad5c3',
            '400': '#bcb295',
            '500': '#988c66',
            '600': '#746746',
            '700': '#584f3c',
            '800': '#433c34',
            '900': '#34302c',
          },
          manatee: {
            '50':  '#f6f9f9',
            '100': '#eff5f5',
            '200': '#dde8ea',
            '300': '#c3d5dc',
            '400': '#95b3c5',
            '500': '#8494ad',
            '600': '#4b6887',
            '700': '#3f516a',
            '800': '#333e52',
            '900': '#293241',
          },
        }),
        backgroundColor: theme => ({
          ...theme('colors'),
        }),
    },
  },
  variants: {
    extend: {
      opacity: ['disabled']
    },
  },
  plugins: [
      require('@tailwindcss/forms'),
      require('tailwindcss-truncate-multiline')(),
  ],
}
