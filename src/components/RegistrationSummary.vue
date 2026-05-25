<template>
  <div class="w-full rounded-[2rem] bg-white p-8 shadow-2xl shadow-slate-200/60">
    <div class="mb-8 grid gap-6 lg:grid-cols-[1.4fr_0.9fr]">
      <section class="rounded-[2rem] bg-slate-50 p-8 shadow-sm shadow-slate-200">
        <div class="space-y-4">
          <p class="text-sm uppercase tracking-[0.35em] text-slate-500">Registration summary</p>
          <h2 class="text-3xl font-semibold text-slate-950">Review your selected courses</h2>
          <p class="text-slate-600">Keep the selected courses and download the PDF when you are ready. You can then start a fresh registration round without leaving your session.</p>
        </div>
      </section>

      <aside class="rounded-[2rem] border border-slate-200 bg-slate-50 p-6">
        <div class="space-y-4">
          <div class="rounded-3xl bg-white p-4 shadow-sm shadow-slate-200">
            <p class="text-xs uppercase tracking-[0.35em] text-slate-500">Student</p>
            <p class="mt-2 text-xl font-semibold text-slate-950">{{ student.name }}</p>
          </div>
          <div class="rounded-3xl bg-white p-4 shadow-sm shadow-slate-200">
            <p class="text-xs uppercase tracking-[0.35em] text-slate-500">Level</p>
            <p class="mt-2 text-xl font-semibold text-slate-950">{{ student.level }}</p>
          </div>
          <div class="rounded-3xl bg-white p-4 shadow-sm shadow-slate-200">
            <p class="text-xs uppercase tracking-[0.35em] text-slate-500">Total courses</p>
            <p class="mt-2 text-xl font-semibold text-slate-950">{{ courses.length }}</p>
          </div>
        </div>
      </aside>
    </div>

    <div class="space-y-6">
      <div class="rounded-[2rem] border border-slate-200 bg-slate-50 p-6">
        <div class="flex items-center justify-between gap-4">
          <div>
            <p class="text-sm uppercase tracking-[0.35em] text-slate-500">Registered Courses</p>
            <h3 class="mt-2 text-2xl font-semibold text-slate-950">{{ courses.length }} course{{ courses.length === 1 ? '' : 's' }} selected</h3>
          </div>
          <div class="rounded-3xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white">{{ groupedSemesters.length }} semester{{ groupedSemesters.length === 1 ? '' : 's' }}</div>
        </div>
      </div>

      <div v-if="courses.length" class="space-y-6">
        <template v-for="semesterName in groupedSemesters" :key="semesterName">
          <section class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm shadow-slate-200">
            <div class="mb-5 flex items-center justify-between gap-3">
              <p class="text-sm uppercase tracking-[0.35em] text-slate-400">{{ semesterName }}</p>
              <span class="rounded-full bg-sky-100 px-3 py-1 text-xs font-semibold text-sky-700">{{ groupedCourses[semesterName].length }} course{{ groupedCourses[semesterName].length === 1 ? '' : 's' }}</span>
            </div>
            <div class="space-y-4">
              <div v-for="(course, idx) in groupedCourses[semesterName]" :key="course.id" class="grid gap-3 rounded-3xl border border-slate-200 bg-slate-50 p-4 md:grid-cols-[auto_1fr_auto] md:items-center">
                <div class="flex h-12 w-12 items-center justify-center rounded-full bg-sky-600 font-semibold text-white">{{ idx + 1 }}</div>
                <div class="space-y-2">
                  <p class="font-semibold text-slate-950">{{ course.code }} - {{ course.title }}</p>
                  <p class="text-sm text-slate-500">{{ course.unit }} unit{{ course.unit === 1 ? '' : 's' }} - {{ course.type }}</p>
                </div>
                <div class="rounded-3xl bg-slate-200 px-4 py-2 text-right text-sm font-semibold text-slate-700">Level {{ course.level }}</div>
              </div>
            </div>
          </section>
        </template>
      </div>

      <div v-else class="rounded-[2rem] border border-dashed border-slate-300 bg-slate-50 p-8 text-center text-slate-600">
        <p class="font-medium">No courses have been selected yet.</p>
        <p class="mt-2 text-sm">Go back to choose courses that match your level.</p>
      </div>
    </div>

    <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:justify-between">
      <button type="button" class="rounded-lg bg-slate-900 px-5 py-3 text-sm font-medium text-white transition hover:bg-slate-800" @click="downloadPdf">Download PDF</button>
      <button type="button" class="rounded-lg bg-sky-600 px-5 py-3 text-sm font-medium text-white transition hover:bg-sky-700" @click="$emit('continue')">Start another registration</button>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { jsPDF } from 'jspdf'

const props = defineProps({
  student: Object,
  courses: Array,
})

defineEmits(['continue'])

const groupedCourses = computed(() => {
  return props.courses.reduce((groups, course) => {
    const semester = course.semester || 'Unspecified Semester'
    if (!groups[semester]) groups[semester] = []
    groups[semester].push(course)
    return groups
  }, {})
})

const groupedSemesters = computed(() => Object.keys(groupedCourses.value))

const downloadPdf = () => {
  const doc = new jsPDF()
  const startX = 15
  let y = 20

  doc.setFontSize(16)
  doc.text('Course Registration Summary', startX, y)

  doc.setFontSize(11)
  y += 10
  doc.text(`Full Name: ${props.student.name}`, startX, y)
  y += 8
  doc.text(`Level: ${props.student.level}`, startX, y)
  y += 12

  groupedSemesters.value.forEach((semesterName) => {
    doc.setFontSize(12)
    doc.text(semesterName, startX, y)
    y += 8

    doc.setFontSize(10)
    const columns = {
      code: 15,
      title: 40,
      unit: 130,
      type: 160,
    }

    doc.text('Code', columns.code, y)
    doc.text('Title', columns.title, y)
    doc.text('Units', columns.unit, y)
    doc.text('Type', columns.type, y)

    y += 6
    doc.setLineWidth(0.2)
    doc.line(15, y - 4, 195, y - 4)
    y += 6

    groupedCourses.value[semesterName].forEach((course) => {
      if (y > 280) {
        doc.addPage()
        y = 20
      }

      doc.text(course.code || '', columns.code, y)
      doc.text(course.title || '', columns.title, y)
      doc.text(String(course.unit ?? '-'), columns.unit, y)
      doc.text(course.type || '-', columns.type, y)
      y += 8
    })

    y += 10
  })

  const fileName = `registration-summary-${props.student.name?.replace(/\s+/g, '_') || 'student'}.pdf`
  doc.save(fileName)
}
</script>
