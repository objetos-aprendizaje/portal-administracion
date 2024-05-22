/** @type {import('tailwindcss').Config} */
export default {
    content: [
        "./resources/**/*.blade.php",
        "./resources/**/*.js",
        "./resources/**/*.vue",
    ],
    theme: {
        extend: {
            colors: {
                primary: "var(--primary-color)",
                secondary: "var(--secondary-color)",
                color_hover: "var(--color-hover)",
                danger: "#E74C3C"
            },
            fontFamily: {
                'roboto-medium': ['roboto-medium', 'sans-serif'],
                'roboto-regular': ['roboto-regular', 'sans-serif'],
                'roboto-bold': ['roboto-bold', 'sans-serif']
            },
        },
    },
    darkMode: 'class',
    container: {
        screens: {
            sm: "100%",
            md: "768px",
            lg: "992px",
            xl: "1200px",
            "2xl": "1600px",
        },
    },
    screens: {
        sm: "480px",
        md: "768px",
        lg: "992px",
        xl: "1200px",
        "2xl": "1600px",
    },
    plugins: [],
};
