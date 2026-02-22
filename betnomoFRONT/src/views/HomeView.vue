<script setup lang="ts">
const features = [
  {
    title: 'Bolões Garantidos',
    emoji: '🎯',
    description: 'Participe dos bolões agora e garanta sua chance de ganhar a cada rodada.'
  },
  {
    title: 'Sempre um Vencedor',
    emoji: '🏆',
    description: 'Em todas as rodadas pelo menos 1 vai ganhar. Suas chances são reais!'
  },
  {
    title: 'Ficha Grátis',
    emoji: '🎁',
    description: 'Crie sua conta agora e ganhe 1 ficha grátis para começar a jogar sem gastar nada.'
  }
]
</script>

<template>
  <div class="min-h-screen">
    <section class="relative min-h-screen flex items-center justify-center overflow-hidden">

      <div
        class="absolute inset-0 bg-cover bg-center bg-no-repeat"
        style="background-image: url('/src/assets/images/homeimg.png')"
      />

      <div class="absolute inset-0 bg-black/60" />
      <div class="absolute inset-0 bg-gradient-to-t from-hon-dark via-transparent to-transparent" />

      <div class="relative z-10 text-center px-4 animate-fadeIn">
        <h1 class="font-display text-4xl md:text-6xl font-bold text-white mb-4 tracking-wide leading-tight">
          TENTE A SORTE AGORA COM A
          <span class="text-hon-gold"> BETNOMO</span>
        </h1>
        <p class="text-hon-text text-lg md:text-xl max-w-xl mx-auto mb-10 font-light">
          Participe dos bolões agora e ganhe 1 ficha grátis
        </p>

        <div class="flex flex-col sm:flex-row gap-4 justify-center">
          <button
            class="btn-primary px-10 py-4 text-base rounded-lg"
            style="width: auto"
          >
            Jogar Grátis
          </button>
          <RouterLink
            to="/about"
            class="inline-flex items-center justify-center px-10 py-4 rounded-lg border border-hon-border text-hon-text hover:text-white hover:border-white/30 transition-all duration-200 font-semibold text-sm tracking-widest uppercase"
          >
            Saiba Mais
          </RouterLink>
        </div>
      </div>
    </section>

    <section class="py-24 px-4">
      <div class="max-w-6xl mx-auto text-center">
        <h2 class="hon-section-title mb-4">Por que jogar na BETNOMO?</h2>
        <p class="text-hon-muted text-base max-w-2xl mx-auto mb-16">
          Aqui em todas as rodadas pelo menos 1 vai ganhar.
        </p>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <div
            v-for="feature in features"
            :key="feature.title"
            class="bg-hon-card border border-hon-border rounded-xl p-6 hover:border-hon-green/30 transition-all duration-300 group"
          >
            <div class="w-12 h-12 rounded-lg bg-hon-green/10 border border-hon-green/20 flex items-center justify-center mb-4 mx-auto group-hover:bg-hon-green/20 transition-colors">
              <span class="text-xl">{{ feature.emoji }}</span>
            </div>
            <h3 class="font-display text-white font-semibold text-lg mb-2">{{ feature.title }}</h3>
            <p class="text-hon-muted text-sm leading-relaxed">{{ feature.description }}</p>
          </div>
        </div>
      </div>
    </section>
  </div>
</template>
