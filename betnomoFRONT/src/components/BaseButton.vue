<script setup lang="ts">
withDefaults(defineProps<{
  variant?: 'primary' | 'outline' | 'ghost'
  loading?: boolean
  disabled?: boolean
  type?: 'button' | 'submit' | 'reset'
  fullWidth?: boolean
}>(), {
  variant: 'primary',
  loading: false,
  disabled: false,
  type: 'button',
  fullWidth: false
})
</script>

<template>
  <button
    :type="type"
    :disabled="disabled || loading"
    :class="[
      'relative inline-flex items-center justify-center font-body font-semibold text-sm tracking-widest uppercase transition-all duration-200 rounded-lg px-5 py-3 cursor-pointer',
      fullWidth ? 'w-full' : '',
      variant === 'primary' && 'btn-primary',
      variant === 'outline' && 'border border-hon-green text-hon-green hover:bg-hon-green hover:text-hon-darker hover:shadow-green-glow bg-transparent',
      variant === 'ghost' && 'text-hon-text hover:text-white hover:bg-white/5 bg-transparent border-none',
      (disabled || loading) && 'opacity-50 cursor-not-allowed pointer-events-none'
    ]"
  >
    <span v-if="loading" class="absolute inset-0 flex items-center justify-center">
      <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
      </svg>
    </span>
    <span :class="loading ? 'opacity-0' : ''">
      <slot />
    </span>
  </button>
</template>
