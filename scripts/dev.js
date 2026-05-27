import { spawn } from 'node:child_process'
import process from 'node:process'

const commands = [
  {
    name: 'backend',
    command: 'php',
    args: ['-S', '127.0.0.1:8000', 'backend/router.php'],
  },
  {
    name: 'frontend',
    command: process.platform === 'win32' ? 'npx.cmd' : 'npx',
    args: ['vite', '--host', '127.0.0.1'],
  },
]

const children = commands.map(({ name, command, args }) => {
  const child = spawn(command, args, {
    cwd: process.cwd(),
    stdio: ['ignore', 'pipe', 'pipe'],
    shell: process.platform === 'win32',
  })

  child.stdout.on('data', (chunk) => {
    process.stdout.write(`[${name}] ${chunk}`)
  })

  child.stderr.on('data', (chunk) => {
    process.stderr.write(`[${name}] ${chunk}`)
  })

  child.on('exit', (code, signal) => {
    if (signal) {
      return
    }

    console.error(`[${name}] exited with code ${code}`)
    stopAll()
    process.exit(code ?? 1)
  })

  return child
})

const stopAll = () => {
  for (const child of children) {
    if (!child.killed) {
      child.kill()
    }
  }
}

process.on('SIGINT', () => {
  stopAll()
  process.exit(0)
})

process.on('SIGTERM', () => {
  stopAll()
  process.exit(0)
})
