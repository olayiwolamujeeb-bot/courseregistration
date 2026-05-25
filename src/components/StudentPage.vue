<template>
  <div class="w-full max-w-6xl space-y-8">
    <section class="rounded-[2rem] bg-white p-8 shadow-2xl shadow-slate-200/80">
      <div class="flex flex-col gap-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
          <div>
            <p class="text-sm uppercase tracking-[0.35em] text-sky-600">Student dashboard</p>
            <h1 class="mt-3 text-3xl font-semibold text-slate-950">Welcome back, {{ student.name }}</h1>
            <p class="mt-2 text-slate-600">Register the courses available for your current level and review them before submission.</p>
          </div>
          <div class="rounded-3xl bg-slate-50 p-5 text-slate-900 shadow-sm shadow-slate-200">
            <p class="text-xs uppercase tracking-[0.3em] text-slate-500">Current level</p>
            <p class="mt-3 text-3xl font-semibold">{{ student.level }}</p>
          </div>
        </div>

        <div class="grid gap-4 md:grid-cols-3">
          <div class="rounded-3xl border border-slate-200 bg-slate-50 p-5">
            <p class="text-xs uppercase tracking-[0.3em] text-slate-500">Selected courses</p>
            <p class="mt-3 text-3xl font-semibold text-slate-950">{{ selectedCourses.length }}</p>
          </div>
          <div class="rounded-3xl border border-slate-200 bg-slate-50 p-5">
            <p class="text-xs uppercase tracking-[0.3em] text-slate-500">Registration step</p>
            <p class="mt-3 text-3xl font-semibold text-slate-950">{{ step }} / 2</p>
          </div>
          <div class="rounded-3xl border border-slate-200 bg-slate-50 p-5">
            <p class="text-xs uppercase tracking-[0.3em] text-slate-500">Status</p>
            <p class="mt-3 text-3xl font-semibold text-slate-950">{{ step === 1 ? 'Selecting' : 'Completed' }}</p>
          </div>
        </div>
      </div>
    </section>

    <div class="space-y-8">
      <CourseSelection
        v-if="step === 1"
        :key="selectionResetKey"
        :student="student"
        :courses="availableCourses"
        :busy="busy"
        @submit-registration="onSubmitRegistration"
        @back="onBack"
      />

      <RegistrationSummary
        v-else
        :student="student"
        :courses="selectedCourses"
        @continue="continueStepping"
      />
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import CourseSelection from './CourseSelection.vue'
import RegistrationSummary from './RegistrationSummary.vue'

const props = defineProps({
  student: Object,
  courses: Array,
  busy: Boolean,
})

const emit = defineEmits(['register'])

const step = ref(1)
const selectedCourseIds = ref([])
const selectionResetKey = ref(0)

const availableCourses = computed(() => {
  return props.courses.filter((course) => course.level === props.student.level)
})

const selectedCourses = computed(() => {
  return props.courses.filter((course) => selectedCourseIds.value.includes(course.id))
})

watch(() => props.student, () => {
  step.value = 1
  selectedCourseIds.value = []
  selectionResetKey.value += 1
})

const onSubmitRegistration = (courseIds) => {
  emit('register', {
    courseIds,
    onSuccess: () => {
      selectedCourseIds.value = courseIds
      step.value = 2
    },
  })
}

const onBack = () => {
  selectedCourseIds.value = []
  step.value = 1
  selectionResetKey.value += 1
}

const continueStepping = () => {
  selectedCourseIds.value = []
  step.value = 1
  selectionResetKey.value += 1
}
</script>
