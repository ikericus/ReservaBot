<?php
// tests/SimpleReservaTest.php

use PHPUnit\Framework\TestCase;
use ReservaBot\Domain\Reserva\ReservaDomain;
use ReservaBot\Infrastructure\ReservaRepository;
use ReservaBot\Infrastructure\DisponibilidadRepository;
use DateTime;
use PDO;

class SimpleReservaTest extends TestCase
{
    private $db;
    private $reservaDomain;
    
    protected function setUp(): void
    {
        // 1. Crear BD SQLite en memoria
        $this->db = new PDO('sqlite::memory:');
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // 2. Crear tablas (mínimas)
        $this->db->exec("
            CREATE TABLE reservas (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                usuario_id INTEGER,
                fecha DATE,
                hora TIME,
                nombre VARCHAR(255),
                telefono VARCHAR(20),
                estado VARCHAR(20) DEFAULT 'pendiente',
                mensaje TEXT,
                whatsapp_id VARCHAR(50),
                access_token VARCHAR(64),
                notas_internas TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );
            
            CREATE TABLE configuraciones_usuario (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                usuario_id INTEGER,
                clave VARCHAR(50),
                valor TEXT
            );
            
            CREATE TABLE usuarios (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                nombre VARCHAR(255),
                email VARCHAR(255),
                plan VARCHAR(20)
            );
        ");
        
        // 3. Insertar usuario test
        $this->db->exec("INSERT INTO usuarios (id, nombre, email, plan) VALUES (1, 'Test', 'test@test.com', 'premium')");
        
        // 4. Configurar horario (lunes 9-18)
        $this->db->exec("
            INSERT INTO configuraciones_usuario (usuario_id, clave, valor) VALUES 
            (1, 'horario_lunes', '{\"activo\":true,\"ventanas\":[{\"inicio\":\"09:00\",\"fin\":\"18:00\",\"capacidad\":4}]}'),
            (1, 'intervalo_minutos', '60')
        ");
        
        // 5. Crear los repositorios
        $reservaRepo = new ReservaRepository($this->db);
        $disponibilidadRepo = new DisponibilidadRepository($this->db);
        
        // 6. Crear el Domain
        $this->reservaDomain = new ReservaDomain($reservaRepo, $disponibilidadRepo);
    }
    
    public function testCrearReservaBasico()
    {
        // Crear una reserva
        $reserva = $this->reservaDomain->crearReserva(
            'Juan Pérez',
            '612345678',
            new DateTime('2025-10-20'),
            '10:00',
            1  // usuario_id
        );
        
        // Verificar que existe y tiene ID
        $this->assertNotNull($reserva);
        $this->assertNotNull($reserva->getId());
        $this->assertGreaterThan(0, $reserva->getId());
    }
    
    public function testObtenerReservasPorFecha()
    {
        // Crear una reserva
        $this->reservaDomain->crearReserva(
            'Juan Pérez',
            '612345678',
            new DateTime('2025-10-20'),
            '10:00',
            1
        );
        
        // Obtenerlas por fecha
        $reservas = $this->reservaDomain->obtenerReservasPorFecha(
            new DateTime('2025-10-20'),
            1
        );
        
        // Verificar
        $this->assertCount(1, $reservas);
        $this->assertEquals('Juan Pérez', $reservas[0]->getNombre());
    }
    
    public function testVerificarDisponibilidad()
    {
        // Comprobar que lunes 10:00 está disponible
        $disponible = $this->reservaDomain->verificarDisponibilidad(
            new DateTime('2025-10-20'),  // Es lunes
            '10:00',
            1
        );
        
        $this->assertTrue($disponible);
    }
}
