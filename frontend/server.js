#!/usr/bin/env node
// Wrapper script to start Next.js standalone server
// This ensures the correct server.js is executed

const { spawn } = require('child_process');
const path = require('path');

const serverPath = path.join(__dirname, '.next', 'standalone', 'server.js');

// console.log('Starting Next.js server from:', serverPath);

// Start the actual server
const server = spawn('node', [serverPath], {
  stdio: 'inherit',
  env: process.env
});

server.on('error', (err) => {
  console.error('Failed to start server:', err);
  process.exit(1);
});

server.on('exit', (code) => {
  process.exit(code);
});
