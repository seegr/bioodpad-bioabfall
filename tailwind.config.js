const plugin = require('tailwindcss/plugin');

const screens = {
  "lg-mobile": 480,
  tablet: 768,
  "lg-tablet": 1024,
  desktop: 1280,
  "lg-desktop": 1441
}

const pixels = [
  1, 2, 3, 4, 5, 6, 8, 10, 12, 14, 16, 18, 20, 22, 24, 26, 28, 30, 38, 32, 34, 36, 40, 42, 44, 46,
  48, 50, 54, 56, 58, 60, 64, 68, 70, 74, 72, 76, 80, 86, 94, 96, 124, 128, 146, 172, 176, 192, 196, 200, 256,
]


const customColors = {
  white: {
    DEFAULT: "#FFFFFF",
    dark: "#e1e1e1"
  },
  black: "#000000",
  beige: {
    DEFAULT: "#FEFAE0",
    light: "#fcf8f1",
    lighter: "#fffaf9",
  },
  green: {
    DEFAULT: "#708a3a",
    light: "#A9B388",
    lighter: "#cbd0bc",
    dark: "#5c723e",
    darker: "#2f3a1f",
  },
  brown: {
    DEFAULT: "#B99470",
    light: "#d7cfc1",
    dark: "#7b5937",
  },
  orange: {
    DEFAULT: "#c48354"
  },
  grey: {
    DEFAULT: "#1b1b1b",
    light: "#333333"
  }
}

const borderRadius = {
  DEFAULT: "8px",
  full: '100%',
  '24px': '24px',
  '12px': '12px',
  '8px': '8px',
  '4px': '4px',
  '3px': '3px',
  '2px': '2px',
}

module.exports = {
  content: [
    "./app/modules/Front/**/*.(latte|js)",
    "./dev/front/**/*.(js|scss|vue)",
    "./app/modules/Admin/**/*.latte",
    "./app/modules/Admin/**/*.php",
    './node_modules/flowbite/**/*.js',
  ],
  safelist: generateSafeListClasses(),
  theme: {
    fontFamily: {
      primary: ["Open Sans, sans-serif"],
      secondary: ["Nunito, sans-serif"],
    },
    fontSize: {
      "12px": [pxToRem(12), "normal"],
      "14px": [pxToRem(14), "normal"],
      "16px": [pxToRem(16), "normal"],
      "18px": [pxToRem(18), "normal"],
      "24px": [pxToRem(24), "normal"],
      "26px": [pxToRem(26), "normal"],
      "28px": [pxToRem(28), "normal"],
      "30px": [pxToRem(30), "normal"],
      "32px": [pxToRem(32), "normal"],
      "36px": [pxToRem(36), "normal"],
      "38px": [pxToRem(38), "normal"],
      "42px": [pxToRem(42), "normal"],
      "40px": [pxToRem(40), "normal"],
      "44px": [pxToRem(44), "normal"],
      "48px": [pxToRem(48), "normal"],
      "56px": [pxToRem(56), "normal"],
    },
    screens: Object.fromEntries(
      Object.entries(screens).map(([key, pixels]) => [key, pxToRem(pixels)])
    ),
    spacing: Object.assign(createSizeObject(pixels), {
      0: "0px",
    }),
    extend: {
      colors: customColors,
      aspectRatio: {
        '1/1': '1 / 1',
        '1/2': '1 / 2',
        '2/1': '2 / 1',
        '2/3': '2 / 3',
        '3/2': '3 / 2',
        '4/3': '4 / 3',
        '16/9': '16 / 9'
      },
      backgroundImage: {
        'green-gradient': 'linear-gradient(0deg, #708a3a 0%, #708a3a60 100%)',
        'green-dark-gradient': 'linear-gradient(0deg, #5c723e 0%, #5c723e60 100%)',
        'brown-gradient': 'linear-gradient(0deg, #7b5937 70%, #7b593760 100%)'
      }
    },
    borderRadius,
    borderWidth: {
      DEFAULT: '1px',
      '2px': '2px',
      '4px': '4px',
    },
  },
  corePlugins: {
    container: false,
  },
  plugins: [
    require("@tailwindcss/aspect-ratio"),
    require('flowbite/plugin'),
    plugin(({ matchUtilities, theme }) => {
      matchUtilities(
        {
          size: (value) => {
            const size = value
            return {
              width: size,
              height: size
            };
          },
        },
        { values: theme('spacing'), type: 'any' }
      );
    }),
  ],
}

function pxToRem(pixels) {
  return `${pixels / 16}rem`
}

function createSizeObject(values) {
  const sizes = {}

  values.forEach((value) => {
    sizes[`${value}px`] = `${value}px`
  })

  return sizes
}

function generateSafeListClasses() {
  const colorClasses = [];

  Object.keys(customColors).forEach(color => {
    if (typeof customColors[color] === 'object' && !Array.isArray(customColors[color])) {
      Object.keys(customColors[color]).forEach(shade => {
        if (shade === 'DEFAULT') {
          colorClasses.push(`bg-${color}`);
          colorClasses.push(`text-${color}`);
          colorClasses.push(`border-${color}`);
        } else {
          colorClasses.push(`bg-${color}-${shade}`);
          colorClasses.push(`text-${color}-${shade}`);
          colorClasses.push(`border-${color}-${shade}`);
        }
      });
    } else {
      colorClasses.push(`bg-${color}`);
      colorClasses.push(`text-${color}`);
      colorClasses.push(`border-${color}`);
    }
  });


  const radiusClasses = []
  Object.keys(borderRadius).forEach(radius => {
    if (radius === 'DEFAULT') return
    radiusClasses.push(`rounded-${radius}`)
  })

  return [...radiusClasses, ...colorClasses];
}