// ============================================================
// Configuração do Tailwind CSS
// Diz ao Tailwind quais arquivos ele deve escanear para gerar
// apenas as classes CSS que são realmente usadas no projeto
// ============================================================

/** @type {import('tailwindcss').Config} */
export default {
  content: [
    './index.html',
    './src/**/*.{vue,js,ts}',  // Todos os arquivos Vue e JS na pasta src
  ],
  theme: {
    extend: {},
  },
  plugins: [],
}
