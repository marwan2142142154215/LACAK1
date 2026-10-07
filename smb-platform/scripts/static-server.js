// Minimal static file server (no external deps) used for production web hosting
// and the APK download host. Usage: node static-server.js <root> <port> [spa]
const http = require('http')
const fs = require('fs')
const path = require('path')

const root = path.resolve(process.argv[2] || '.')
const port = parseInt(process.argv[3] || '4173', 10)
const spa = process.argv[4] === 'spa'

const types = {
  '.html': 'text/html; charset=utf-8',
  '.js': 'text/javascript; charset=utf-8',
  '.css': 'text/css; charset=utf-8',
  '.json': 'application/json; charset=utf-8',
  '.svg': 'image/svg+xml',
  '.png': 'image/png',
  '.jpg': 'image/jpeg',
  '.jpeg': 'image/jpeg',
  '.ico': 'image/x-icon',
  '.apk': 'application/vnd.android.package-archive',
  '.txt': 'text/plain; charset=utf-8',
  '.map': 'application/json',
}

function serveFile(res, file) {
  fs.readFile(file, (err, data) => {
    if (err) {
      res.writeHead(404, { 'Content-Type': 'text/plain' })
      return res.end('not found')
    }
    res.writeHead(200, { 'Content-Type': types[path.extname(file)] || 'application/octet-stream' })
    res.end(data)
  })
}

http
  .createServer((req, res) => {
    const urlPath = decodeURIComponent((req.url || '/').split('?')[0])
    let file = path.join(root, urlPath)

    if (!file.startsWith(root)) {
      res.writeHead(403)
      return res.end('forbidden')
    }

    fs.stat(file, (err, stat) => {
      if (err || stat.isDirectory()) {
        file = path.join(root, urlPath, 'index.html')
      }
      fs.readFile(file, (e) => {
        if (e && spa) return serveFile(res, path.join(root, 'index.html'))
        if (e) {
          res.writeHead(404, { 'Content-Type': 'text/plain' })
          return res.end('not found')
        }
        serveFile(res, file)
      })
    })
  })
  .listen(port, '127.0.0.1', () => console.log(`SMB static server: ${root} on http://127.0.0.1:${port} spa=${spa}`))
