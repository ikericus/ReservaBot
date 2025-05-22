const express = require('express');
const path = require('path');

// Crear la aplicación Express
const app = express();
const port = 3000;

// Middleware para servir archivos estáticos desde la carpeta 'public'
app.use(express.static(path.join(__dirname, '..', 'public')));

// Ruta por defecto para cualquier otra URL
app.get('/', (req, res) => {
  res.sendFile(path.join(__dirname, '..', 'public', 'index.html'));
});

// Ruta explícita para cada página HTML
app.get('/calendar', (req, res) => {
  res.sendFile(path.join(__dirname, '..', 'public', 'calendario.html'));
});

app.get('/day', (req, res) => {
  res.sendFile(path.join(__dirname, '..', 'public', 'day.html'));
});

app.get('/reserva-form', (req, res) => {
  res.sendFile(path.join(__dirname, '..', 'public', 'reserva-form.html'));
});

app.get('/reserva-detail', (req, res) => {
  res.sendFile(path.join(__dirname, '..', 'public', 'reserva-detail.html'));
});

app.get('/config', (req, res) => {
  res.sendFile(path.join(__dirname, '..', 'public', 'config.html'));
});

// Iniciar el servidor
app.listen(port, () => {
  console.log(`Servidor funcionando en http://localhost:${port}`);
});