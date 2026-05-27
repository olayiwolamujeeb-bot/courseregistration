<template>
  <div class="flex h-screen items-center justify-center bg-gradient-to-br from-sky-50 to-slate-100 px-4">
    <div class="w-full max-w-md rounded-3xl bg-white p-8 shadow-2xl shadow-sky-200/30">
      <h1 class="text-center text-3xl font-bold text-slate-950">Course Registration Portal</h1>

      <div v-if="!selectedRole" class="mt-8 space-y-4">
        <button
          type="button"
          :disabled="busy"
          class="w-full rounded-lg bg-sky-600 px-6 py-3 text-sm font-medium text-white transition hover:bg-sky-700 disabled:cursor-not-allowed disabled:bg-slate-400"
          @click="selectRole('admin')"
        >
          Admin Login
        </button>
        <button
          type="button"
          :disabled="busy"
          class="w-full rounded-lg border border-slate-300 bg-white px-6 py-3 text-sm font-medium text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:border-slate-300 disabled:text-slate-400"
          @click="selectRole('student')"
        >
          Student Login
        </button>
      </div>

      <div v-else class="mt-8 space-y-5">
        <button
          type="button"
          :disabled="busy"
          class="w-full text-sm text-slate-600 underline transition hover:text-slate-900 disabled:text-slate-400"
          @click="changeRole"
        >
          Back to role selection
        </button>

        <form class="space-y-4" @submit.prevent="handleLogin">
          <div>
            <label class="mb-1 block text-sm font-medium text-slate-700" for="login-name">Name</label>
            <input
              id="login-name"
              v-model="loginForm.name"
              :disabled="busy"
              type="text"
              placeholder="Enter your name"
              class="w-full rounded-lg border border-slate-300 bg-slate-50 px-4 py-2 text-sm text-slate-900 outline-none transition focus:border-sky-500 focus:ring-2 focus:ring-sky-200 disabled:cursor-not-allowed disabled:bg-slate-100"
              required
            />
          </div>

          <div v-if="selectedRole === 'admin'">
            <label class="mb-1 block text-sm font-medium text-slate-700" for="login-password">Password</label>
            <input
              id="login-password"
              v-model="loginForm.password"
              :disabled="busy"
              type="password"
              placeholder="password"
              class="w-full rounded-lg border border-slate-300 bg-slate-50 px-4 py-2 text-sm text-slate-900 outline-none transition focus:border-sky-500 focus:ring-2 focus:ring-sky-200 disabled:cursor-not-allowed disabled:bg-slate-100"
              required
            />
          </div>

          <div v-if="selectedRole === 'student'" class="space-y-4">
            <div>
              <label class="mb-1 block text-sm font-medium text-slate-700" for="login-level">Level</label>
              <select
                id="login-level"
                v-model="loginForm.level"
                :disabled="busy"
                class="w-full rounded-lg border border-slate-300 bg-slate-50 px-4 py-2 text-sm text-slate-900 outline-none transition focus:border-sky-500 focus:ring-2 focus:ring-sky-200 disabled:cursor-not-allowed disabled:bg-slate-100"
                required
              >
                <option value="" disabled>Select level</option>
                <option value="100">100</option>
                <option value="200">200</option>
                <option value="300">300</option>
              </select>
            </div>
          </div>

          <p v-if="loginError || serverError" class="text-sm text-red-600">{{ loginError || serverError }}</p>

          <button
            type="submit"
            :disabled="busy"
            class="w-full rounded-lg bg-sky-600 px-6 py-2.5 text-sm font-medium text-white transition hover:bg-sky-700 disabled:cursor-not-allowed disabled:bg-slate-400"
          >
            {{ busy ? 'Please wait...' : 'Login' }}
          </button>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'

defineProps({
  busy: {
    type: Boolean,
    default: false,
  },
  serverError: {
    type: String,
    default: '',
  },
})

const emit = defineEmits(['login-success'])

const selectedRole = ref(null)
const loginError = ref('')
const loginForm = ref({
  name: '',
  password: '',
  level: '',
})

const selectRole = (role) => {
  selectedRole.value = role
  loginError.value = ''
  loginForm.value = { name: '', password: '', level: '' }
}

const changeRole = () => {
  selectedRole.value = null
  loginError.value = ''
  loginForm.value = { name: '', password: '', level: '' }
}

const handleLogin = () => {
  loginError.value = ''
  const name = loginForm.value.name.trim()

  if (selectedRole.value === 'admin') {
    if (!name || !loginForm.value.password) {
      loginError.value = 'Enter your admin name and password.'
      return
    }

    emit('login-success', {
      role: 'admin',
      credentials: {
        name,
        password: loginForm.value.password,
      },
    })
    return
  }

  if (!name || !loginForm.value.level) {
    loginError.value = 'Enter your name and select a level.'
    return
  }

  emit('login-success', {
    role: 'student',
    student: {
      name,
      level: loginForm.value.level,
    },
  })
}
</script>
