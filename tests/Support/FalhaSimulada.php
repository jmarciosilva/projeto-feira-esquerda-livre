<?php

namespace Tests\Support;

use RuntimeException;

/**
 * Falha injetada por teste, com tipo próprio.
 *
 * Capturar `RuntimeException` num teste é armadilha: `TestCase::fail()` sinaliza
 * com uma exceção que também descende dela, e um `catch` largo faria o teste
 * passar exatamente quando a falha injetada **não** aconteceu.
 */
class FalhaSimulada extends RuntimeException {}
