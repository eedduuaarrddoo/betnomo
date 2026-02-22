/** @type {import('tailwindcss').Config} */
export default {
  content: [
    './index.html',
    './src/**/*.{vue,js,ts,jsx,tsx}'
  ],
  theme: {
    extend: {
      colors: {
        hon: {
          dark: '#0a0d0f',
          darker: '#060809',
          card: '#111518',
          border: '#1e2428',
          green: '#3dd68c',
          'green-dark': '#2ab373',
          gold: '#f0a500',
          'gold-dark': '#c88400',
          text: '#c8d3da',
          muted: '#6b7b8a',
        }
      },
      fontFamily: {
        display: ['Cinzel', 'Georgia', 'serif'],
        body: ['Exo 2', 'sans-serif'],
      },
      backgroundImage: {
        'hero-gradient': 'linear-gradient(180deg, rgba(10,13,15,0) 0%, rgba(10,13,15,0.8) 60%, #0a0d0f 100%)',
      },
      boxShadow: {
        'green-glow': '0 0 20px rgba(61, 214, 140, 0.3)',
        'gold-glow': '0 0 20px rgba(240, 165, 0, 0.3)',
      },
      keyframes: {
        fadeIn: {
          '0%': { opacity: '0', transform: 'translateY(-8px)' },
          '100%': { opacity: '1', transform: 'translateY(0)' }
        },
        modalIn: {
          '0%': { opacity: '0', transform: 'scale(0.95) translateY(-12px)' },
          '100%': { opacity: '1', transform: 'scale(1) translateY(0)' }
        },
        overlayIn: {
          '0%': { opacity: '0' },
          '100%': { opacity: '1' }
        }
      },
      animation: {
        fadeIn: 'fadeIn 0.3s ease forwards',
        modalIn: 'modalIn 0.3s cubic-bezier(0.16, 1, 0.3, 1) forwards',
        overlayIn: 'overlayIn 0.25s ease forwards',
      }
    }
  },
  plugins: []
}
