<?php

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use Behat\MinkExtension\Context\MinkContext;

/**
 * Defines application features from the specific context.
 */
class FeatureContext extends MinkContext implements Context
{
    /**
     * Initializes context.
     */
    public function __construct()
    {
        // Constructor vacío por ahora
    }

    /**
     * @Given estoy en la página de inicio
     */
    public function estoyEnLaPaginaDeInicio()
    {
        $this->visitPath('/');
    }

    /**
     * @Given estoy en la página :page
     */
    public function estoyEnLaPagina($page)
    {
        $this->visitPath($page);
    }

    /**
     * @When hago clic en :linkText
     */
    public function hagoClicEn($linkText)
    {
        $this->clickLink($linkText);
    }

    /**
     * @When hago clic en el botón :buttonText
     */
    public function hagoClicEnElBoton($buttonText)
    {
        $this->pressButton($buttonText);
    }

    /**
     * @Then debería ver el título :title
     */
    public function deberiaVerElTitulo($title)
    {
        $this->assertPageContainsText($title);
    }

    /**
     * @Then debería ver :text
     */
    public function deberiaVer($text)
    {
        $this->assertPageContainsText($text);
    }

    /**
     * @Then no debería ver :text
     */
    public function noDeberiaVer($text)
    {
        $this->assertPageNotContainsText($text);
    }

    /**
     * @When completo el campo :field con :value
     */
    public function completoElCampoCon($field, $value)
    {
        $this->fillField($field, $value);
    }

    /**
     * @When completo el formulario con:
     */
    public function completoElFormularioCon(TableNode $table)
    {
        foreach ($table->getRowsHash() as $field => $value) {
            $this->fillField($field, $value);
        }
    }

    /**
     * @When envío el formulario
     */
    public function envioElFormulario()
    {
        $this->getSession()->getPage()->pressButton('submit');
    }

    /**
     * @Then debería estar en la página :page
     */
    public function deberiaEstarEnLaPagina($page)
    {
        $this->assertSession()->addressEquals($this->locatePath($page));
    }

    /**
     * @Then la URL debería contener :text
     */
    public function laUrlDeberiaContener($text)
    {
        $this->assertSession()->addressMatches('/' . preg_quote($text, '/') . '/');
    }

    /**
     * @Then el código de respuesta debe ser :code
     */
    public function elCodigoDeRespuestaDebeSer($code)
    {
        $this->assertSession()->statusCodeEquals($code);
    }

    /**
     * @Then la respuesta debe ser JSON válido
     */
    public function laRespuestaDebeSerJsonValido()
    {
        $content = $this->getSession()->getPage()->getContent();
        $json = json_decode($content);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('La respuesta no es JSON válido: ' . json_last_error_msg());
        }
    }

    /**
     * @Then el JSON debe contener la clave :key
     */
    public function elJsonDebeContenerLaClave($key)
    {
        $content = $this->getSession()->getPage()->getContent();
        $json = json_decode($content, true);
        
        if (!isset($json[$key])) {
            throw new \Exception("La clave '$key' no existe en el JSON");
        }
    }

    /**
     * @Then el JSON debe contener :key con valor :value
     */
    public function elJsonDebeContenerConValor($key, $value)
    {
        $content = $this->getSession()->getPage()->getContent();
        $json = json_decode($content, true);
        
        if (!isset($json[$key])) {
            throw new \Exception("La clave '$key' no existe en el JSON");
        }
        
        if ($json[$key] != $value) {
            throw new \Exception("El valor de '$key' es '{$json[$key]}', se esperaba '$value'");
        }
    }

    /**
     * @Then el JSON :key debe ser verdadero
     */
    public function elJsonDebeSerVerdadero($key)
    {
        $content = $this->getSession()->getPage()->getContent();
        $json = json_decode($content, true);
        
        if (!isset($json[$key]) || $json[$key] !== true) {
            throw new \Exception("La clave '$key' no es verdadera");
        }
    }

    /**
     * @Then debería ver un elemento con id :id
     */
    public function deberiaVerUnElementoConId($id)
    {
        $element = $this->getSession()->getPage()->findById($id);
        
        if (null === $element) {
            throw new \Exception("No se encontró elemento con id '$id'");
        }
    }

    /**
     * @Then debería ver un elemento con clase :class
     */
    public function deberiaVerUnElementoConClase($class)
    {
        $element = $this->getSession()->getPage()->find('css', '.' . $class);
        
        if (null === $element) {
            throw new \Exception("No se encontró elemento con clase '$class'");
        }
    }

    /**
     * @When espero :seconds segundos
     */
    public function esperoSegundos($seconds)
    {
        sleep((int)$seconds);
    }

    /**
     * @Given estoy autenticado como :username
     */
    public function estoyAutenticadoComo($username)
    {
        // Implementar lógica de autenticación según tu sistema
        // Por ejemplo, visitar login y completar formulario
        $this->visitPath('/login');
        $this->fillField('username', $username);
        $this->fillField('password', 'test-password');
        $this->pressButton('Iniciar sesión');
    }

    /**
     * @Then debería ver una tabla con :rows filas
     */
    public function deberiaVerUnaTablaConFilas($rows)
    {
        $table = $this->getSession()->getPage()->find('css', 'table');
        
        if (null === $table) {
            throw new \Exception("No se encontró ninguna tabla");
        }
        
        $actualRows = count($table->findAll('css', 'tbody tr'));
        
        if ($actualRows != $rows) {
            throw new \Exception("Se esperaban $rows filas pero se encontraron $actualRows");
        }
    }
}