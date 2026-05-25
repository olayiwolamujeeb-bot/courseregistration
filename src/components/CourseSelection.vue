<template>
  <div class="w-full rounded-3xl bg-white p-8 shadow-2xl shadow-slate-300/20">
    <div class="mb-8">
      <h2 class="text-3xl font-bold text-slate-950">Select Your Courses</h2>
      <p class="mt-2 text-slate-600">Filter the available courses, pick from the dropdown, and build your registration list.</p>
    </div>

    <div class="grid gap-4 lg:grid-cols-4">
      <div class="space-y-2 lg:col-span-2">
        <label class="text-sm font-medium text-slate-700" for="course-search">Search</label>
        <input
          id="course-search"
          v-model="searchTerm"
          type="text"
          placeholder="Search by code or title"
          class="w-full rounded-2xl border border-slate-300 bg-slate-50 px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-sky-500 focus:ring-2 focus:ring-sky-200"
        />
      </div>

      <div class="space-y-2">
        <label class="text-sm font-medium text-slate-700" for="semester-filter">Semester</label>
        <select
          id="semester-filter"
          v-model="semesterFilter"
          class="w-full rounded-2xl border border-slate-300 bg-slate-50 px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-sky-500 focus:ring-2 focus:ring-sky-200"
        >
          <option value="">All semesters</option>
          <option value="First Semester">First Semester</option>
          <option value="Second Semester">Second Semester</option>
        </select>
      </div>

      <div class="space-y-2">
        <label class="text-sm font-medium text-slate-700" for="type-filter">Course type</label>
        <select
          id="type-filter"
          v-model="typeFilter"
          class="w-full rounded-2xl border border-slate-300 bg-slate-50 px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-sky-500 focus:ring-2 focus:ring-sky-200"
        >
          <option value="">All types</option>
          <option value="Core">Core</option>
          <option value="Elective">Elective</option>
        </select>
      </div>
    </div>

    <div class="mt-6 rounded-3xl border border-slate-200 bg-slate-50 p-5">
      <div class="grid gap-4 lg:grid-cols-[1fr_auto]">
        <div class="space-y-2">
          <label class="text-sm font-medium text-slate-700" for="course-select">Course selection</label>
          <select
            id="course-select"
            v-model="selectedDropdownId"
            class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-sky-500 focus:ring-2 focus:ring-sky-200"
          >
            <option value="">Choose a course</option>
            <option
              v-for="course in selectableCourses"
              :key="course.id"
              :value="String(course.id)"
            >
              {{ course.code }} - {{ course.title }} ({{ course.semester }})
            </option>
          </select>
        </div>

        <div class="flex items-end">
          <button
            type="button"
            :disabled="!selectedDropdownId"
            class="w-full rounded-2xl bg-sky-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-sky-700 disabled:cursor-not-allowed disabled:bg-slate-400"
            @click="addSelectedCourse"
          >
            Add course
          </button>
        </div>
      </div>
      <p class="mt-3 text-sm text-slate-500">{{ filteredCourses.length }} course(s) match your filters.</p>
    </div>

    <div class="mt-8 grid gap-6 xl:grid-cols-[1.15fr_0.85fr]">
      <div class="rounded-3xl border border-slate-200">
        <div class="border-b border-slate-200 bg-slate-50 px-5 py-4">
          <h3 class="text-lg font-semibold text-slate-950">Filtered courses</h3>
        </div>

        <div v-if="filteredCourses.length" class="overflow-x-auto">
          <table class="w-full text-left text-sm">
            <thead class="border-b border-slate-200 bg-white">
              <tr>
                <th class="px-5 py-4 font-semibold text-slate-900">Code</th>
                <th class="px-5 py-4 font-semibold text-slate-900">Title</th>
                <th class="px-5 py-4 font-semibold text-slate-900">Semester</th>
                <th class="px-5 py-4 font-semibold text-slate-900">Type</th>
                <th class="px-5 py-4 font-semibold text-slate-900">Units</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
              <tr v-for="course in filteredCourses" :key="course.id" class="bg-white">
                <td class="px-5 py-4 font-semibold text-sky-700">{{ course.code }}</td>
                <td class="px-5 py-4 text-slate-700">{{ course.title }}</td>
                <td class="px-5 py-4 text-slate-700">{{ course.semester }}</td>
                <td class="px-5 py-4 text-slate-700">{{ course.type }}</td>
                <td class="px-5 py-4 text-slate-700">{{ course.unit }}</td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-else class="px-5 py-8 text-center text-sm text-slate-500">
          No courses match the current filters.
        </div>
      </div>

      <div class="rounded-3xl border border-slate-200 bg-slate-50 p-5">
        <div class="flex items-center justify-between gap-4">
          <h3 class="text-lg font-semibold text-slate-950">Selected courses</h3>
          <span class="rounded-full bg-white px-3 py-1 text-xs font-semibold text-slate-600">{{ selectedCourses.length }} selected</span>
        </div>

        <div v-if="selectedCourses.length" class="mt-5 space-y-3">
          <div
            v-for="course in selectedCourses"
            :key="course.id"
            class="rounded-2xl bg-white px-4 py-4 shadow-sm shadow-slate-200"
          >
            <div class="flex items-start justify-between gap-4">
              <div>
                <p class="font-semibold text-slate-950">{{ course.code }}</p>
                <p class="mt-1 text-sm text-slate-600">{{ course.title }}</p>
                <p class="mt-2 text-xs uppercase tracking-[0.2em] text-slate-500">{{ course.semester }} | {{ course.type }} | {{ course.unit }} units</p>
              </div>
              <button
                type="button"
                class="rounded-full bg-rose-100 px-3 py-1 text-xs font-semibold text-rose-700 transition hover:bg-rose-200"
                @click="removeCourse(course.id)"
              >
                Remove
              </button>
            </div>
          </div>
        </div>

        <div v-else class="mt-5 rounded-2xl border border-dashed border-slate-300 px-4 py-6 text-center text-sm text-slate-500">
          No course selected yet.
        </div>
      </div>
    </div>

    <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
      <button
        type="button"
        class="rounded-lg border-2 border-slate-300 bg-white px-6 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 hover:border-slate-400"
        @click="$emit('back')"
      >
        Reset selection
      </button>
      <div class="flex items-center gap-3">
        <span v-if="selectedIds.length > 0" class="text-sm font-medium text-slate-600">{{ selectedIds.length }} course(s) ready</span>
        <button
          type="button"
          :disabled="selectedIds.length === 0 || busy"
          class="rounded-lg bg-sky-600 px-6 py-3 text-sm font-medium text-white transition hover:bg-sky-700 disabled:cursor-not-allowed disabled:bg-slate-400"
          @click="submitRegistration"
        >
          {{ busy ? 'Submitting...' : 'Continue' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue'

const props = defineProps({
  student: Object,
  courses: Array,
  busy: Boolean,
})

const emit = defineEmits(['submit-registration', 'back'])

const selectedIds = ref([])
const searchTerm = ref('')
const semesterFilter = ref('')
const typeFilter = ref('')
const selectedDropdownId = ref('')

const filteredCourses = computed(() => {
  const query = searchTerm.value.trim().toLowerCase()

  return props.courses.filter((course) => {
    const matchesSearch = !query
      || course.code.toLowerCase().includes(query)
      || course.title.toLowerCase().includes(query)
    const matchesSemester = !semesterFilter.value || course.semester === semesterFilter.value
    const matchesType = !typeFilter.value || course.type === typeFilter.value

    return matchesSearch && matchesSemester && matchesType
  })
})

const selectableCourses = computed(() => {
  return filteredCourses.value.filter((course) => !selectedIds.value.includes(course.id))
})

const selectedCourses = computed(() => {
  return props.courses.filter((course) => selectedIds.value.includes(course.id))
})

watch(() => props.student, () => {
  selectedIds.value = []
  searchTerm.value = ''
  semesterFilter.value = ''
  typeFilter.value = ''
  selectedDropdownId.value = ''
})

const addSelectedCourse = () => {
  const courseId = Number(selectedDropdownId.value)
  if (!courseId || selectedIds.value.includes(courseId)) {
    return
  }

  selectedIds.value.push(courseId)
  selectedDropdownId.value = ''
}

const removeCourse = (courseId) => {
  selectedIds.value = selectedIds.value.filter((id) => id !== courseId)
}

const submitRegistration = () => {
  emit('submit-registration', [...selectedIds.value])
}
</script>
