<template>
  <section class="grid gap-6">
    <div class="overflow-hidden rounded-32px border border-slate-200 bg-white shadow-lg shadow-slate-200/60">
      <div class="border-b border-slate-200 bg-[linear-gradient(135deg,#f8fafc,white_45%,#e0f2fe)] px-6 py-6">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
          <div class="max-w-2xl">
            <p class="text-xs font-semibold uppercase tracking-[0.35em] text-sky-700">Admin workspace</p>
            <h2 class="mt-3 text-2xl font-semibold tracking-tight text-slate-950">
              {{ editingCourseId ? 'Update course details' : 'Course management' }}
            </h2>
            <p class="mt-2 text-sm leading-6 text-slate-600">
              Add, update, and organize the course catalog with a calmer layout built for day-to-day use.
            </p>
          </div>

          <div class="grid items-stretch gap-3 sm:grid-cols-3">
            <div class="flex h-full flex-col justify-between rounded-2xl border border-slate-200 bg-white/80 px-4 py-3">
              <p class="text-[11px] uppercase tracking-[0.25em] text-slate-500">Courses</p>
              <p class="mt-2 text-2xl font-semibold text-slate-950">{{ courses.length }}</p>
            </div>
            <div class="flex h-full flex-col justify-between rounded-2xl border border-slate-200 bg-white/80 px-4 py-3">
              <p class="text-[11px] uppercase tracking-[0.25em] text-slate-500">Registrations</p>
              <p class="mt-2 text-2xl font-semibold text-slate-950">{{ registrations.length }}</p>
            </div>
            <div class="flex h-full flex-col justify-between rounded-2xl border border-slate-200 bg-white/80 px-4 py-3">
              <p class="text-[11px] uppercase tracking-[0.25em] text-slate-500">Registered students</p>
              <p class="mt-2 text-2xl font-semibold text-slate-950">{{ registeredStudentsCount }}</p>
            </div>
          </div>
        </div>
      </div>

      <div class="px-6 py-6">
        <form class="grid gap-4 lg:grid-cols-6" @submit.prevent="handleSubmit">
          <div class="space-y-2 lg:col-span-2">
            <label class="block text-sm font-medium text-slate-700" for="title">Course Title</label>
            <input id="title" v-model="form.title" placeholder="Physics 101" required class="w-full rounded-xl border border-slate-300 bg-slate-50 px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-sky-500 focus:ring-2 focus:ring-sky-100" />
          </div>

          <div class="space-y-2">
            <label class="block text-sm font-medium text-slate-700" for="code">Course Code</label>
            <input id="code" v-model="form.code" placeholder="PHY101" required class="w-full rounded-xl border border-slate-300 bg-slate-50 px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-sky-500 focus:ring-2 focus:ring-sky-100" />
          </div>

          <div class="space-y-2">
            <label class="block text-sm font-medium text-slate-700" for="level">Level</label>
            <select id="level" v-model="form.level" required class="w-full rounded-xl border border-slate-300 bg-slate-50 px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-sky-500 focus:ring-2 focus:ring-sky-100">
              <option disabled value="">Level</option>
              <option value="100">100</option>
              <option value="200">200</option>
              <option value="300">300</option>
            </select>
          </div>

          <div class="space-y-2">
            <label class="block text-sm font-medium text-slate-700" for="unit">Units</label>
            <input id="unit" v-model.number="form.unit" type="number" min="1" placeholder="3" required class="w-full rounded-xl border border-slate-300 bg-slate-50 px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-sky-500 focus:ring-2 focus:ring-sky-100" />
          </div>

          <div class="space-y-2">
            <label class="block text-sm font-medium text-slate-700" for="semester">Semester</label>
            <select id="semester" v-model="form.semester" required class="w-full rounded-xl border border-slate-300 bg-slate-50 px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-sky-500 focus:ring-2 focus:ring-sky-100">
              <option disabled value="">Semester</option>
              <option value="First Semester">First Semester</option>
              <option value="Second Semester">Second Semester</option>
            </select>
          </div>

          <div class="space-y-2">
            <label class="block text-sm font-medium text-slate-700" for="type">Type</label>
            <select id="type" v-model="form.type" required class="w-full rounded-xl border border-slate-300 bg-slate-50 px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-sky-500 focus:ring-2 focus:ring-sky-100">
              <option disabled value="">Type</option>
              <option value="Core">Core</option>
              <option value="Elective">Elective</option>
            </select>
          </div>

          <div class="col-span-full flex flex-wrap items-center gap-3 pt-2">
            <button type="submit" :disabled="busy" class="rounded-xl bg-slate-900 px-5 py-2.5 text-sm font-medium text-white transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:bg-slate-400">
              {{ busy ? 'Saving...' : editingCourseId ? 'Save changes' : 'Add course' }}
            </button>
            <button v-if="editingCourseId" type="button" class="rounded-xl border border-slate-300 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50" @click="resetForm">
              Cancel
            </button>
          </div>
        </form>
      </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-[1.15fr_0.85fr]">
      <div class="rounded-32px border border-slate-200 bg-white p-6 shadow-lg shadow-slate-200/60">
        <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
          <div>
            <h3 class="text-xl font-semibold text-slate-950">Courses</h3>
            <p class="mt-1 text-sm text-slate-500">Filter and manage the current catalog.</p>
          </div>

          <div class="grid gap-3 sm:grid-cols-3">
            <input v-model="searchTerm" placeholder="Search course" class="rounded-xl border border-slate-300 bg-slate-50 px-4 py-2.5 text-sm text-slate-900 outline-none transition focus:border-sky-500 focus:ring-2 focus:ring-sky-100" />
            <select v-model="levelFilter" class="rounded-xl border border-slate-300 bg-slate-50 px-4 py-2.5 text-sm text-slate-900 outline-none transition focus:border-sky-500 focus:ring-2 focus:ring-sky-100">
              <option value="">All levels</option>
              <option value="100">100</option>
              <option value="200">200</option>
              <option value="300">300</option>
            </select>
            <select v-model="semesterFilter" class="rounded-xl border border-slate-300 bg-slate-50 px-4 py-2.5 text-sm text-slate-900 outline-none transition focus:border-sky-500 focus:ring-2 focus:ring-sky-100">
              <option value="">All semesters</option>
              <option value="First Semester">First Semester</option>
              <option value="Second Semester">Second Semester</option>
            </select>
          </div>
        </div>

        <div v-if="filteredCourses.length" class="mt-5 overflow-hidden rounded-24px border border-slate-200">
          <table class="min-w-full text-left text-sm text-slate-700">
            <thead class="bg-slate-50">
              <tr>
                <th class="border-b border-slate-200 px-4 py-3 font-medium text-slate-900">Title</th>
                <th class="border-b border-slate-200 px-4 py-3 font-medium text-slate-900">Code</th>
                <th class="border-b border-slate-200 px-4 py-3 font-medium text-slate-900">Level</th>
                <th class="border-b border-slate-200 px-4 py-3 font-medium text-slate-900">Semester</th>
                <th class="border-b border-slate-200 px-4 py-3 font-medium text-slate-900">Units</th>
                <th class="border-b border-slate-200 px-4 py-3 font-medium text-slate-900">Type</th>
                <th class="border-b border-slate-200 px-4 py-3 font-medium text-slate-900">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="course in filteredCourses" :key="course.id" class="bg-white even:bg-slate-50/70">
                <td class="px-4 py-3 align-middle">{{ course.title }}</td>
                <td class="px-4 py-3 align-middle font-medium text-sky-700">{{ course.code }}</td>
                <td class="px-4 py-3 align-middle">{{ course.level }}</td>
                <td class="px-4 py-3 align-middle">{{ course.semester }}</td>
                <td class="px-4 py-3 align-middle">{{ course.unit ?? '-' }}</td>
                <td class="px-4 py-3 align-middle">
                  <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700">
                    {{ course.type ?? '-' }}
                  </span>
                </td>
                <td class="px-4 py-3 align-middle">
                  <div class="flex flex-nowrap items-center justify-end gap-2">
                    <button type="button" @click="startEdit(course)" class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 transition hover:border-sky-300 hover:text-sky-700">
                      Edit
                    </button>
                    <button type="button" @click="emit('delete-course', course.id)" class="rounded-lg bg-rose-50 px-3 py-1.5 text-xs font-medium text-rose-700 transition hover:bg-rose-100">
                      Delete
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <p v-else class="mt-5 rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-4 py-6 text-sm text-slate-500">
          No courses match the current filters.
        </p>
      </div>

      <div class="rounded-30px border border-slate-200 bg-white p-6 shadow-lg shadow-slate-200/60">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <h3 class="text-xl font-semibold text-slate-950">Student registrations</h3>
            <p class="mt-1 text-sm text-slate-500">A quick view of submitted course selections.</p>
          </div>
          <span class="inline-flex items-center justify-center rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-600">
            {{ registrations.length }} records
          </span>
        </div>

        <div v-if="registrations.length" class="mt-5 space-y-3">
          <article
            v-for="registration in registrations"
            :key="registration.id"
            class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4"
          >
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
              <div>
                <p class="font-medium text-slate-950">{{ studentById(registration.studentId)?.name || 'Unknown' }}</p>
                <p class="mt-1 text-sm text-slate-600">{{ courseNames(registration.courseIds) || 'No courses linked' }}</p>
              </div>
              <span class="inline-flex items-center self-start rounded-full bg-white px-3 py-1 text-xs font-medium text-slate-600 sm:self-center">
                Level {{ studentById(registration.studentId)?.level || 'N/A' }}
              </span>
            </div>
          </article>
        </div>
        <p v-else class="mt-5 rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-4 py-6 text-sm text-slate-500">
          No registrations yet.
        </p>
      </div>
    </div>
  </section>
