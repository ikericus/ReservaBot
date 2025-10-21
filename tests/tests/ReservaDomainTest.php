<?php

use PHPUnit\Framework\TestCase;
use ReservaBot\Domain\Reserva\Reserva;

final class ReservaDomainTest extends TestCase
{
    private $reservaDomain;
    private $usuarioId = 1;
    
    protected function setUp(): void
    {
        $this->reservaDomain = getContainer()->getReservaDomain();
        
        // Limpiar reservas previas
        getPDO()->exec("DELETE FROM reservas");
        
        // Configurar horario disponible (lunes a viernes 9:00-18:00)
        getPDO()->exec("DELETE FROM configuraciones_usuario");
        getPDO()->exec("
            INSERT INTO configuraciones_usuario (usuario_id, clave, valor)
            VALUES 
                (1, 'horario_lunes', '{\"activo\":true,\"ventanas\":[{\"inicio\":\"09:00\",\"fin\":\"18:00\",\"capacidad\":4}]}'),
                (1, 'horario_martes', '{\"activo\":true,\"ventanas\":[{\"inicio\":\"09:00\",\"fin\":\"18:00\",\"capacidad\":4}]}'),
                (1, 'horario_miercoles', '{\"activo\":true,\"ventanas\":[{\"inicio\":\"09:00\",\"fin\":\"18:00\",\"capacidad\":4}]}'),
                (1, 'horario_jueves', '{\"activo\":true,\"ventanas\":[{\"inicio\":\"09:00\",\"fin\":\"18:00\",\"capacidad\":4}]}'),
                (1, 'horario_viernes', '{\"activo\":true,\"ventanas\":[{\"inicio\":\"09:00\",\"fin\":\"18:00\",\"capacidad\":4}]}'),
                (1, 'intervalo_minutos', '60')
        ");
    }
    
    public function testCrearReservaGeneraId(): void
    {
        $datos = [
            'fecha' => '2025-10-20',
            'hora' => '10:00',
            'nombre' => 'Juan Pérez',
            'telefono' => '612345678',
            'email' => 'juan@test.com',
            'mensaje' => 'Reserva de prueba',
            'estado' => 'confirmada'
        ];
        
        $reservaId = $this->reservaDomain->crearReserva($datos, $this->usuarioId);
        
        $this->assertIsInt($reservaId);
        $this->assertGreaterThan(0, $reservaId);
    }
    
    public function testObtenerReservasPorFecha(): void
    {
        // Crear reservas de prueba
        $this->reservaDomain->crearReserva([
            'fecha' => '2025-10-20',
            'hora' => '10:00',
            'nombre' => 'Cliente 1',
            'telefono' => '611111111',
            'estado' => 'confirmada'
        ], $this->usuarioId);
        
        $this->reservaDomain->crearReserva([
            'fecha' => '2025-10-20',
            'hora' => '11:00',
            'nombre' => 'Cliente 2',
            'telefono' => '622222222',
            'estado' => 'pendiente'
        ], $this->usuarioId);
        
        $reservas = $this->reservaDomain->obtenerReservasPorFecha(
            new DateTime('2025-10-20'),
            $this->usuarioId
        );
        
        $this->assertCount(2, $reservas);
        $this->assertInstanceOf(Reserva::class, $reservas[0]);
    }
    
    public function testVerificarDisponibilidad(): void
    {
        // Día disponible sin reservas
        $disponible = $this->reservaDomain->verificarDisponibilidad(
            new DateTime('2025-10-20'),
            '10:00',
            $this->usuarioId
        );
        
        $this->assertTrue($disponible);
    }
    
    public function testVerificarDisponibilidadHoraOcupada(): void
    {
        // Crear 4 reservas (capacidad completa)
        for ($i = 1; $i <= 4; $i++) {
            $this->reservaDomain->crearReserva([
                'fecha' => '2025-10-20',
                'hora' => '10:00',
                'nombre' => "Cliente $i",
                'telefono' => "61111111$i",
                'estado' => 'confirmada'
            ], $this->usuarioId);
        }
        
        // Verificar que ya no hay disponibilidad
        $disponible = $this->reservaDomain->verificarDisponibilidad(
            new DateTime('2025-10-20'),
            '10:00',
            $this->usuarioId
        );
        
        $this->assertFalse($disponible);
    }
    
    public function testObtenerHorasDisponibles(): void
    {
        $horas = $this->reservaDomain->obtenerHorasDisponibles(
            new DateTime('2025-10-20'),
            $this->usuarioId
        );
        
        $this->assertIsArray($horas);
        $this->assertNotEmpty($horas);
        $this->assertContains('09:00', $horas);
        $this->assertContains('17:00', $horas);
    }
    
    public function testConfirmarReserva(): void
    {
        $reservaId = $this->reservaDomain->crearReserva([
            'fecha' => '2025-10-20',
            'hora' => '10:00',
            'nombre' => 'Cliente Test',
            'telefono' => '612345678',
            'estado' => 'pendiente'
        ], $this->usuarioId);
        
        $this->reservaDomain->confirmarReserva($reservaId, $this->usuarioId);
        
        // Verificar que el estado cambió
        $reservas = $this->reservaDomain->obtenerReservasPorFecha(
            new DateTime('2025-10-20'),
            $this->usuarioId
        );
        
        $this->assertEquals('confirmada', $reservas[0]->getEstado());
    }
    
    public function testCancelarReserva(): void
    {
        $reservaId = $this->reservaDomain->crearReserva([
            'fecha' => '2025-10-20',
            'hora' => '10:00',
            'nombre' => 'Cliente Test',
            'telefono' => '612345678',
            'estado' => 'confirmada'
        ], $this->usuarioId);
        
        $this->reservaDomain->cancelarReserva($reservaId, $this->usuarioId);
        
        // Verificar que el estado cambió
        $reservas = $this->reservaDomain->obtenerReservasPorFecha(
            new DateTime('2025-10-20'),
            $this->usuarioId
        );
        
        $this->assertEquals('cancelada', $reservas[0]->getEstado());
    }
    
    public function testNoPermitirReservaFueraDHorario(): void
    {
        $this->expectException(\DomainException::class);
        
        $this->reservaDomain->crearReserva([
            'fecha' => '2025-10-20',
            'hora' => '20:00', // Fuera de horario
            'nombre' => 'Cliente Test',
            'telefono' => '612345678'
        ], $this->usuarioId);
    }
}