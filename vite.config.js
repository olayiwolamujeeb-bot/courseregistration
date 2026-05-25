import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import tailwindcss from '@tailwindcss/vite'

const seedCourses = [
  { id: 1, title: 'Physics 101', code: 'PHY101', level: '100', unit: 3, type: 'Core', semester: 'First Semester' },
  { id: 2, title: 'Mathematics 102', code: 'MTH102', level: '100', unit: 3, type: 'Core', semester: 'Second Semester' },
  { id: 3, title: 'Organic Chemistry', code: 'CHM201', level: '200', unit: 4, type: 'Elective', semester: 'First Semester' },
]

const store = {
  courses: [...seedCourses],
  students: [],
  registrations: [],
}

const readStore = (key) => store[key]
const writeStore = (key, value) => {
  store[key] = value
}
const nextId = (items) => items.reduce((maxId, item) => Math.max(maxId, Number(item.id) || 0), 0) + 1
const sendJson = (res, status, payload) => {
  res.statusCode = status
  res.setHeader('Content-Type', 'application/json')
  res.end(JSON.stringify(payload))
}

const apiPlugin = () => ({
  name: 'course-registration-api',
  configureServer(server) {
    server.middlewares.use('/api', (req, res) => {
      res.setHeader('Access-Control-Allow-Origin', '*')
      res.setHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
      res.setHeader('Access-Control-Allow-Headers', 'Content-Type, Accept')

      if (req.method === 'OPTIONS') {
        res.statusCode = 204
        res.end()
        return
      }

      const pathname = req.url ? req.url.split('?')[0] : '/'
      const segments = pathname.split('/').filter(Boolean)
      const resource = segments[0] || ''
      const resourceId = segments[1] ? Number(segments[1]) : null

      const collectBody = () => new Promise((resolve) => {
        let body = ''
        req.on('data', (chunk) => {
          body += chunk
        })
        req.on('end', () => {
          resolve(body ? JSON.parse(body) : {})
        })
      })

      const handleRequest = async () => {
        if (resource === 'courses' && req.method === 'GET') {
          sendJson(res, 200, readStore('courses'))
          return
        }

        if (resource === 'courses' && req.method === 'POST') {
          const payload = await collectBody()
          const courses = readStore('courses')
          const course = {
            id: nextId(courses),
            title: String(payload.title || '').trim(),
            code: String(payload.code || '').trim().toUpperCase(),
            level: String(payload.level || '').trim(),
            semester: String(payload.semester || '').trim(),
            unit: Number(payload.unit) || 0,
            type: String(payload.type || '').trim(),
          }
          courses.push(course)
          writeStore('courses', courses)
          sendJson(res, 201, course)
          return
        }

        if (resource === 'courses' && req.method === 'PUT' && resourceId) {
          const payload = await collectBody()
          const courses = readStore('courses')
          const index = courses.findIndex((course) => Number(course.id) === resourceId)
          if (index === -1) {
            sendJson(res, 404, { message: 'Course not found.' })
            return
          }

          const updatedCourse = {
            id: resourceId,
            title: String(payload.title || '').trim(),
            code: String(payload.code || '').trim().toUpperCase(),
            level: String(payload.level || '').trim(),
            semester: String(payload.semester || '').trim(),
            unit: Number(payload.unit) || 0,
            type: String(payload.type || '').trim(),
          }

          courses[index] = updatedCourse
          writeStore('courses', courses)
          sendJson(res, 200, updatedCourse)
          return
        }

        if (resource === 'courses' && req.method === 'DELETE' && resourceId) {
          const courses = readStore('courses').filter((course) => Number(course.id) !== resourceId)
          writeStore('courses', courses)

          const registrations = readStore('registrations').map((registration) => ({
            ...registration,
            course_ids: registration.course_ids.filter((id) => Number(id) !== resourceId),
          }))
          writeStore('registrations', registrations)

          res.statusCode = 204
          res.end()
          return
        }

        if (resource === 'students' && req.method === 'GET') {
          sendJson(res, 200, readStore('students'))
          return
        }

        if (resource === 'students' && req.method === 'POST') {
          const payload = await collectBody()
          const students = readStore('students')
          const student = {
            id: nextId(students),
            name: String(payload.name || '').trim(),
            level: String(payload.level || '').trim(),
            email: String(payload.email || '').trim(),
            matric: String(payload.matric || '').trim(),
          }
          students.push(student)
          writeStore('students', students)
          sendJson(res, 201, student)
          return
        }

        if (resource === 'registrations' && req.method === 'GET') {
          sendJson(res, 200, readStore('registrations'))
          return
        }

        if (resource === 'registrations' && req.method === 'POST') {
          const payload = await collectBody()
          const registrations = readStore('registrations')
          const registration = {
            id: nextId(registrations),
            student_id: Number(payload.student_id) || 0,
            course_ids: Array.isArray(payload.course_ids) ? payload.course_ids.map((id) => Number(id)) : [],
            created_at: new Date().toISOString(),
          }
          registrations.push(registration)
          writeStore('registrations', registrations)
          sendJson(res, 201, registration)
          return
        }

        sendJson(res, 404, { message: 'API route not found.' })
      }

      handleRequest().catch((error) => {
        sendJson(res, 500, { message: error.message || 'API request failed.' })
      })
    })
  },
})

export default defineConfig({
  plugins: [vue(), tailwindcss(), apiPlugin()],
})
