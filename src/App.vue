<template>
  <div v-if="!loggedInRole" class="min-h-screen">
    <LoginCard :busy="isSavingStudent" @login-success="handleLoginSuccess" />
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
const isSavingCourse = ref(false)
const isSavingStudent = ref(false)
const isSavingRegistration = ref(false)
const isUsingFallback = ref(false)
let localIdCounter = 0

const createLocalStudent = (student) => ({
  id: Date.now() + (++localIdCounter),
  name: student.name,
  level: student.level,
  email: student.email || '',
  matric: student.matric || '',
})

const createLocalCourse = (course) => ({
  id: Date.now(),
  title: course.title,
  code: course.code,
  level: course.level,
  semester: course.semester,
  unit: Number(course.unit) || 0,
  type: course.type,
})

const createLocalRegistration = (studentId, courseIds) => ({
  id: Date.now(),
  studentId,
  courseIds: [...courseIds],
  createdAt: new Date().toISOString(),
})

const switchToLocalMode = () => {
  isUsingFallback.value = true
  errorMessage.value = ''
}

onMounted(() => {
  loadInitialData()
})

const loadInitialData = async () => {
  const data = await api.bootstrap()
  courses.value = data.courses
  students.value = data.students
  registrations.value = data.registrations

  if (data.usingFallback) {
    switchToLocalMode()
    return
  }

  isUsingFallback.value = false
}

const handleLoginSuccess = async (payload) => {
  errorMessage.value = ''

  if (payload.role === 'admin') {
    loggedInRole.value = 'admin'
    return
  }

  if (payload.role === 'student') {
    const sessionStudent = createLocalStudent(payload.student)
    currentStudent.value = sessionStudent
    loggedInRole.value = 'student'

    const existingStudentIndex = students.value.findIndex((student) => student.id === sessionStudent.id)
    if (existingStudentIndex > -1) {
      students.value.splice(existingStudentIndex, 1, sessionStudent)
    } else {
      students.value.unshift(sessionStudent)
    }

    isSavingStudent.value = true

    try {
      if (!isUsingFallback.value) {
        const savedStudent = await api.createStudent(payload.student)
        currentStudent.value = savedStudent

        const localStudentIndex = students.value.findIndex((student) => student.id === sessionStudent.id)
        if (localStudentIndex > -1) {
          students.value.splice(localStudentIndex, 1, savedStudent)
        } else {
          students.value.unshift(savedStudent)
        }
      }
    } catch (error) {
      switchToLocalMode()
    } finally {
      isSavingStudent.value = false
    }
  }
}

const addCourse = async (course) => {
  isSavingCourse.value = true
  errorMessage.value = ''

  try {
    const savedCourse = isUsingFallback.value
      ? createLocalCourse(course)
      : await api.createCourse(course)
    courses.value.unshift(savedCourse)
    errorMessage.value = ''
  } catch (error) {
    const savedCourse = createLocalCourse(course)
    courses.value.unshift(savedCourse)
    switchToLocalMode()
  } finally {
    isSavingCourse.value = false
  }
}

const updateCourse = async ({ id, payload }) => {
  isSavingCourse.value = true
  errorMessage.value = ''

  try {
    const updatedCourse = isUsingFallback.value
      ? { id, ...payload, unit: Number(payload.unit) || 0 }
      : await api.updateCourse(id, payload)
    const courseIndex = courses.value.findIndex((course) => course.id === id)

    if (courseIndex > -1) {
      courses.value.splice(courseIndex, 1, updatedCourse)
    }
    errorMessage.value = ''
  } catch (error) {
    const courseIndex = courses.value.findIndex((course) => course.id === id)
    if (courseIndex > -1) {
      courses.value.splice(courseIndex, 1, { id, ...payload, unit: Number(payload.unit) || 0 })
    }
    switchToLocalMode()
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
    const savedRegistration = isUsingFallback.value
      ? createLocalRegistration(currentStudent.value.id, courseIds)
      : await api.createRegistration({
          student_id: currentStudent.value.id,
          course_ids: courseIds,
        })

    registrations.value.unshift(savedRegistration)
    errorMessage.value = ''
    if (typeof onSuccess === 'function') {
      onSuccess(savedRegistration)
    }
  } catch (error) {
    const savedRegistration = createLocalRegistration(currentStudent.value.id, courseIds)
    registrations.value.unshift(savedRegistration)
    switchToLocalMode()
    if (typeof onSuccess === 'function') {
      onSuccess(savedRegistration)
    }
  } finally {
    isSavingRegistration.value = false
  }
}

const deleteCourse = async (courseId) => {
  isSavingCourse.value = true
  errorMessage.value = ''

  try {
    if (!isUsingFallback.value) {
      await api.deleteCourse(courseId)
    }
    courses.value = courses.value.filter((course) => course.id !== courseId)
    registrations.value = registrations.value.map((registration) => ({
      ...registration,
      courseIds: registration.courseIds.filter((id) => id !== courseId),
    }))
    errorMessage.value = ''
  } catch (error) {
    courses.value = courses.value.filter((course) => course.id !== courseId)
    registrations.value = registrations.value.map((registration) => ({
      ...registration,
      courseIds: registration.courseIds.filter((id) => id !== courseId),
    }))
    switchToLocalMode()
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
