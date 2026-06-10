// ============================================================
// Ponto de entrada da aplicação Vue.js
//
// Aqui iniciamos o Vue, registramos os plugins (Pinia, Router)
// e montamos a aplicação no HTML.
// ============================================================

import { createApp }  from 'vue'
import { createPinia } from 'pinia'
import App    from './App.vue'
import router from './router'

// Importa o CSS global
import './estilos/global.css'

// Cria a aplicação Vue
const app = createApp(App)

// Registra o Pinia (gerenciador de estado)
app.use(createPinia())

// Registra o Vue Router (sistema de rotas)
app.use(router)

// Monta a aplicação na div #app do index.html
app.mount('#app')
