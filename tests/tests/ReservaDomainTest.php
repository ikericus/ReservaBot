<?php

use PHPUnit\Framework\TestCase;

final class ReservaDomainTest extends TestCase
{
    public function testCrearReservaGeneraId()
    {
        $reservaDomain = getContainer()->getReservaDomain();
        $id = $reservaDomain->crearReserva(['fecha' => '2025-10-20']);
        $this->assertNotEmpty($id);
    }
}
