const http = require('http')
const fs = require('fs')
const path = require('path')
const url = require('url')

const port = process.env.PORT || 3000
const distDir = path.join(__dirname, 'dist')

const mimeTypes = {
    '.css': 'text/css; charset=utf-8',
    '.html': 'text/html; charset=utf-8',
    '.js': 'application/javascript; charset=utf-8',
    '.json': 'application/json; charset=utf-8',
    '.svg': 'image/svg+xml',
    '.txt': 'text/plain; charset=utf-8',
    '.ico': 'image/x-icon',
    '.png': 'image/png',
    '.jpg': 'image/jpeg',
    '.jpeg': 'image/jpeg',
    '.webp': 'image/webp',
    '.woff': 'font/woff',
    '.woff2': 'font/woff2',
}

const getContentType = (filePath) => mimeTypes[path.extname(filePath).toLowerCase()] || 'application/octet-stream'

const sendFile = (filePath, res) => {
    fs.readFile(filePath, (error, data) => {
        if (error) {
            res.writeHead(500, { 'Content-Type': 'text/plain; charset=utf-8' })
            res.end('Failed to read file.')
            return
        }

        res.writeHead(200, { 'Content-Type': getContentType(filePath) })
        res.end(data)
    })
}

const sendIndex = (res) => {
    const indexPath = path.join(distDir, 'index.html')
    fs.readFile(indexPath, (error, data) => {
        if (error) {
            res.writeHead(500, { 'Content-Type': 'text/plain; charset=utf-8' })
            res.end('Build output not found. Run npm run build first.')
            return
        }

        res.writeHead(200, { 'Content-Type': 'text/html; charset=utf-8' })
        res.end(data)
    })
}

const server = http.createServer((req, res) => {
    const parsedUrl = url.parse(req.url || '/')
    const pathname = decodeURIComponent(parsedUrl.pathname || '/')

    if (pathname === '/health') {
        res.writeHead(200, { 'Content-Type': 'application/json; charset=utf-8' })
        res.end(JSON.stringify({ ok: true }))
        return
    }

    const requestedPath = path.join(distDir, pathname)

    if (!requestedPath.startsWith(distDir)) {
        res.writeHead(400, { 'Content-Type': 'text/plain; charset=utf-8' })
        res.end('Bad request.')
        return
    }

    fs.stat(requestedPath, (error, stats) => {
        if (!error && stats.isFile()) {
            sendFile(requestedPath, res)
            return
        }

        if (pathname.startsWith('/assets/') || path.extname(pathname)) {
            res.writeHead(404, { 'Content-Type': 'text/plain; charset=utf-8' })
            res.end('Not found.')
            return
        }

        sendIndex(res)
    })
})

server.listen(port, () => {
    console.log(`Server listening on port ${port}`)
})
