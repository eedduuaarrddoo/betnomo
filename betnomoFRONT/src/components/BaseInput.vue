<script setup lang="ts">
import { computed } from 'vue'

const props = withDefaults(defineProps<{
  modelValue: string
  label?: string
  placeholder?: string
  type?: string
  required?: boolean
  error?: string
  id?: string
}>(), {
  type: 'text',
  required: false
})

const emit = defineEmits<{
  'update:modelValue': [value: string]
}>()

const inputId = computed(() => props.id || `input-${Math.random().toString(36).slice(2, 7)}`)
</script>

<template>
  <div class="flex flex-col gap-1.5">
    <label
      v-if="label"
      :for="inputId"
      class="text-sm font-medium text-hon-text"
    >
      {{ label }}
      <span v-if="required" class="text-hon-gold ml-0.5">*</span>
    </label>
    <input
      :id="inputId"
      :type="type"
      :placeholder="placeholder"
      :value="modelValue"
      class="input-field"
      @input="emit('update:modelValue', ($event.target as HTMLInputElement).value)"
    />
    <span v-if="error" class="text-xs text-red-400">{{ error }}</span>
  </div>
</template>
