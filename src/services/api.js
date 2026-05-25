const API_BASE_URL = (import.meta.env.VITE_API_BASE_URL || '/api').replace(/\/$/, '')

const demoCourses = [
  { id: 1, title: 'Physics 101', code: 'PHY101', level: '100', unit: 3, type: 'Core', semester: 'First Semester' },
  { id: 2, title: 'Mathematics 102', code: 'MTH102', level: '100', unit: 3, type: 'Core', semester: 'Second Semester' },
  { id: 3, title: 'Organic Chemistry', code: 'CHM201', level: '200', unit: 4, type: 'Elective', semester: 'First Semester' },
]

const DEFAULT_TIMEOUT_MS = 10000
const LOGIN_TIMEOUT_MS = 10000

const normalizeCourse = (course) => ({
  id: course.id,
  title: course.title,
  code: course.code,
  level: String(course.level),
  unit: Number(course.unit),
  type: course.type,
  semester: course.semester,
})

const normalizeStudent = (student) => ({
  id: student.id,
  name: student.name,
  level: String(student.level),
  email: student.email || '',
  matric: student.matric || '',
})

const normalizeRegistration = (registration) => ({
  id: registration.id,
  studentId: registration.studentId ?? registration.student_id,
  courseIds: registration.courseIds ?? registration.course_ids ?? [],
  createdAt: registration.createdAt ?? registration.created_at,
})

const request = async (path, options = {}) => {
  const controller = new AbortController()
  const timeoutMs = options.timeoutMs ?? DEFAULT_TIMEOUT_MS
  const timeoutId = setTimeout(() => controller.abort(), timeoutMs)

  try {
    const response = await fetch(`${API_BASE_URL}${path}`, {
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        ...(options.headers || {}),
      },
      signal: controller.signal,
      ...options,
    })

    if (!response.ok) {
      let message = 'Request failed.'
      try {
        const payload = await response.json()
        message = payload.message || message
      } catch {
        message = response.statusText || message
      }
      throw new Error(message)
    }

    if (response.status === 204) {
      return null
    }

    return response.json()
  } catch (error) {
    if (error.name === 'AbortError') {
      throw new Error('The request timed out.')
    }
    throw error
  } finally {
    clearTimeout(timeoutId)
  }
}

export const api = {
  async bootstrap() {
    try {
      const [courses, students, registrations] = await Promise.all([
        request('/courses'),
        request('/students', { timeoutMs: 10000 }),
        request('/registrations', { timeoutMs: 10000 }),
      ])

      return {
        courses: courses.map(normalizeCourse),
        students: students.map(normalizeStudent),
        registrations: registrations.map(normalizeRegistration),
        usingFallback: false,
      }
    } catch {
      return {
        courses: demoCourses,
        students: [],
        registrations: [],
        usingFallback: true,
      }
    }
  },

  createCourse(course) {
    return request('/courses', {
      method: 'POST',
      body: JSON.stringify(course),
    }).then(normalizeCourse)
  },

  updateCourse(courseId, course) {
    return request(`/courses/${courseId}`, {
      method: 'PUT',
      body: JSON.stringify(course),
    }).then(normalizeCourse)
  },

  deleteCourse(courseId) {
    return request(`/courses/${courseId}`, {
      method: 'DELETE',
    })
  },

  createStudent(student) {
    return request('/students', {
      method: 'POST',
      body: JSON.stringify(student),
      timeoutMs: LOGIN_TIMEOUT_MS,
    }).then(normalizeStudent)
  },

  createRegistration(registration) {
    return request('/registrations', {
      method: 'POST',
      body: JSON.stringify(registration),
    }).then(normalizeRegistration)
  },
}

export { API_BASE_URL }
