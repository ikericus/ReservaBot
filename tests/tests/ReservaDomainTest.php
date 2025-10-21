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
        $reserva = $this->reservaDomain->crearReserva(
            'Juan Pérez',
            '612345678',
            new \DateTime('2025-10-20'),
            '10:00',
            $this->usuarioId,
            'Reserva de prueba'
        );
        
        $this->assertIsInt($reserva->getId());
        $this->assertGreaterThan(0, $reserva->getId());
    }
    
    public function testObtenerReservasPorFecha(): void
    {
        // Crear reservas de prueba
        $this->reservaDomain->crearReserva(
            'Cliente 1',
            '611111111',
            new \DateTime('2025-10-20'),
            '10:00',
            $this->usuarioId
        );
        
        $this->reservaDomain->crearReserva(
            'Cliente 2',
            '622222222',
            new \DateTime('2025-10-20'),
            '11:00',
            $this->usuarioId
        );
        
        $reservas = $this->reservaDomain->obtenerReservasPorFecha(
            new \DateTime('2025-10-20'),
            $this->usuarioId
        );
        
        $this->assertCount(2, $reservas);
        $this->assertInstanceOf(Reserva::class, $reservas[0]);
    }
    
    public function testVerificarDisponibilidad(): void
    {
        // Día disponible sin reservas
        $disponible = $this->reservaDomain->verificarDisponibilidad(
            new \DateTime('2025-10-20'),
            '10:00',
            $this->usuarioId
        );
        
        $this->assertTrue($disponible);
    }
    
    public function testVerificarDisponibilidadHoraOcupada(): void
    {
        // Crear 4 reservas (capacidad completa)
        for ($i = 1; $i <= 4; $i++) {
            $this->reservaDomain->crearReserva(
                "Cliente $i",
                "61111111$i",
                new \DateTime('2025-10-20'),
                '10:00',
                $this->usuarioId
            );
        }
        
        // Verificar que ya no hay disponibilidad
        $disponible = $this->reservaDomain->verificarDisponibilidad(
            new \DateTime('2025-10-20'),
            '10:00',
            $this->usuarioId
        );
        
        $this->assertFalse($disponible);
    }
    
    public function testObtenerHorasDisponibles(): void
    {
        $horas = $this->reservaDomain->obtenerHorasDisponibles(
            new \DateTime('2025-10-20'),
            $this->usuarioId
        );
        
        $this->assertIsArray($horas);
        $this->assertNotEmpty($horas);
        $this->assertContains('09:00', $horas);
        $this->assertContains('17:00', $horas);
    }
    
    public function testConfirmarReserva(): void
    {
        $reserva = $this->reservaDomain->crearReserva(
            'Cliente Test',
            '612345678',
            new \DateTime('2025-10-20'),
            '10:00',
            $this->usuarioId
        );
        
        $this->reservaDomain->confirmarReserva($reserva->getId(), $this->usuarioId);
        
        // Verificar que el estado cambió
        $reservas = $this->reservaDomain->obtenerReservasPorFecha(
            new \DateTime('2025-10-20'),
            $this->usuarioId
        );
        
        $this->assertEquals('confirmada', $reservas[0]->getEstado()->value);
    }
    
    public function testCancelarReserva(): void
    {
        $reserva = $this->reservaDomain->crearReserva(
            'Cliente Test',
            '612345678',
            new \DateTime('2025-10-20'),
            '10:00',
            $this->usuarioId
        );
        
        $this->reservaDomain->cancelarReserva($reserva->getId(), $this->usuarioId);
        
        // Verificar que el estado cambió
        $reservas = $this->reservaDomain->obtenerReservasPorFecha(
            new \DateTime('2025-10-20'),
            $this->usuarioId
        );
        
        $this->assertEquals('cancelada', $reservas[0]->getEstado()->value);
    }
    
    public function testNoPermitirReservaFueraDHorario(): void
    {
        $this->expectException(\DomainException::class);
        
        $this->reservaDomain->crearReserva(
            'Cliente Test',
            '612345678',
            new \DateTime('2025-10-20'),
            '20:00', // Fuera de horario
            $this->usuarioId
        );
    }
}