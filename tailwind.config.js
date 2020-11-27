module.exports = {
    purge: ["./public/**/*.html"],
    theme: {
        extend: {},
    },
    variants: {},
    plugins: [
        require('@tailwindcss/forms'),
    ],
};