</template>

<script setup>
import { computed, ref } from 'vue'

const props = defineProps({
  courses: Array,
  registrations: Array,
  students: Array,
  busy: Boolean,
})

const emit = defineEmits(['add-course', 'update-course', 'delete-course'])

const createEmptyForm = () => ({
  title: '',
  code: '',
  unit: 3,
  level: '',
  semester: 'First Semester',
  type: 'Core',
})

const form = ref(createEmptyForm())
const editingCourseId = ref(null)
const searchTerm = ref('')
const levelFilter = ref('')
const semesterFilter = ref('')

const filteredCourses = computed(() => {
  const query = searchTerm.value.trim().toLowerCase()

  return props.courses.filter((course) => {
    const matchesSearch = !query
      || course.title.toLowerCase().includes(query)
      || course.code.toLowerCase().includes(query)
    const matchesLevel = !levelFilter.value || course.level === levelFilter.value
    const matchesSemester = !semesterFilter.value || course.semester === semesterFilter.value

    return matchesSearch && matchesLevel && matchesSemester
  })
})

const registeredStudentsCount = computed(() => {
  return new Set(
    props.registrations
      .map((registration) => registration.studentId)
      .filter((studentId) => studentId !== undefined && studentId !== null)
  ).size
})

const handleSubmit = () => {
  const payload = {
    title: form.value.title.trim(),
    code: form.value.code.trim().toUpperCase(),
    level: form.value.level,
    semester: form.value.semester,
    unit: Number(form.value.unit) || 0,
    type: form.value.type,
  }

  if (editingCourseId.value) {
    emit('update-course', { id: editingCourseId.value, payload })
  } else {
    emit('add-course', payload)
  }

  resetForm()
}

const startEdit = (course) => {
  editingCourseId.value = course.id
  form.value = {
    title: course.title,
    code: course.code,
    level: course.level,
    semester: course.semester,
    unit: course.unit,
    type: course.type,
  }
}

const resetForm = () => {
  editingCourseId.value = null
  form.value = createEmptyForm()
}

const studentById = (id) => props.students.find((student) => student.id === id)

const courseNames = (ids) => {
  return ids
    .map((courseId) => props.courses.find((course) => course.id === courseId))
    .filter(Boolean)
    .map((course) => course.code)
    .join(', ')
}
</script>
