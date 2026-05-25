<template>
  <div class="w-full max-w-2xl rounded-4xl bg-white p-7 shadow-xl shadow-slate-200/80">
    <div class="space-y-3">
      <p class="text-sm font-semibold uppercase tracking-[0.35em] text-sky-600">Student registration</p>
      <h2 class="text-2xl font-semibold text-slate-950">Enter your details</h2>
      <p class="text-sm text-slate-600">Provide name and level to see available courses.</p>
    </div>

    <form class="mt-6 space-y-5" @submit.prevent="submitStudent">
      <div class="grid gap-2">
        <label class="text-sm font-medium text-slate-700" for="name">Full Name</label>
        <input id="name" v-model="name" placeholder="John Doe" required class="w-full rounded-3xl border border-slate-300 bg-slate-50 px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-sky-500 focus:ring-2 focus:ring-sky-200" />
      </div>

      <div class="grid gap-2 sm:grid-cols-2 sm:gap-4">
        <div class="grid gap-2">
          <label class="text-sm font-medium text-slate-700" for="level">Level</label>
          <select id="level" v-model="level" required class="w-full rounded-3xl border border-slate-300 bg-slate-50 px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-sky-500 focus:ring-2 focus:ring-sky-200">
            <option disabled value="">Choose level</option>
            <option value="100">100</option>
            <option value="200">200</option>
            <option value="300">300</option>
          </select>
        </div>

        <div class="grid gap-2">
          <label class="text-sm font-medium text-slate-700" for="email">Email (optional)</label>
          <input id="email" type="email" v-model="email" placeholder="john@example.com" class="w-full rounded-3xl border border-slate-300 bg-slate-50 px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-sky-500 focus:ring-2 focus:ring-sky-200" />
        </div>
      </div>

      <div class="grid gap-2">
        <label class="text-sm font-medium text-slate-700" for="matric">Matric Number (optional)</label>
        <input id="matric" v-model="matric" placeholder="MAT123456" class="w-full rounded-3xl border border-slate-300 bg-slate-50 px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-sky-500 focus:ring-2 focus:ring-sky-200" />
      </div>

      <button type="submit" class="inline-flex items-center justify-center rounded-full bg-sky-600 px-7 py-3 text-sm font-semibold text-white shadow-lg shadow-sky-200/50 transition hover:bg-sky-700">Continue</button>
    </form>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { defineEmits } from 'vue'

const emit = defineEmits(['submit-student'])

const name = ref('')
const level = ref('')
const email = ref('')
const matric = ref('')

const submitStudent = () => {
  emit('submit-student', {
    name: name.value.trim(),
    level: level.value,
    email: email.value.trim() || undefined,
    matric: matric.value.trim() || undefined,
  })
}
</script>
