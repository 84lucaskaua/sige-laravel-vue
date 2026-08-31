<template>
  <!--
    Componente raiz da aplicação.
    O RouterView renderiza a página correspondente à URL atual.
  -->
  <RouterView />
 
  <!-- Widget global de acessibilidade (aumentar/diminuir fonte) -->
  <WidgetAcessibilidade />
</template>
 
<script setup>
import { RouterView } from 'vue-router'
import WidgetAcessibilidade from '@/componentes/ui/WidgetAcessibilidade.vue'
</script>
 