// Teste simples para verificar se o servidor funciona
const { spawn } = require('child_process');

// console.log('Iniciando servidor de desenvolvimento...');

const server = spawn('npm', ['run', 'dev'], {
  stdio: 'inherit',
  shell: true
});

server.on('error', (err) => {
  console.error('Erro ao iniciar servidor:', err);
});

server.on('close', (code) => {
  // console.log(`Servidor finalizado com código: ${code}`);
});

// Parar o servidor após 10 segundos para teste
setTimeout(() => {
  // console.log('Parando servidor...');
  server.kill();
}, 10000);
