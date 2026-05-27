<template>
  <div v-if="isBootstrapping" class="flex min-h-screen items-center justify-center bg-slate-50 px-4">
    <div class="w-full max-w-lg rounded-3xl bg-white p-8 text-center shadow-xl shadow-slate-200/80">
      <p class="text-sm uppercase tracking-[0.3em] text-sky-600">Course Registration</p>
      <h1 class="mt-3 text-3xl font-semibold text-slate-950">Connecting to backend</h1>
      <p class="mt-4 text-slate-600">
        The application is loading live data from the server.
      </p>
    </div>
  </div>

  <div v-else-if="initializationError" class="flex min-h-screen items-center justify-center bg-slate-50 px-4">
    <div class="w-full max-w-xl rounded-3xl bg-white p-8 shadow-xl shadow-slate-200/80">
      <p class="text-sm uppercase tracking-[0.3em] text-rose-600">Backend Required</p>
      <h1 class="mt-3 text-3xl font-semibold text-slate-950">The backend is unavailable</h1>
      <p class="mt-4 text-slate-600">
        {{ initializationError }}
      </p>
      <button
        type="button"
        class="mt-6 rounded-full bg-slate-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-800"
        @click="loadInitialData"
      >
        Retry connection
      </button>
    </div>
  </div>

  <div v-else-if="!loggedInRole" class="min-h-screen">
    <LoginCard :busy="isAuthenticating" :server-error="errorMessage" @login-success="handleLoginSuccess" />
  </div>

  <div v-else class="min-h-screen bg-slate-50 text-slate-900">
    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 sm:py-8 lg:px-8 lg:py-10">
      <header class="flex flex-col gap-5 rounded-[2rem] bg-white/90 p-5 shadow-lg shadow-slate-200/80 backdrop-blur-xl md:flex-row md:items-center md:justify-between md:p-6">
        <div>
          <p class="text-sm uppercase tracking-[0.3em] text-sky-600">Course Registration</p>
          <h1 class="mt-2 text-2xl font-semibold tracking-tight text-slate-950 sm:text-3xl md:text-4xl">Course Registration System</h1>
        </div>

        <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center sm:justify-between">
          <p class="text-sm text-slate-700">
            Signed in as
            <span class="font-semibold text-slate-950">
              {{ loggedInRole === 'admin' ? 'Admin' : currentStudent?.name || 'Student' }}
            </span>
          </p>
          <button
            type="button"
            class="w-full rounded-full bg-slate-200 px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-300 sm:w-auto"
            @click="logout"
          >
            Logout
          </button>
        </div>
      </header>

      <main class="mt-6 sm:mt-8 lg:mt-10">
        <div v-if="errorMessage" class="mb-6 rounded-3xl border border-rose-200 bg-rose-50 px-5 py-4 text-sm text-rose-700">
          {{ errorMessage }}
        </div>

        <div v-if="loggedInRole === 'admin'">
          <AdminDashboard
            :busy="isSavingCourse"
            :courses="courses"
            :registrations="registrations"
            :students="students"
            @add-course="addCourse"
            @update-course="updateCourse"
            @delete-course="deleteCourse"
          />
        </div>

        <section v-else class="flex justify-center">
          <StudentPage
            v-if="currentStudent"
            :busy="isSavingRegistration"
            :student="currentStudent"
            :courses="courses"
            @register="saveRegistration"
          />
        </section>
      </main>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import AdminDashboard from './components/AdminDashboard.vue'
import LoginCard from './components/LoginCard.vue'
import StudentPage from './components/StudentPage.vue'
import { api } from './services/api'

const loggedInRole = ref(null)
const currentStudent = ref(null)
const courses = ref([])
const students = ref([])
const registrations = ref([])
const errorMessage = ref('')
const initializationError = ref('')
const isBootstrapping = ref(true)
const isSavingCourse = ref(false)
const isAuthenticating = ref(false)
const isSavingRegistration = ref(false)

onMounted(() => {
  loadInitialData()
})

const loadInitialData = async () => {
  errorMessage.value = ''
  initializationError.value = ''
  isBootstrapping.value = true

  try {
    const data = await api.bootstrap()
    courses.value = data.courses
    students.value = data.students
    registrations.value = data.registrations
  } catch (error) {
    courses.value = []
    students.value = []
    registrations.value = []
    initializationError.value = error.message || 'Unable to load data from the server.'
  } finally {
    isBootstrapping.value = false
  }
}

const handleLoginSuccess = async (payload) => {
  errorMessage.value = ''

  if (payload.role === 'admin') {
    isAuthenticating.value = true

    try {
      await api.loginAdmin(payload.credentials)
      loggedInRole.value = 'admin'
    } catch (error) {
      errorMessage.value = error.message || 'Admin login failed.'
    } finally {
      isAuthenticating.value = false
    }

    return
  }

  if (payload.role === 'student') {
    isAuthenticating.value = true

    try {
      const savedStudent = await api.loginStudent(payload.student)
      currentStudent.value = savedStudent
      loggedInRole.value = 'student'
      const existingStudentIndex = students.value.findIndex((student) => student.id === savedStudent.id)

      if (existingStudentIndex > -1) {
        students.value.splice(existingStudentIndex, 1, savedStudent)
      } else {
        students.value.unshift(savedStudent)
      }
    } catch (error) {
      errorMessage.value = error.message || 'Student login failed.'
    } finally {
      isAuthenticating.value = false
    }
  }
}

const addCourse = async (course) => {
  isSavingCourse.value = true
  errorMessage.value = ''

  try {
    const savedCourse = await api.createCourse(course)
    courses.value.unshift(savedCourse)
  } catch (error) {
    errorMessage.value = error.message || 'Unable to create course.'
  } finally {
    isSavingCourse.value = false
  }
}

const updateCourse = async ({ id, payload }) => {
  isSavingCourse.value = true
  errorMessage.value = ''

  try {
    const updatedCourse = await api.updateCourse(id, payload)
    const courseIndex = courses.value.findIndex((course) => course.id === id)

    if (courseIndex > -1) {
      courses.value.splice(courseIndex, 1, updatedCourse)
    }
  } catch (error) {
    errorMessage.value = error.message || 'Unable to update course.'
  } finally {
    isSavingCourse.value = false
  }
}

const saveRegistration = async (payload) => {
  if (!currentStudent.value) return

  const courseIds = Array.isArray(payload) ? payload : payload.courseIds
  const onSuccess = Array.isArray(payload) ? null : payload.onSuccess

  isSavingRegistration.value = true
  errorMessage.value = ''

  try {
    const savedRegistration = await api.createRegistration({
      student_id: currentStudent.value.id,
      course_ids: courseIds,
    })

    registrations.value.unshift(savedRegistration)
    if (typeof onSuccess === 'function') {
      onSuccess(savedRegistration)
    }
  } catch (error) {
    errorMessage.value = error.message || 'Unable to save registration.'
  } finally {
    isSavingRegistration.value = false
  }
}

const deleteCourse = async (courseId) => {
  isSavingCourse.value = true
  errorMessage.value = ''

  try {
    await api.deleteCourse(courseId)
    courses.value = courses.value.filter((course) => course.id !== courseId)
    registrations.value = registrations.value.map((registration) => ({
      ...registration,
      courseIds: registration.courseIds.filter((id) => id !== courseId),
    }))
  } catch (error) {
    errorMessage.value = error.message || 'Unable to delete course.'
  } finally {
    isSavingCourse.value = false
  }
}

const logout = () => {
  loggedInRole.value = null
  currentStudent.value = null
  errorMessage.value = ''
}
</script>
