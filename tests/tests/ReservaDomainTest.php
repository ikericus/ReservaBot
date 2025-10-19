<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../public/src/domain/reserva/ReservaDomain.php';

final class ReservaDomainTest extends TestCase
{
    public function testCrearReservaGeneraId()
    {
        $domain = new ReservaDomain();
        $id = $domain->crearReserva(['fecha' => '2025-10-20']);
        $this->assertNotEmpty($id);
    }
}
