<?php

use PHPUnit\Framework\TestCase;
use ReservaBot\Domain\Cliente\Cliente;

final class ClienteDomainTest extends TestCase
{
    private $clienteDomain;
    private $reservaDomain;
    private $usuarioId = 1;
    
    protected function setUp(): void
    {
        $this->clienteDomain = getContainer()->getClienteDomain();
        $this->reservaDomain = getContainer()->getReservaDomain();
        
        // Limpiar datos previos
        getPDO()->exec("DELETE FROM reservas");
        
        // Configurar horario disponible
        getPDO()->exec("DELETE FROM configuraciones_usuario");
        getPDO()->exec("
            INSERT INTO configuraciones_usuario (usuario_id, clave, valor)
            VALUES 
                (1, 'horario_lunes', '{\"activo\":true,\"ventanas\":[{\"inicio\":\"09:00\",\"fin\":\"18:00\",\"capacidad\":4}]}'),
                (1, 'horario_martes', '{\"activo\":true,\"ventanas\":[{\"inicio\":\"09:00\",\"fin\":\"18:00\",\"capacidad\":4}]}'),
                (1, 'horario_miercoles', '{\"activo\":true,\"ventanas\":[{\"inicio\":\"09:00\",\"fin\":\"18:00\",\"capacidad\":4}]}'),
                (1, 'intervalo_minutos', '60')
        ");
        
        // Crear reservas de prueba para varios clientes
        $reserva1 = $this->reservaDomain->crearReserva(
            'Juan Pérez',
            '612345678',
            new \DateTime('2025-10-20'),
            '10:00',
            $this->usuarioId
        );
        // Confirmar la primera reserva
        $this->reservaDomain->confirmarReserva($reserva1->getId(), $this->usuarioId);
        
        $this->reservaDomain->crearReserva(
            'Juan Pérez',
            '612345678',
            new \DateTime('2025-10-21'),
            '11:00',
            $this->usuarioId
        );
        
        $reserva3 = $this->reservaDomain->crearReserva(
            'María García',
            '623456789',
            new \DateTime('2025-10-22'),
            '12:00',
            $this->usuarioId
        );
        // Confirmar la tercera reserva
        $this->reservaDomain->confirmarReserva($reserva3->getId(), $this->usuarioId);
    }
    
    public function testListarClientes(): void
    {
        $resultado = $this->clienteDomain->listarClientes($this->usuarioId);
        
        $this->assertIsArray($resultado);
        $this->assertArrayHasKey('clientes', $resultado);
        $this->assertArrayHasKey('total', $resultado);
        $this->assertCount(2, $resultado['clientes']); // 2 clientes únicos
    }
    
    public function testListarClientesConBusqueda(): void
    {
        $resultado = $this->clienteDomain->listarClientes(
            $this->usuarioId,
            'Juan',
            1,
            20
        );
        
        $this->assertCount(1, $resultado['clientes']);
        $this->assertEquals('Juan Pérez', $resultado['clientes'][0]['ultimo_nombre']);
    }
    
    public function testObtenerDetalleCliente(): void
    {
        $detalle = $this->clienteDomain->obtenerDetalleCliente(
            '612345678',
            $this->usuarioId
        );
        
        $this->assertArrayHasKey('cliente', $detalle);
        $this->assertArrayHasKey('reservas', $detalle);
        $this->assertInstanceOf(Cliente::class, $detalle['cliente']);
        $this->assertCount(2, $detalle['reservas']); // Juan tiene 2 reservas
    }
    
    public function testObtenerDetalleClienteInexistente(): void
    {
        $this->expectException(\DomainException::class);
        
        $this->clienteDomain->obtenerDetalleCliente(
            '999999999',
            $this->usuarioId
        );
    }
    
    public function testBuscarPorTelefono(): void
    {
        $resultados = $this->clienteDomain->buscarPorTelefono(
            '612',
            $this->usuarioId
        );
        
        $this->assertIsArray($resultados);
        $this->assertNotEmpty($resultados);
        $this->assertEquals('612345678', $resultados[0]['telefono']);
    }
    
    public function testBuscarPorTelefonoTelefonoDemasiandoCorto(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        
        $this->clienteDomain->buscarPorTelefono('12', $this->usuarioId);
    }
    
    public function testBuscarPorNombreSiNoHayTelefono(): void
    {
        // Buscar por nombre cuando no coincide teléfono
        $resultados = $this->clienteDomain->buscarPorTelefono(
            'María',
            $this->usuarioId
        );
        
        $this->assertNotEmpty($resultados);
        $this->assertEquals('María García', $resultados[0]['ultimo_nombre']);
    }
    
    public function testEstadisticasCliente(): void
    {
        $detalle = $this->clienteDomain->obtenerDetalleCliente(
            '612345678',
            $this->usuarioId
        );
        
        $cliente = $detalle['cliente']->toArray();
        
        $this->assertEquals(2, $cliente['total_reservas']);
        $this->assertEquals(1, $cliente['reservas_confirmadas']);
        $this->assertEquals(1, $cliente['reservas_pendientes']);
        $this->assertEquals(0, $cliente['reservas_canceladas']);
    }
}